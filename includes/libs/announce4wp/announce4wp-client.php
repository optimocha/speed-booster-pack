<?php

if ( ! class_exists( "Announce4WP_Client" ) ) {
	class Announce4WP_Client {
		private $api_endpoint_url = '';
		private $service_id = '';
		private $transient_name = '';
		private $settings_screen = '';
		private $plugin_name = '';
		private $is_allowed = true;

		public function __construct( $plugin_name, $service_id, $api_endpoint_url, $settings_screen ) {

			// LAHMACUNTODO: current_user_can( 'manage_options' ) kontrolünü burada yapmak daha doğru olmaz mı? veya en azından aşağıdaki hook'ları ateşlerken mesela

			$this->service_id       = $service_id;
			$this->api_endpoint_url = $api_endpoint_url;
			$this->settings_screen  = $settings_screen;
			$this->plugin_name      = $plugin_name;
			$this->transient_name   = 'a4wp_' . $this->service_id . '_announcements';

			// Check disabled plugins
			$this->check_if_disabled();

			if ( ! $this->is_allowed ) {
				return;
			}

			add_action( 'admin_init', [ $this, 'admin_init' ] );

			// Enqueue Dismiss Script
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Admin Notices
			add_action( 'admin_notices', [ $this, 'display_notices' ] );

			// Dismiss Notice Action
			add_action( 'wp_ajax_a4wp_dismiss_notice', [ $this, 'dismiss_notice' ] );
		}

		public function display_notices() {
			// Get notices
			$this->display_dismissible_notices();
			$this->display_permanent_notices();
		}

		public function save_notices() {
			if ( get_transient( $this->transient_name ) ) {
				return;
			}

			$remote_notices = $this->fetch_notices();
			if ( ! $remote_notices ) {
				return;
			}

			// Update transient
			set_transient( $this->transient_name, $remote_notices, 12 * HOUR_IN_SECONDS );
		}

		private function fetch_notices() {
			$notices = wp_remote_get( $this->api_endpoint_url );
			if ( $notices instanceof WP_Error ) {
				return false;
			}
			$notices = json_decode( $notices['body'], true );

			return $notices;
		}

		public function enqueue_scripts() {
			wp_add_inline_script( 'jquery',
				'jQuery(document).on(\'click\', \'.a4wp-notice .notice-dismiss\', function() {
		    var $notice = jQuery(this).parent();
		    var notice_id = $notice.data(\'notice-id\');
		    var service_id = $notice.data(\'service-id\');
		    var data = {action: \'a4wp_dismiss_notice\', notice_id: notice_id, service_id: service_id};
		    jQuery.get(ajaxurl, data);
		});' );
		}

		public function dismiss_notice() {
			if ( current_user_can( 'manage_options' ) && isset( $_GET['action'] ) && $_GET['action'] == 'a4wp_dismiss_notice' ) {
				$id         = $_GET['notice_id'];
				$service_id = $_GET['service_id'];
				if ( ! $service_id || $service_id != $this->service_id ) {
					return;
				}
				$last_ids = get_user_meta( get_current_user_id(), 'a4wp_dismissed_ids', true );
				$last_ids = $last_ids == '' ? [] : $last_ids;
				if ( ( isset( $last_ids[ $this->service_id ] ) && $last_ids[ $this->service_id ] < $id ) || ! isset( $last_ids[ $this->service_id ] ) ) {
					$last_ids[ $this->service_id ] = $id;
				}
				update_user_meta( get_current_user_id(), 'a4wp_dismissed_ids', $last_ids );
			}
		}

		private function parse_attributes( $rules ) {
			if ( ! $rules ) {
				return [];
			}

			$attributes = [];
			foreach ( $rules as $rule ) {
				if ( strpos( $rule, ":" ) !== false ) {
					list( $type, $rule ) = explode( ":", $rule );
					$attributes[ $type ] = $rule;
				}
			}

			return $attributes;
		}

		// LAHMACUNTODO: az biraz refactor ile aşağıdaki iki fonksiyonu tek bir fonksiyonda birleştirebiliriz gibi
		private function display_dismissible_notices() {
			$announcements = get_transient( $this->transient_name );
			if ( isset( $announcements['dismissible_notice'] ) && $notice = $announcements['dismissible_notice'] ) {
				$attributes = $this->parse_attributes( $notice['rules'] );
				$type       = isset( $attributes['type'] ) ? $attributes['type'] : 'notice-info';
				if ( $this->should_display( $attributes, $notice ) ) {
					echo '<div class="notice a4wp-notice ' . $type . ' is-dismissible" data-service-id="' . $this->service_id . '" data-notice-id="' . $notice['id'] . '">';
					echo ( $notice['title'] ) ? '<p style="font-size:120%;font-weight:700;">' . $notice['title'] . '</p>' : null;
					echo ( $notice['content'] ) ? '<p>' . $notice['content'] . '</p>' : null;
					echo '</div>';
				}
			}
		}

		private function display_permanent_notices() {
			if ( isset( get_transient( $this->transient_name )["permanent_notices"] ) ) {
				foreach ( get_transient( $this->transient_name )["permanent_notices"] as $notice ) {
					$attributes = $this->parse_attributes( $notice['rules'] );
					$type       = isset( $attributes['type'] ) ? $attributes['type'] : 'notice-info';
					if ( $this->should_display( $attributes ) ) {
						echo '<div class="notice ' . $type . '" data-service-id="' . $this->service_id . '" data-notice-id="' . $notice['id'] . '">';
						echo ( $notice['title'] ) ? '<p style="font-size:120%;font-weight:700;">' . $notice['title'] . '</p>' : null;
						echo ( $notice['content'] ) ? '<p>' . $notice['content'] . '</p>' : null;
						echo '</div>';
					}
				}
			}
		}

		/**
		 * @param $attributes
		 * @param null $notice required for dismissible notices
		 *
		 * @return bool
		 */
		private function should_display( $attributes, $notice = null ) {
			// Check Page
			$page = isset( $attributes['page'] ) ? $attributes['page'] : $this->settings_screen;
			if ( $page != "all" && $page != get_current_screen()->id ) {
				return false;
			}


			// Check Dismissible Notices
			if ( null !== $notice ) {
				$last_ids = get_user_meta( get_current_user_id(), 'a4wp_dismissed_ids', true );
				if ( isset( $last_ids[ $this->service_id ] ) && $last_ids[ $this->service_id ] >= $notice['id'] ) {
					return false;
				}
			}

			// LAHMACUNTODO: sürüm kontrolünü burada değil, ana sunucuda json çıktılarını oluştururken yapmak çok zor olur mu?
			// Check Min Version
			if ( isset( $attributes['min-version'] ) ) {
				$plugin_version = get_plugin_data( SBP_PATH . $this->plugin_name )['Version'];
				if ( version_compare( $plugin_version, $attributes['min-version'], '<' ) ) {
					return false;
				}
			}

			// Check Max Version
			if ( isset( $attributes['max-version'] ) ) {
				// LAHMACUNTODO: WP_CONTENT_DIR yerine SBP_PATH kullandım.
				// LAHMACUNTODO: $this->plugin_name eklenti dosya ismini değil, eklenti ismini veriyor (ama bunu düzeltmek için hardcode ile speed-booster-pack.php yazmamalıyız)
				$plugin_version = get_plugin_data( SBP_PATH . $this->plugin_name )['Version'];
				if ( version_compare( $plugin_version, $attributes['max-version'], '>' ) ) {
					return false;
				}
			}

			return true;
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function settings_page() {
			if ( empty ( $GLOBALS['admin_page_hooks']['a4wp_options_page'] ) ) {
				add_menu_page(
					'Announce 4 WP Settings',
					'A4WP Settings',
					'administrator',
					'a4wp_options_page',
					[ $this, 'settings_page_template' ]
				);
			}
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function settings_page_template() {
			echo '<h1>' . __( 'A4WP Settings', 'announce4wp' ) . '</h1>';
			echo '<div class="wrap">';
			echo '<p>' . __( 'You can choose which plugins can show you notices.', 'announce4wp' ) . '</p>';
			echo '<form action="" method="POST">';
			$plugins          = get_option( 'a4wp_plugins' );
			$disabled_plugins = get_option( 'a4wp_disabled_plugins' );
			foreach ( $plugins as $plugin ) {
				$is_checked = true;
				if ( in_array( $plugin, $disabled_plugins ) ) {
					$is_checked = false;
				}
				echo '<div class="form-group">';
				echo '<label>';
				echo '<input type="checkbox" name="a4wp_allowed_plugins[]" value="' . $plugin . '" ' . ( $is_checked ? 'checked' : null ) . ' />';
				echo get_plugin_data( SBP_PATH . $plugin )['Name'];
				echo '</label>';
				echo '</div>';
			}
			echo get_submit_button( null, 'primary', 'a4wp_settings_page' );
			echo '</form>';
			echo '</div>';
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function save_plugin_names() {
			if ( $plugins = get_option( 'a4wp_plugins' ) ) {
				$plugins_list = array_merge( $plugins, [ $this->plugin_name ] );
				update_option( 'a4wp_plugins', array_unique( $plugins_list ) );
			} else {
				add_option( 'a4wp_plugins', [ $this->plugin_name ] );
			}
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function check_if_disabled() {
			$disabled_plugins = get_option( 'a4wp_disabled_plugins' );
			if ( is_array( $disabled_plugins ) && in_array( $this->plugin_name, $disabled_plugins ) ) {
				$this->is_allowed = false;
			}
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function handle_options_page_form() {
			if ( isset( $_POST['a4wp_settings_page'] ) ) {
				$plugins          = get_option( 'a4wp_plugins' );
				$allowed_plugins  = $_POST['a4wp_allowed_plugins'];
				$disabled_plugins = [];
				foreach ( $plugins as $key => $plugin ) {
					if ( ! in_array( $plugin, $allowed_plugins ) ) {
						$disabled_plugins[] = $plugin;
					}
				}
				update_option( 'a4wp_disabled_plugins', $disabled_plugins );
				wp_redirect( admin_url( 'admin.php?page=a4wp_options_page' ) );
			}
		}

		// LAHMACUNTODO: doktor bu ne? :D
		public function admin_init() {
			$this->handle_options_page_form();
			$this->save_plugin_names();
			$this->save_notices();
		}
	}
}