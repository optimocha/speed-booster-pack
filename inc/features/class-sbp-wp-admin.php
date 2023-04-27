<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Admin {
	public function __construct() {

		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );

		if ( ! is_admin() ) { return; }

		add_action( 'admin_init', [ $this, 'set_notices' ] );
		add_action( 'admin_init', [ $this, 'timed_notifications' ] );
		add_action( 'admin_head', [ $this, 'check_required_file_permissions' ] );
		add_action( 'admin_init', [ $this, 'upgrade_php_notice' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_meta_links' ], 10, 2 );
		add_filter( 'plugin_action_links_' . SBP_PLUGIN_BASENAME, [ $this, 'settings_links' ], 10, 2 );

	}

	public function plugin_meta_links( $meta_fields, $file ) {

		if ( SBP_PLUGIN_BASENAME == $file ) {

			$report_a_bug_url = "https://optimocha.com/contact/?subject=Speed%20Booster%20Pack%20bug%20report";
			$report_a_bug_text = __( 'Report a bug', 'speed-booster-pack' );

			$pro_services_url = "https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post";
			$pro_services_text = __( 'Pro Services', 'speed-booster-pack' );

			$rate_us_url = "https://wordpress.org/support/plugin/speed-booster-pack/reviews/?rate=5#new-post";
			$rate_us_text = __( 'Rate Us', 'speed-booster-pack' );
			$star_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"><path d="m12 2 3.1 6.3 6.9 1-5 4.8 1.2 6.9-6.2-3.2L5.8 21 7 14.1 2 9.3l6.9-1L12 2z"/></svg>';

			$meta_fields[] =  "<a href='$report_a_bug_url' target='_blank' title='$report_a_bug_text'>$report_a_bug_text</a> | <a href='$pro_services_url' target='_blank' title='$pro_services_text' style='font-weight:bold;'>$pro_services_text</a> | $rate_us_text: <a href='$rate_us_url' target='_blank' title='$rate_us_text'><i class='sbp-stars' style='position: relative; top: 3px;'>$star_svg$star_svg$star_svg$star_svg$star_svg</i></a>";

		}

		return $meta_fields;

	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {

		if ( current_user_can( 'manage_options' ) ) {

			$admin_bar->add_node( [
				'id'    => 'speed_booster_pack',
				'title' => 'Speed Booster',
				'href'  => admin_url( 'admin.php?page=sbp-settings' ),
				'meta'  => [
					'target' => '_self',
					'html'   => '<style>#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item{background:url("' . SBP_URL . 'admin/images/icon.svg?ver=' . SPEED_BOOSTER_PACK['version'] . '") no-repeat 5px center;padding-left:25px;}#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item:hover{color:white;}</style>',
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
			'flash' );

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
			'flash' );

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

		if ( is_array( $links ) ) {
			$links['settings'] = '<a href="' . admin_url( 'admin.php?page=sbp-settings' ) . '">' .  __( 'Settings', 'speed-booster-pack' ) . '</a>';
		}

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

	public function upgrade_php_notice() {

		if ( version_compare( phpversion(), '7.0', '>=' ) ) { return; }

		SBP_Notice_Manager::display_notice( 'upgrade_php_notice', '<p><strong>' . SBP_PLUGIN_NAME . '</strong>: ' .  __( 'You are using a really old PHP version! In a few months, Speed Booster Pack will stop working with PHP versions below 7.0, so we highly recommend you update PHP to the latest version (or ask your hosting company to do it).', 'speed-booster-pack' ) . '</p>', 'warning', true );

	}

}