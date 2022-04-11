<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Admin {
	public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'set_notices' ] );

			add_action( 'admin_init', [ $this, 'timed_notifications' ] );
			add_action( 'admin_init', [ $this, 'welcome_notice' ] );
			add_action( 'admin_init', [ $this, 'clear_custom_code_manager' ] );
			add_action( 'admin_head', [ $this, 'check_required_file_permissions' ] );

			add_action( 'wp_ajax_sbp_dismiss_intro', [ $this, 'dismiss_intro' ] );
			add_action( 'wp_ajax_sbp_dismiss_ccm_backup', [ $this, 'dismiss_custom_code_manager_backup' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_deactivation_survey_scripts' ] );
		}

		add_filter( 'plugin_row_meta', [ $this, 'plugin_meta_links' ], 10, 2 );
		add_filter( 'plugin_action_links_' . SBP_PLUGIN_BASENAME, [ $this, 'settings_links' ], 10, 2 );
	}

	public function plugin_meta_links( $meta_fields, $file ) {
		if ( SBP_PLUGIN_BASENAME == $file ) {
			$plugin_url    = "https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post";
			$meta_fields[] = "<a href='" . esc_url( $plugin_url ) . "' target='_blank' title='" . esc_html__( 'Rate Us',
					'speed-booster-pack' ) . "'>
            <i class='sbp-stars' style='position: relative; top: 3px;'>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
			                 . "</i></a>";
		}

		return $meta_fields;
	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {

			$admin_bar->add_menu( [
				'id'    => 'speed_booster_pack',
				'title' => 'Speed Booster',
				'href'  => admin_url( 'admin.php?page=sbp-settings' ),
				'meta'  => [
					'target' => '_self',
					'html'   => '<style>#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item{background:url("' . SBP_URL . 'admin/images/icon.svg") no-repeat 5px center;padding-left:25px;filter: brightness(0.7) sepia(1) hue-rotate(50deg) saturate(1.5);}#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item:hover{color:white;}</style>',
				],
			] );

			if ( sbp_get_option( 'module_caching' ) && ! sbp_should_disable_feature( 'caching' ) ) {
				// Cache clear
				$clear_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cache' ),
					'sbp_clear_total_cache',
					'sbp_nonce' );
				$sbp_admin_menu  = [
					'id'     => 'sbp_clear_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cache', 'speed-booster-pack' ),
					'href'   => $clear_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );

				// Cache warmup
				$warmup_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_warmup_cache' ),
					'sbp_warmup_cache',
					'sbp_nonce' );
				$sbp_admin_menu   = [
					'id'     => 'sbp_warmup_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Warmup Cache', 'speed-booster-pack' ),
					'href'   => $warmup_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'localize_tracking_scripts' ) ) {
				$clear_tracking_scripts_url = wp_nonce_url( add_query_arg( 'sbp_action',
					'sbp_clear_localized_analytics' ),
					'sbp_clear_localized_analytics',
					'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_localized_scripts',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Localized Scripts', 'speed-booster-pack' ),
					'href'   => $clear_tracking_scripts_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( SBP_Cloudflare::is_cloudflare_active() ) {
				$clear_cloudflare_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cloudflare_cache' ),
					'sbp_clear_cloudflare_cache',
					'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_cloudflare_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cloudflare Cache', 'speed-booster-pack' ),
					'href'   => $clear_cloudflare_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'sucuri_enable' ) ) {
				$clear_sucuri_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_sucuri_cache' ),
					'sbp_clear_sucuri_cache',
					'sbp_nonce' );
				$sbp_admin_menu         = [
					'id'     => 'sbp_clear_sucuri_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Sucuri Cache', 'speed-booster-pack' ),
					'href'   => $clear_sucuri_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}
		}
	}

	public function set_notices() {
		// Set Sucuri Notice
		if ( $transient_value = get_transient( 'sbp_clear_sucuri_cache' ) ) {
			$notice_message = $transient_value == '1' ? __( 'Sucuri cache cleared.', 'speed-booster-pack' ) : __( 'Error occured while clearing Sucuri cache. ', 'speed-booster-pack' ) . get_transient( 'sbp_sucuri_error' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_clear_sucuri_cache',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . $notice_message . '</p>',
				$notice_type,
				true,
				'flash' );
		}

		// Set Cloudflare Notice
		$cf_transient_value = get_transient( 'sbp_notice_cloudflare' );
		if ( $cf_transient_value == 1 ) {
			$notice_message = __( 'Cloudflare cache cleared.', 'speed-booster-pack' );
			$notice_type    = 'success';
		} elseif ( $cf_transient_value == 2 ) {
			$notice_message = __( 'Error occured while clearing Cloudflare cache. Possible reason: Credentials invalid.', 'speed-booster-pack' );
			$notice_type    = 'error';
		} else {
			$notice_message = '';
			$notice_type    = '';
		}
		SBP_Notice_Manager::display_notice( 'sbp_notice_cloudflare',
			'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . $notice_message . '</p>',
			$notice_type,
			true,
			'flash' );

		// Set Cache Clear Notice
		SBP_Notice_Manager::display_notice( 'sbp_notice_cache',
			'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache cleared.', 'speed-booster-pack' ) . '</p>',
			'success',
			true,
			'recurrent' );

		// Set Localizer Cache Clear Notice
		if ( get_transient( 'sbp_notice_tracker_localizer' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_notice_tracker_localizer',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Localized scripts are cleared.', 'speed-booster-pack' ) . '</p>',
				'success',
				true,
				'flash' );
		}

		// Advanced Cache File Error
		if ( get_transient( 'sbp_advanced_cache_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_advanced_cache_error',
				/* translators: %s: wp-content/advanced-cache.php */
				'<p><strong>' . SBP_PLUGIN_NAME . '</strong>: ' . sprintf( __( '%s is not writable. Please check your file permissions, or some features might not work.', 'speed-booster-pack' ), '<code>wp-content/advanced-cache.php</code>' ) . '</p>',
				'error',
				true,
				'recurrent' );
		}

		// WP-Config File Error
		if ( get_transient( 'sbp_wp_config_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_wp_config_error',
				/* translators: %s: wp-config.php */
				'<p><strong>' . SBP_PLUGIN_NAME . '</strong>: ' . sprintf( __( '%s is not writable. Please check your file permissions, or some features might not work.', 'speed-booster-pack' ), '<code>wp-config.php</code>' ) . '</p>',
				'error',
				true,
				'recurrent' );
		}

		// Warmup Started Notice
		SBP_Notice_Manager::display_notice( 'sbp_warmup_started',
			'<p>' . sprintf( __( '%s will now send requests to your homepage and all the pages that are linked to in the homepage (including links in navigation menus) so they\'ll all be cached.', 'speed-booster-pack' ), SBP_PLUGIN_NAME ) . '</p>',
			'info',
			true,
			'recurrent' );
	}

	public function timed_notifications() {
		$notices    = [
			'sbp_rate_wp_org' => [
				'show_after' => '+7 days',
				'text'       => '<b>' . SBP_PLUGIN_NAME . ':</b> ' . sprintf( __( 'If you like our plugin, we would be so grateful if you could %1$sgive us a fair rating on wordpress.org%2$s.', 'speed-booster-pack' ), '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post" rel="noopener" target="_blank">', '</a>' ),
			],
		];

		foreach ( $notices as $notice_key => $notice ) {
			if ( current_user_can( 'manage_options' ) ) {
				$meta_key    = $notice_key . '_notice_display_time';
				$notice_meta = get_user_meta( get_current_user_id(), $meta_key, true );
				if ( ! $notice_meta ) {
					if ( isset( $notice['depends_on'] ) && $notice['depends_on'] ) {
						if ( SBP_Notice_Manager::has_dismissed( $notice['depends_on'] ) ) {
							update_user_meta( get_current_user_id(), $meta_key, strtotime( $notice['show_after'] ) );
						}
					} else {
						update_user_meta( get_current_user_id(), $meta_key, strtotime( $notice['show_after'] ) );
					}
				} else {
					if ( $notice_meta <= time() ) {
						SBP_Notice_Manager::display_notice( $notice_key, '<p>' . $notice['text'] . '</p>', 'info', true, 'one_time', 'toplevel_page_sbp-settings' );
					}
				}
			}
		}
	}

	public function settings_links( $links ) {
		$pro_link = ' <a href="https://optimocha.com/?ref=speed-booster-pack" target="_blank">Pro Services</a > ';
		array_unshift( $links, $pro_link );

		return $links;
	}

	public function check_required_file_permissions() {
		if ( get_current_screen()->id !== 'toplevel_page_sbp-settings' ) {
			return;
		}

		$permission_errors = [];

		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_path = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		$upload_dir          = wp_upload_dir()['basedir'];
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';

		$check_list = [
			'WordPress root directory'           => ABSPATH,
			'wp-content directory'               => WP_CONTENT_DIR,
			'WordPress uploads directory'        => $upload_dir,
			'SBP uploads directory'              => SBP_UPLOADS_DIR,
			'wp-config.php file'                 => $wp_config_path,
			'wp-content/advanced-cache.php file' => $advanced_cache_path,
		];

		/** @var \WP_Filesystem_Base $wp_filesystem */
		$wp_filesystem = sbp_get_filesystem();

		foreach ( $check_list as $key => $item ) {
			if ( $wp_filesystem->exists( $item ) ) {
				if ( ! sbp_check_file_permissions( $item ) ) {
					$permission_errors[ $key ] = $item;
				}
			}
		}

		if ( count( $permission_errors ) ) {
			$notice_content = '<p>';
			$notice_content .= __( sprintf( '%s needs write permissions for the following files/directories to work properly:', SBP_PLUGIN_NAME ), 'speed-booster' );
			$notice_content .= '<ul>';
			foreach ( $permission_errors as $key => $error ) {
				$notice_content .= '<li>' . $key . ' (' . $error . ')</li>';
			}
			$notice_content .= '</ul>';
			$notice_content .= '<a href="https://www.wpbeginner.com/beginners-guide/how-to-fix-file-and-folder-permissions-error-in-wordpress/" target="_blank">' . __( 'Here\'s a tutorial on how to change file/directory permissions.', 'speed-booster' ) . '</a>';
			$notice_content .= '</p>';

			SBP_Notice_Manager::display_notice( 'permission_errors', $notice_content, 'warning', false, 'recurrent', 'toplevel_page_sbp-settings' );
		}
	}

	public function welcome_notice() {
		SBP_Notice_Manager::display_notice( 'welcome_notice', sprintf( '<p>' . __( 'Thank you for installing %1$s! You can now visit the %2$ssettings page%3$s to start speeding up your website.', 'speed-booster-pack' ) . '</p>', SBP_PLUGIN_NAME, '<a href="' . admin_url( 'admin.php?page=sbp-settings&dismiss_welcome_notice=true' ) . '">', '</a>' ), 'success', true, 'one_time', 'plugins' );

		if ( isset( $_GET['dismiss_welcome_notice'] ) && $_GET['dismiss_welcome_notice'] == true ) {
			SBP_Notice_Manager::dismiss_notice( 'welcome_notice' );
		}
	}

	public function dismiss_intro() {
		update_user_meta( get_current_user_id(), 'sbp_intro', true );
	}

	public function enqueue_deactivation_survey_scripts() {
		if ( get_current_screen()->id === 'plugins' ) {
			wp_enqueue_script( 'sbp_deactivation_survey', SBP_URL . '/admin/js/deactivation-survey.js', array(
				'jquery'
			), SBP_VERSION );

			wp_enqueue_style( 'sbp_deactivation_survey', SBP_URL . '/admin/css/deactivation-survey.css', null, SBP_VERSION );

			add_action( 'admin_footer', [ $this, 'deactivation_survey_modal' ] );
		}
	}

	public function deactivation_survey_modal() {
		$current_user = wp_get_current_user();
		
		$email = (string) $current_user->user_email;

		echo '
		<div class="sbp-deactivation-survey">
			<div class="sbp-survey-inner">
				<h3>' . __( 'Sorry to see you go!', 'speed-booster-pack' ) . '</h3>
				<h4>' . sprintf( __( 'We would appreciate if you let us know why you\'re deactivating %s.', 'speed-booster-pack' ), SBP_PLUGIN_NAME ) . '</h4>
				<form action="" method="POST">
					<label>
						<input type="radio" name="sbp_reason" value="I don\'t see any performance improvement." />
						' . __( 'I don\'t see any performance improvement.', 'speed-booster-pack' ) . '
					</label>
					<label>
						<input type="radio" name="sbp_reason" value="It broke my site." />
						' . __( 'It broke my site.', 'speed-booster-pack' ) . '
					</label>
					<label>
						<input type="radio" name="sbp_reason" value="I found a better solution." />
						' . __( 'I found a better solution.', 'speed-booster-pack' ) . '
					</label>
					<label>
						<input type="radio" name="sbp_reason" value="I\'m just disabling temporarily." />
						' . __( 'I\'m just disabling temporarily.', 'speed-booster-pack' ) . '
					</label>
					<label>
						<input type="radio" name="sbp_reason" value="Other." />
						' . __( 'Other (please specify below)', 'speed-booster-pack' ) . '
					</label>
					<label>
						<textarea name="sbp_deactivation_description" class="widefat" style="display: none;"></textarea>
					</label>
					<input type="hidden" name="sbp_site_url" value="' . site_url() . '" />
					<input type="hidden" name="sbp_version" value="' . SBP_VERSION . '" />
					<hr>
					<label>
						<input type="checkbox" name="sbp_reply" />
						' . __( 'I would like to get a response to my submission.', 'speed-booster-pack' ) . '
					</label>
					<label>
						<input name="sbp_reply_email" type="email" class="widefat" value="' . $email . '" style="padding: 3px 5px; display: none;" />
					</label>
					<div class="sbp-deactivate-buttons-wrapper">
						<button class="button button-primary submit-and-deactivate" disabled="disabled">' . __( 'Submit & Deactivate', 'speed-booster-pack' ) . '</button>
						<button class="button button-secondary deactivate-plugin" type="button">' . __( 'Just Deactivate', 'speed-booster-pack' ) . '</button>
						<button class="button button-secondary cancel-deactivation-survey" type="button">' . __( 'Cancel', 'speed-booster-pack' ) . '</button>
					</div>
				</form>
			</div>
		</div>
		';
	}

	public function clear_custom_code_manager() {

		$custom_code_manager_original = sbp_get_option( 'custom_codes', []);
		if( sbp_get_option( 'custom_codes', []) ) {

			$custom_code_manager_backup = '';

			for( $i = 0; $i < count( $custom_code_manager_original ); $i++ ) {
			    $custom_code_manager_backup .= '<!-- Custom code #' . $i . ' (' . $custom_code_manager_original[$i]['custom_codes_place'] . ') -->' . PHP_EOL;
			    $custom_code_manager_backup .= '<script>' . PHP_EOL;
			    $custom_code_manager_backup .= $custom_code_manager_original[$i]['custom_codes_item'] . PHP_EOL;
			    $custom_code_manager_backup .= '</script>' . PHP_EOL . PHP_EOL;
			}

			update_option( 'sbp_custom_code_manager_backup', $custom_code_manager_backup );

			$sbp_options = get_option( 'sbp_options' );

			if ( $sbp_options ) {
				unset( $sbp_options['custom_codes'] );
				update_option( 'sbp_options', $sbp_options );
			}

		}

		if( ! get_option( 'sbp_custom_code_manager_backup' ) ) { return; }

		SBP_Notice_Manager::display_notice(
		'custom_code_manager_backup',
		'<p>' . __( 'Speed Booster Pack: We have removed the Custom Code Manager feature from our plugin because it\'s not totally related to performance. Since you were using this feature, here\'s a backup of your custom codes:', 'speed-booster-pack' ) . '</p>' .
		    '<textarea style="max-width: 100%; width: 600px; min-height: 150px;" readonly>' . get_option( 'sbp_custom_code_manager_backup' ) . '</textarea>' .
		    '<p>' . sprintf( __( 'You can use any plugin you want to add these custom codes (%s is a decent alternative). Better yet, you can use your theme if it has a custom code feature.', 'speed-booster-pack' ), '<a href="https://wordpress.org/plugins/insert-headers-and-footers/" target="_blank" rel="external nofollow">Insert Headers and Footers</a>' ) . '</p>' .
		    '<p><button class="button button-primary sbp-dismiss-ccm-notice notice-dismiss-button" data-notice-id="custom_code_manager_backup" data-notice-action="sbp_dismiss_notice">' . __( 'I copied the code, dismiss this notice', 'speed-booster-pack' ) . '</button></p>',
		'warning',
		false
		);

	}

	public function dismiss_custom_code_manager_backup() {

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['action'] ) || ! $_GET['action'] === 'sbp_dismiss_ccm_backup' ) { return; }

		if ( ! wp_verify_nonce( $_GET['nonce'], 'sbp_ajax_nonce' ) ) {
			echo wp_json_encode( [
				'status'  => 'failure',
				'message' => __( 'Invalid nonce.', 'speed-booster-pack' ),
			] );
			wp_die();
		}

		delete_option( 'sbp_custom_code_manager_backup' );

		echo wp_json_encode( [ 'status' => 'success', 'message' => 'Custom codes successfully removed.' ] );
		wp_die();

	}

}