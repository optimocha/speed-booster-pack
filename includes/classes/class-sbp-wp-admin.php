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
			require_once SBP_LIB_PATH . 'announce4wp/announce4wp-client.php';
			add_action( 'admin_init', [ $this, 'set_notices' ] );
			$this->initialize_announce4wp();

			add_action( 'admin_init', [ $this, 'timed_notifications' ] );
			add_action( 'admin_init', [ $this, 'check_pagespeed_tricker' ] );
			add_action( 'admin_head', [ $this, 'check_required_file_permissions' ] );
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
			$notice_message = $transient_value == '1' ? __( 'Sucuri cache cleared.',
				'speed-booster-pack' ) : __( 'Error occured while clearing Sucuri cache. ',
					'speed-booster-pack' ) . get_transient( 'sbp_sucuri_error' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_clear_sucuri_cache',
				'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>',
				$notice_type,
				true,
				'flash' );
		}

		// Set Cloudflare Notice
		$cf_transient_value = get_transient( 'sbp_notice_cloudflare' );
		if ($cf_transient_value == 1) {
			$notice_message = __( 'Cloudflare cache cleared.', 'speed-booster-pack' );
			$notice_type    = 'success';
		} else if ($cf_transient_value == 2) {
			$notice_message = __( 'Error occured while clearing Cloudflare cache. Possible reason: Credentials invalid.', 'speed-booster-pack' );
			$notice_type    = 'error';
		} else {
			$notice_message = '';
			$notice_type    = '';
		}
		SBP_Notice_Manager::display_notice( 'sbp_notice_cloudflare',
			'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>',
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
			'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache warmup started.', 'speed-booster-pack' ) . '</p>',
			'info',
			true,
			'recurrent' );

		// Warmup Completed Notice
		SBP_Notice_Manager::display_notice( 'sbp_warmup_completed',
			'<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache warmup completed.', 'speed-booster-pack' ) . '</p>',
			'success',
			true,
			'recurrent' );

		// WP-Config File Error
		if ( get_transient( 'sbp_warmup_errors' ) ) {
			$list   = '';
			$errors = get_transient( 'sbp_warmup_errors' );
			if ( is_array( $errors ) ) {
				foreach ( $errors as $error ) {
					$extras = [];
					if ( isset( $error['options']['user-agent'] ) && $error['options']['user-agent'] === 'Mobile' ) {
						$extras[] = '(Mobile)';
					}
					$list .= '<li><a href="' . $error['url'] . '" target="_blank">' . $error['url'] . ' ' . implode( ' ',
							$extras ) . '</a></li>';
				}
				SBP_Notice_Manager::display_notice( 'sbp_warmup_errors',
					'<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Cache warmup completed but following pages may not be cached. Please check this pages are available. (Hover over this notice to see all errors)',
						'speed-booster-pack' ) . '</p><ul class="warmup-cache-error-list">' . $list . '</ul>',
					'error',
					true,
					'recurrent' );
			}
		}
	}

	public function timed_notifications() {
		$tweet_link = "https://twitter.com/intent/tweet?hashtags=SpeedBoosterPack&text=I've been using Speed Booster Pack for a couple of weeks and I love it!&tw_p=tweetbutton&url=https://wordpress.org/plugins/speed-booster-pack/";
		$notices    = [
			'sbp_tweet'       => [
				'show_after' => '+7 days',
				'text'       => '<b>' . SBP_PLUGIN_NAME . ':</b> ' . sprintf( __( 'If you\'re enjoying using our plugin, can you %1$ssend a tweet to support us%2$s?', 'speed-booster-pack' ), '<a href="' . $tweet_link . '" rel="noopener" target="_blank">', '</a>' ),
				'depends_on' => 'sbp_rate_wp_org',
			],
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

	private function initialize_announce4wp() {
		if ( sbp_get_option( 'enable_external_notices' ) ) {
			new \Announce4WP_Client( 'speed-booster-pack.php',
				SBP_PLUGIN_NAME,
				"sbp",
				"https://speedboosterpack.com/wp-json/a4wp/v1/" . SBP_VERSION . "/news.json",
				"toplevel_page_sbp-settings" );
		}
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

		$upload_dir = wp_upload_dir()['basedir'];
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';

		$check_list = [
			'WordPress root directory' => ABSPATH,
			'wp-content directory' => WP_CONTENT_DIR,
			'WordPress uploads directory' => $upload_dir,
			'SBP uploads directory' => SBP_UPLOADS_DIR,
			'wp-config.php file' => $wp_config_path,
			'wp-content/advanced-cache.php file' => $advanced_cache_path,
		];

		/** @var \WP_Filesystem_Base $wp_filesystem */
		$wp_filesystem = sbp_get_filesystem();

		foreach ( $check_list as $key => $item ) {
			if ( $wp_filesystem->exists( $item ) ) {
				if ( ! sbp_check_file_permissions( $item ) ) {
					$permission_errors[$key] = $item;
				}
			}
		}

		if ( count($permission_errors) ) {
			$notice_content = '<p>';
			$notice_content .= __( sprintf( '%s needs write permissions for the following files/directories to work properly:', SBP_PLUGIN_NAME ), 'speed-booster' );
			$notice_content .= '<ul>';
			foreach ( $permission_errors as $key => $error ) {
				$notice_content .= '<li>' . $key . ' (' . $error . ')</li>';
			}
			$notice_content .= '</ul>';
			$notice_content .= '<a href="https://www.wpbeginner.com/beginners-guide/how-to-fix-file-and-folder-permissions-error-in-wordpress/" target="_blank">' . __( 'Here\'s a tutorial on how to change file/directory permissions.', 'speed-booster' ) . '</a>';
			$notice_content .= '</p>';

			SBP_Notice_Manager::display_notice('permission_errors', $notice_content, 'warning', false, 'recurrent', 'toplevel_page_sbp-settings');
		}
	}

	public function check_pagespeed_tricker() {
		if ( sbp_get_option( 'pagespeed_tricker' ) ) {
			SBP_Notice_Manager::display_notice( 'pagespeed_tricker_active', '<p>' . sprintf( __( '%1$s\'s experimental feature, %2$s, is enabled. You will get 100%% PageSpeed Insights scores in all PageSpeed tests, but SEO rankings are calculated by Real User Monitoring (RUM) in all search engines. %2$s only proves that it\'s easy to manipulate PageSpeed Insights and nothing else. Please don\'t keep this feature enabled on production websites!', 'speed-booster-pack' ) . '</p>', SBP_PLUGIN_NAME, 'PageSpeed Tricker' ), 'info', false );
		}
	}
}