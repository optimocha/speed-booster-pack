<?php

if ( ! class_exists( "Announce4WP_Client" ) ) {
	class Announce4WP_Client {
		private $api_endpoint_url = '';
		private $service_id = '';
		private $transient_name = '';
		private $error_transient_name = '';
		private $settings_screen = '';
		private $plugin_name = '';
		private $plugin_file_name = '';

		public function __construct( $plugin_file_name, $plugin_name, $service_id, $api_endpoint_url, $settings_screen ) {
			$this->service_id           = $service_id;
			$this->api_endpoint_url     = $api_endpoint_url;
			$this->settings_screen      = $settings_screen;
			$this->plugin_file_name     = $plugin_file_name;
			$this->plugin_name          = $plugin_name;
			$this->transient_name       = 'a4wp_' . $this->service_id . '_announcements';
			$this->error_transient_name = $this->transient_name . '_disabled';

			add_action( 'admin_init', [ $this, 'save_notices' ] );

			// Enqueue Dismiss Script
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Admin Notices
			add_action( 'admin_notices', [ $this, 'display_notices' ] );

			// Dismiss Notice Action
			add_action( 'wp_ajax_a4wp_dismiss_notice', [ $this, 'dismiss_notice' ] );
		}

		public function save_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( get_transient( $this->transient_name ) || get_transient( $this->error_transient_name ) ) {
				return;
			}

			$remote_notices = $this->fetch_notices();
			if ( ! $remote_notices ) {
				$error_timestamps = [ time() ];
				if ( get_option( 'sbp_notice_error' ) ) {
					$error_timestamps   = get_option( 'sbp_notice_error' );
					$error_timestamps[] = time();
				}

				update_option( 'sbp_notice_error', $error_timestamps );
				set_transient( $this->error_transient_name, 1, HOUR_IN_SECONDS );

				if ( count( $error_timestamps ) >= 10 ) {
					$sbp_options                            = get_option( 'sbp_options' );
					$sbp_options['enable_external_notices'] = false;
					update_option( 'sbp_options', $sbp_options );
					delete_transient( $this->error_transient_name );
				}


				return;
			}

			// Update transient
			set_transient( $this->transient_name, $remote_notices, 48 * HOUR_IN_SECONDS );
		}

		private function fetch_notices() {
			$notices = wp_remote_get( $this->api_endpoint_url );
			if ( $notices instanceof WP_Error ) {
				return false;
			}

			if ( $notices = json_decode( $notices['body'], true ) ) {
				delete_transient( $this->error_transient_name );

				return $notices;
			}

			return false;
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
				$last_ids                        = get_user_meta( get_current_user_id(), $this->service_id . '_dismissed_notices', true );
				$last_ids                        = $last_ids == '' ? [] : $last_ids;
				$last_ids[ $this->service_id ][] = $id;
				$last_ids[ $this->service_id ]   = array_unique( $last_ids[ $this->service_id ] );
				update_user_meta( get_current_user_id(), $this->service_id . '_dismissed_notices', $last_ids );
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

		public function display_notices() {
			$announcements = get_transient( $this->transient_name );
			if ( is_array( $announcements ) && isset( $announcements['normal_notices'] ) ) {
				foreach ( $announcements['normal_notices'] as $notice ) {
					$attributes = $this->parse_attributes( $notice['rules'] );
					$this->print_notice( $attributes, $notice );
				}
			}

			if ( is_array( $announcements ) && isset( $announcements["important_notices"] ) ) {
				foreach ( $announcements["important_notices"] as $notice ) {
					$attributes = $this->parse_attributes( $notice['rules'] );
					$this->print_notice( $attributes, $notice, true );
				}
			}
		}

		private function print_notice( $attributes, $notice, $is_important = false ) {
			$type = isset( $attributes['type'] ) ? $attributes['type'] : 'notice-info';
			if ( true === $is_important ) {
				$should_display = $this->should_display( $attributes );
			} else {
				$should_display = $this->should_display( $attributes, $notice );
			}
			if ( $should_display ) {
				echo '<div class="notice a4wp-notice ' . $type . ' ' . ( ! $is_important ? 'is-dismissible' : null ) . '" data-service-id="' . $this->service_id . '" data-notice-id="' . $notice['id'] . '">';
				echo ( $notice['title'] ) ? '<p style="font-size:120%;font-weight:700;">' . $notice['title'] . '</p>' : null;
				echo ( $notice['content'] ) ? '<p>' . $notice['content'] . '</p>' : null;
				echo '</div>';
			}
		}

		/**
		 * @param $attributes
		 * @param null $notice required for notices
		 *
		 * @return bool
		 */
		private function should_display( $attributes, $notice = null ) {
			// Check Page
			$page = isset( $attributes['page'] ) ? $attributes['page'] : $this->settings_screen;
			if ( $page != "all" && $page != get_current_screen()->id ) {
				return false;
			}

			if ( null !== $notice ) {
				$dismissed_ids = get_user_meta( get_current_user_id(), $this->service_id . '_dismissed_notices', true );
				if ( is_array( $dismissed_ids ) ) {
					if ( isset( $dismissed_ids[ $this->service_id ] ) && in_array( $notice['id'], $dismissed_ids[ $this->service_id ] ) ) {
						return false;
					}
				}
			}

			return true;
		}
	}
}