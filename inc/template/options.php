<?php

// handle closed postboxes
$user_id     = get_current_user_id();
$option_name = 'closedpostboxes_' . 'toplevel_page_sbp-options'; // use the "pagehook" ID
$option_arr  = get_user_option( $option_name, $user_id ); // get the options for that page


if ( is_array( $option_arr ) && in_array( 'exclude-from-footer', $option_arr ) ) {
	$closed = true;
}


if ( is_array( $option_arr ) && in_array( 'defer-from-footer', $option_arr ) ) {
	$closed_defer = true;
}

?>

<div class="wrap about-wrap">
	<div class="sb-pack">

		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<div class="about-text">
			<?php
			/* Translators: Welcome Screen Description. */
			echo esc_html__( 'Speed Booster Pack is a lightweight, frequently updated, easy to use and well supported plugin which allows you to improve your website’s loading speed. Visitors usually close a website if it doesn’t load in a few seconds and the slower a site loads the greater the chances are that the visitors will leave. And you don’t want that to happen, do you?', 'speed-booster-pack' );
			?>
		</div>
		<div class="wp-badge sbp-welcome-logo"></div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a class="nav-tab" href="#general-options"><?php esc_html_e( 'General', 'speed-booster-pack' ); ?></a>
			<a class="nav-tab" href="#advanced-options"><?php esc_html_e( 'Advanced', 'speed-booster-pack' ); ?></a>
			<a class="nav-tab" href="#cdn-options"><?php esc_html_e( 'CDN', 'speed-booster-pack' ); ?></a>
			<a class="nav-tab" href="#google-analytics"><?php esc_html_e( 'Google Analytics', 'speed-booster-pack' ); ?></a>
			<a class="nav-tab" href="#optimize-more"><?php esc_html_e( 'Optimize More', 'speed-booster-pack' ); ?></a>
		</h2>

		<form method="post" action="options.php">

			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			<?php settings_fields( 'speed_booster_settings_group' ); ?>

			<?php

			$sbp_options_array = array(
				//general options panel
				'general-options'  => array(
					//General section
					'sections' => array(
						array(
							'type'  => 'section',
							'label' => __( 'General', 'speed-booster-pack' ),
							'items' => array(
								'jquery_to_footer' => array(
									'type'          => 'checkbox',
									'label'         => __( 'Move scripts to footer', 'speed-booster-pack' ),
									'tooltip'       => __( 'This option move all scripts to the footer while keeping stylesheets in the header to improve page loading speed and get a higher score on the major speed testing sites such as GTmetrix or other website speed testing tools', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'use_google_libs'  => array(
									'type'          => 'checkbox',
									'label'         => __( 'Load JS from Google Libraries', 'speed-booster-pack' ),
									'tooltip'       => __( 'Loading WordPress javascript files from Google’s Libraries rather than serving it from your WordPress install directly, will reduce latency, increase parallelism and improve caching.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'minify_html_js'   => array(
									'type'          => 'checkbox',
									'label'         => __( 'Minify HTML', 'speed-booster-pack' ),
									'tooltip'       => __( 'Activate this option only if you don’t want to use other minify plugins or other speed optimization plugin that has minify option included. If something goes wrong, simply uncheck this option and save the settings.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'defer_parsing'    => array(
									'type'          => 'checkbox',
									'label'         => __( 'Defer parsing of javascript files', 'speed-booster-pack' ),
									'tooltip'       => __( '!!!Note: This will be disabled IF Move Scripts to Footer is enabled. By deferring parsing of unneeded JavaScript until it needs to be executed, you can reduce the initial load time of your page. Please note that this option will not defer the main WordPress jQuery script if Load JS from Google Libraries option is not checked.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'query_strings'    => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove query strings', 'speed-booster-pack' ),
									'tooltip'       => __( 'Since most proxies do not cache resources with a ? in their URL, this option allows you to remove any query strings (version numbers) from static resources like CSS & JS files, thus improving your speed scores in services like GTmetrix, PageSpeed, YSlow and Pingdoom.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'font_awesome'     => array(
									'type'          => 'checkbox',
									'label'         => __( 'Removes extra Font Awesome styles', 'speed-booster-pack' ),
									'tooltip'       => __( 'Use this option only if your theme uses Font Awesome, to prevent other plugins that uses Font Awesome, to add their stylesheets to your theme. In other words, this option removes extra Font Awesome stylesheets added to your theme by certain plugins.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
							),
						),
						//more settings section
						array(
							'type'  => 'section',
							'label' => __( 'More settings', 'speed-booster-pack' ),
							'items' => array(
								'remove_emojis'          => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove WordPress Emoji scripts', 'speed-booster-pack' ),
									'tooltip'       => __( 'Emojis are fun and all, but if you are aren’t using them they actually load a JavaScript file (wp-emoji-release.min.js) on every page of your website. For a lot of businesses, this is not needed and simply adds load time to your site. So we recommend disabling this.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'remove_wsl'             => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove WordPress Shortlink', 'speed-booster-pack' ),
									'tooltip'       => __( 'WordPress URL shortening is sometimes useful, but it automatically adds an ugly code in your header, so you can remove it.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'remove_adjacent'        => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove Adjacent Posts Links', 'speed-booster-pack' ),
									'tooltip'       => __( 'WordPress incorrectly implements this feature that supposedly should fix a pagination issues but it messes up, so there is no reason to keep these around. However, some browsers may use Adjacent Posts Links to navigate your site, although you can remove it if you run a well designed theme.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'wml_link'               => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove Windows Manifest', 'speed-booster-pack' ),
									'tooltip'       => __( 'Windows Live Writer (WLW) is a Microsoft application for composing and managing blog posts offline and publish them later. If you are not using Windows Live Writer application, you can remove it from the WP head.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'wp_generator'           => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove the WordPress Version', 'speed-booster-pack' ),
									'tooltip'       => __( 'Remove WordPress version meta tag.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'remove_all_feeds'       => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove all rss feed links', 'speed-booster-pack' ),
									'tooltip'       => __( 'This option wil remove all rss feed links to cleanup your WordPress header. It is also useful on Unicorn – The W3C Markup Validation Service to get rid out the “feed does not validate” error.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'disable_xmlrpc'         => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable XML-RPC', 'speed-booster-pack' ),
									'tooltip'       => __( 'XML-RPC was added in WordPress 3.5 and allows for remote connections, and unless you are using your mobile device to post to WordPress it does more bad than good. In fact, it can open your site up to a bunch of security risks. There are a few plugins that utilize this such as JetPack.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'disable_self_pingbacks' => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable Self Pingbacks', 'speed-booster-pack' ),
									'tooltip'       => __( 'A pingback is a special type of comment that’s created when you link to another blog post, as long as the other blog is set to accept pingbacks.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'disable_dashicons'      => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable Dashicons', 'speed-booster-pack' ),
									'tooltip'       => __( 'Disable dashicons from front end.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'disable_google_maps'    => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable Google Maps', 'speed-booster-pack' ),
									'tooltip'       => __( 'Remove any instance of Google Maps being loaded across the entire site. ', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'disable_heartbeat'      => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable Heartbeat', 'speed-booster-pack' ),
									'tooltip'       => __( 'Disable heartbeat everywhere ( used for autosaving and revision tracking ).', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'heartbeat_frequency'    => array(
									'type'          => 'select',
									'label'         => __( 'Heartbeat frequency', 'speed-booster-pack' ),
									'tooltip'       => __( 'Controls how often the Wordpress Heartbeat API is allowed to run. ', 'speed-booster-pack' ),
									'options'       => array(
										'15' => '15',
										'30' => '30',
										'45' => '45',
										'60' => '60',
									),
									'options_group' => 'sbp_settings',
								),
								'limit_post_revisions'   => array(
									'type'          => 'select',
									'label'         => __( 'Limit Post Revision', 'speed-booster-pack' ),
									'tooltip'       => __( 'Controls how many Revisions Wordpress will save ', 'speed-booster-pack' ),
									'options'       => array(
										'1'     => '1',
										'2'     => '2',
										'3'     => '3',
										'4'     => '4',
										'5'     => '5',
										'10'    => '10',
										'15'    => '15',
										'20'    => '20',
										'25'    => '25',
										'30'    => '30',
										'false' => 'Disable',
									),
									'options_group' => 'sbp_settings',
								),
								'autosave_interval'      => array(
									'type'          => 'select',
									'label'         => __( 'Autosave interval', 'speed-booster-pack' ),
									'tooltip'       => __( 'Controls how wordpress will autosave posts and pages while editing.', 'speed-booster-pack' ),
									'options'       => array(
										'1' => __( '1 minute ( default )', 'speed-booster-pack' ),
										'2' => __( '2 minutes', 'speed-booster-pack' ),
										'3' => __( '3 minutes', 'speed-booster-pack' ),
										'4' => __( '4 minutes', 'speed-booster-pack' ),
										'5' => __( '5 minutes', 'speed-booster-pack' ),
									),
									'options_group' => 'sbp_settings',
								),
								'remove_rest_api_links'  => array(
									'type'          => 'checkbox',
									'label'         => __( 'Remove REST API Links', 'speed-booster-pack' ),
									'tooltip'       => __( 'The WordPress REST API provides API endpoints for WordPress data types that allow developers to interact with sites remotely by sending and receiving JSON (JavaScript Object Notation) objects.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
							),
						),
						//need even more speed section
						array(
							'type'  => 'section',
							'label' => __( 'Need even more speed?', 'speed-booster-pack' ),
							'items' => array(
								'sbp_css_async'  => array(
									'type'          => 'checkbox',
									'label'         => __( 'Inline all CSS styles', 'speed-booster-pack' ),
									'tooltip'       => __( 'Checking this option will inline the contents of all your stylesheets. This helps with the annoying render blocking error Google Page Speed Insights displays.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_css_minify' => array(
									'type'          => 'checkbox',
									'label'         => __( 'Minify all (previously) inlined CSS styles', 'speed-booster-pack' ),
									'tooltip'       => __( 'Minifying all inlined CSS styles will optimize the CSS delivery and will eliminate the annoying message on Google Page Speed regarding to render-blocking css.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_footer_css' => array(
									'type'          => 'checkbox',
									'label'         => __( 'Move all inlined CSS into the footer', 'speed-booster-pack' ),
									'tooltip'       => __( 'Inserting all CSS styles inline to the footer is a sensitive option that will eliminate render-blocking CSS warning in Google Page Speed test. If there is something broken after activation, you need to disable this option. Please note that before enabling this sensitive option, it is strongly recommended that you also enable the “ Move scripts to the footer” option.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_is_mobile'  => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable all above CSS options on mobile devices', 'speed-booster-pack' ),
									'tooltip'       => __( 'Disable all above CSS options on mobile devices: this option was added to avoid some appearance issues on mobile devices.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
							),
						),
						//other options section
						array(
							'type'  => 'section',
							'items' => array(
								'sbp_css_exceptions'   => array(
									'type'        => 'textarea',
									'label'       => __( 'Exclude styles from being inlined and/or minified option: ', 'speed-booster-pack' ),
									'description' => __( 'Enter one by line, the handles of css files or the final part of the style URL.', 'speed-booster-pack' ),
								),
								//CSS handle guidance
								'guidance_options_css' => array(
									'type'  => 'guidance',
									'label' => __( 'As a guidance, here is a list of css handles of each enqueued style detected by our plugin:', 'speed-booster-pack' ),
								),
							),
						),
					),
				),
				//advanced options panel
				'advanced-options' => array(
					//Exclude scripts fro being moved to the footer
					'sections' => array(
						array(
							'type'        => 'section',
							'label'       => __( 'Exclude scripts from being moved to the footer', 'speed-booster-pack' ),
							'description' => __( 'Enter one js handle per text field. Read more <a href="https://docs.machothemes.com/article/119-plugin-options-explained#exclude-scripts-from-being-moved-to-the-footer-50">detailed instructions</a> on this option on plugin documentation.', 'speed-booster-pack' ),
							'items'       => array(
								'sbp_js_footer_exceptions1' => array(
									'type' => 'text',
								),
								'sbp_js_footer_exceptions2' => array(
									'type' => 'text',
								),
								'sbp_js_footer_exceptions3' => array(
									'type' => 'text',
								),
								'sbp_js_footer_exceptions4' => array(
									'type' => 'text',
								),
								//guidance
								'guidance_options_js'       => array(
									'type'  => 'guidance',
									'label' => __( 'As a guidance, here is a list of script handles and script paths of each enqueued script detected by our plugin:', 'speed-booster-pack' ),
								),
							),
						),
						//Exclude scripts from being deferred
						array(
							'type'  => 'section',
							'label' => __( 'Exclude scripts from being deferred', 'speed-booster-pack' ),
							'items' => array(
								'sbp_defer_exceptions1' => array(
									'type' => 'text',
								),
								'sbp_defer_exceptions2' => array(
									'type' => 'text',
								),
								'sbp_defer_exceptions3' => array(
									'type' => 'text',
								),
								'sbp_defer_exceptions4' => array(
									'type' => 'text',
								),
								'info'                  => array(
									'type'             => 'guidance',
									'description_only' => true,
									'description'      => __( 'Enter one by text field, the handle part of the js files that you want to be excluded from defer parsing option. For example: <code>jquery-core</code> If you want to exclude more than 4 scripts, you can use the following filter: <code>sbp_exclude_defer_scripts</code> which takes an array of script handles as params. If you don\'t know how to handle this, feel free to post on our support forums.', 'speed-booster-pack' ),
								),
							),
						),
					),
				),
				'cdn-options'      => array(
					'sections' => array(
						array(
							'type'        => 'section',
							'label'       => __( 'CDN', 'speed-booster-pack' ),
							'description' => __( 'CDN options that allow you to rewrite your site URLs with your CDN URLs.', 'speed-booster-pack' ),
							'items'       => array(
								'sbp_enable_cdn'               => array(
									'type'          => 'checkbox',
									'label'         => __( 'Enable CDN Rewrite', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enables rewriting of your site URLs with your CDN URLs', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_cdn_url'                  => array(
									'type'          => 'text',
									'label'         => __( 'CDN URL', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enter your CDN URL without the trailing backslash. Example: https://cdn.example.com', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_cdn_included_directories' => array(
									'type'          => 'text',
									'label'         => __( 'Included Directories', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enter any directories you would like to be included in CDN rewriting, separated by commas (,). Default: wp-content,wp-includes', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
									'default'       => 'wp-content,wp-includes',
								),
								'sbp_cdn_exclusions'           => array(
									'type'          => 'text',
									'label'         => __( 'CDN Exclusions', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enter any directories or file extensions you would like to be excluded from CDN rewriting, separated by commas (,). Default: .php', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
									'default'       => '.php',
								),
							),
						),
					),
				),
				'google-analytics' => array(
					'sections' => array(
						array(
							'type'        => 'section',
							'label'       => __( 'Google Analytics', 'speed-booster-pack' ),
							'description' => __( 'Optimization options for Google Analytics.', 'speed-booster-pack' ),
							'items'       => array(
								'sbp_enable_local_analytics'   => array(
									'type'          => 'checkbox',
									'label'         => __( 'Enable Local Analytics', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enable syncing og the Google Analytics script to your own server.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_ga_tracking_id'           => array(
									'type'          => 'text',
									'label'         => __( 'Tracking ID', 'speed-booster-pack' ),
									'tooltip'       => __( 'Enter your Google Analytics tracking ID', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_tracking_position'        => array(
									'type'          => 'select',
									'label'         => __( 'Tracking code position', 'speed-booster-pack' ),
									'tooltip'       => __( 'Load your analytics script in the header or footer of the site. Default - header', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
									'options'       => array(
										'header' => 'Header ( default )',
										'footer' => 'Footer',
									),
								),
								'sbp_disable_display_features' => array(
									'type'          => 'checkbox',
									'label'         => __( 'Disable Display Features', 'speed-booster-pack' ),
									'tooltip'       => __( 'Disable marketing and advertising which generates a 2nd HTTP request', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_anonymize_ip'             => array(
									'type'          => 'checkbox',
									'label'         => __( 'Anonymize IP', 'speed-booster-pack' ),
									'tooltip'       => __( 'Shorten visitor IP to comply with privacy restrictions in some countries.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_track_loggedin_admins'    => array(
									'type'          => 'checkbox',
									'label'         => __( 'Track Logged In Admins', 'speed-booster-pack' ),
									'tooltip'       => __( 'Include logged in Wordpress admins in your GA report.', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_bounce_rate'              => array(
									'type'          => 'text',
									'label'         => __( 'Adjusted Bounce Rate', 'speed-booster-pack' ),
									'tooltip'       => __( 'Set a timeout limit in seconds to better evaluate the quality of your traffic ( 1 - 100 )', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
								'sbp_monsterinsights'          => array(
									'type'          => 'checkbox',
									'label'         => __( 'User MonsterInsights', 'speed-booster-pack' ),
									'tooltip'       => __( 'Allows MonsterInsights to manage your Google Analaytics while still using the locally hosted analytics.js generated by Speed Booster Pack', 'speed-booster-pack' ),
									'options_group' => 'sbp_settings',
								),
							),
						),
					),
				),
			);

			//Start the tabs
			foreach ( $sbp_options_array as $k => $values ) { ?>
			<!--  Tab sections  -->
			<div id="<?php echo $k; ?>" class="sb-pack-tab">

				<?php
				if ( $k == 'advanced-options' ) {
				?>
				<!-- Advanced Options sections -->
				<h3><?php _e('Advanced', 'speed-booster-pack'); ?></h3>
				<div id="poststuff">

					<?php
					} else {
					?>
					<!-- Sections For General Options -->
					<div class="sb-pack">
						<?php
						if ( isset( $values['label'] ) ) {
							?>
							<h3><?php echo $values['label']; ?></h3>
							<?php
						}
						}
						//Start the sections
						foreach ( $values['sections'] as $section => $section_value ) {
							if ( 'advanced-options' != $k ) {
								?>
								<h3><?php echo ( isset( $section_value['label'] ) ) ? $section_value['label'] : ""; ?></h3><?php
							} else {
								?>
								<div class="meta-box-sortables ui-sortable" id="normal-sortables">
								<div class="postbox" id="<?php echo $section; ?>">
								<button type="button" class="handlediv" aria-expanded="true">
									<span class="screen-reader-text"><?php echo ( isset( $section_value['label'] ) ) ? $section_value['label'] : ""; ?></span>
									<span class="toggle-indicator" aria-hidden="true"></span>
								</button>
								<h3 class="hndle ui-sortable-handle" style="cursor: pointer;">
									<span><?php echo ( isset( $section_value['label'] ) ) ? $section_value['label'] : ""; ?></span>
								</h3>
								<div class="inside">
								<?php
							}
							//Start the options
							foreach ( $section_value['items'] as $item => $item_value ) {

								if ( 'checkbox' == $item_value['type'] ) { ?>
									<div class="onoffswitch-wrapper">
										<?php if ( isset( $item_value['tooltip'] ) ) { ?>
											<span class="tooltip-right"
											      data-tooltip="<?php echo $item_value['tooltip']; ?>">
                                                        <i class="dashicons dashicons-editor-help"></i>
                                                    </span>
										<?php } ?>
										<span class="chekbox-title"><?php echo ( isset( $item_value['label'] ) ) ? $item_value['label'] : ''; ?></span>

										<div class="onoffswitch">
											<div class="epsilon-toggle">
												<input class="epsilon-toggle__input" type="checkbox" id="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>" name="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>" value="1" <?php checked( 1, isset( $sbp_options[ $item ] ) ); ?> >
												<div class="epsilon-toggle__items">
													<span class="epsilon-toggle__track"></span>
													<span class="epsilon-toggle__thumb"></span>
													<svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 6 6">
														<path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
													</svg>
													<svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" role="img" focusable="false" viewBox="0 0 2 6">
														<path d="M0 0h2v6H0z"></path>
													</svg>
												</div>
											</div>
											<label for="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>"></label>
										</div>
									</div>
								<?php }
								if ( 'select' == $item_value['type'] ) { ?>
									<p>
										<?php if ( isset( $item_value['tooltip'] ) ) { ?>
											<span class="tooltip-right"
											      data-tooltip="<?php echo $item_value['tooltip']; ?>">
                                                <i class="dashicons dashicons-editor-help"></i>
                                            </span>
										<?php } ?>
										<label for="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>" class="<?php echo ( isset( $item_value['label'] ) ) ? 'label-text' : ''; ?>"><?php echo ( isset( $item_value['label'] ) ) ? $item_value['label'] : ''; ?></label>
										<select id="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>"
										        name="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>">
											<?php
											foreach ( $item_value['options'] as $option_k => $op_v ) {
												?>
												<option value="<?php echo $option_k; ?>" <?php selected( $option_k, $sbp_options[ $item ], true ); ?> ><?php echo $op_v; ?></option>
												<?php
											}
											?>
										</select>
									</p>
								<?php }

								if ( 'text' == $item_value['type'] ) { ?>
									<p>
										<?php
										$default_value = ( isset( $item_value['default'] ) ) ? $item_value['default'] : "";
										if ( isset( $item_value['options_group'] ) ) {
											$op_text = ( isset( $sbp_options[ $item ] ) && "" != $sbp_options[ $item ] ) ? $sbp_options[ $item ] : $default_value;
										} else {
											$op_text = ( get_option( $item ) ) ? get_option( $item ) : $default_value;
										}

										?>
										<?php if ( isset( $item_value['tooltip'] ) ) { ?>
											<span class="tooltip-right"
											      data-tooltip="<?php echo $item_value['tooltip']; ?>">
                                <i class="dashicons dashicons-editor-help"></i>
                           </span>
										<?php } ?>
										<label for="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>" class="<?php echo ( isset( $item_value['label'] ) ) ? 'label-text' : ''; ?>"><?php echo ( isset( $item_value['label'] ) ) ? $item_value['label'] : ''; ?></label>

										<input id="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>"
										       name="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>" type="text" value="<?php echo esc_attr( $op_text ); ?>" />
									</p>
								<?php }

								if ( 'textarea' == $item_value['type'] ) { ?>
									<div class="td-border-last"></div>
									<h4><?php echo ( isset( $item_value['label'] ) ) ? $item_value['label'] : ''; ?></h4>
									<p>
                                            <textarea cols="50" rows="3" name="<?php echo ( isset( $item_value['options_group'] ) ) ? $item_value['options_group'] . '[' . $item . ']' : $item; ?>"
                                                      id="<?php echo $item; ?>"
                                                      value="<?php echo esc_attr( $css_exceptions ); ?>"><?php echo wp_kses_post( $css_exceptions ); ?></textarea>
									</p>
									<p class="description">
										<?php echo isset( $item_value['description'] ) ? $item_value['description'] : ''; ?>
									</p>
								<?php }

								if ( 'guidance' == $item_value['type'] ) {
									//guidance for General options
									if ( $item == 'guidance_options_css' ) {
										?>
										<div class="td-border-last"></div>

										<p>
										<h4><?php $item_value['label']; ?></h4>
										</p>

										<div class="sbp-all-enqueued">

											<div class="sbp-div-head">
												<div class="sbp-title-scripts"><?php _e( 'CSS Handle', 'speed-booster-pack' ); ?></div>
											</div>

											<div class="sbp-inline-wrap">
												<div class="sbp-columns1 sbp-width">
													<?php print_r( get_option( 'all_theme_styles_handle' ) ); ?>
												</div>
											</div>
										</div>
										<?php
									}
									if ( 'guidance_options_js' == $item ) {
										?>
										<div class="td-border-last"></div>
										<h4><?php echo $item_value['label']; ?></h4>
										<div class="sbp-all-enqueued">
											<div class="sbp-div-head">
												<div class="sbp-title-scripts"><?php _e( 'Script Handle', 'speed-booster-pack' ); ?></div>
												<div class="sbp-title-scripts"><?php _e( 'Script Path', 'speed-booster-pack' ); ?></div>
											</div>
											<div class="sbp-inline-wrap">

												<div class="sbp-columns1 sbp-width">
													<?php
													$all_script_handles = get_option( 'all_theme_scripts_handle' );

													$all_script_handles = explode( '<br />', $all_script_handles );

													foreach ( $all_script_handles as $key => $value ) {
														if ( ! empty( $value ) ) {
															echo '<p>' . esc_html( $value ) . '</p>';
														}
													}
													?>
												</div>

												<div class="sbp-columns2 sbp-width">
													<?php
													$all_scripts_src = get_option( 'all_theme_scripts_src' );

													$all_scripts_src = explode( '<br />', $all_scripts_src );

													foreach ( $all_scripts_src as $key => $value ) {
														if ( ! empty( $value ) ) {
															$value = parse_url( $value );
															echo '<p>' . esc_html( str_replace( '/wp-content', '', $value['path'] ) ) . '</p>';
														}

													}
													?>
												</div>
											</div>
										</div>
										<?php
									}
									if ( isset( $item_value['description_only'] ) && $item_value['description_only'] ) {
										?>
										<p class="description"><?php echo $item_value['description']; ?></p>
										<?php
									}

								}

							}
							if ( 'advanced-options' == $k ) {
								?>      </div>
								</div>
								</div>
							<?php }
						}
						?>
					</div><!-- Advanced Options sections || Sections For General Options -->
				</div> <!-- Tab sections  -->
				<?php } ?>

				<div id="optimize-more" class="sb-pack-tab">
					<div class="feature-section three-col">

					<div class="col sbp-box">
						<img src="https://ps.w.org/shortpixel-image-optimiser/assets/icon-128x128.png?rev=1038819">
						<div class="sbp-box__name"><?php esc_html_e( 'ShortPixel Image Optimizer', 'speed-booster-pack' ); ?></div>

						<div class="sbp-box__description">
							<?php esc_html_e( 'Increase your website’s SEO ranking, number of visitors and ultimately your sales by optimizing any image. ', 'speed-booster-pack' ); ?>
						</div>

						<div class="sbp-box__action-bar">
							<span class="sbp-box__action-button">
								<a class="button" href="<?php echo esc_url( 'https://shortpixel.com/h/af/IVAKFSX31472' ); ?>" target="_blank"><?php esc_html_e( 'Test your site for free', 'speed-booster-pack' ); ?></a>
							</span>
						</div>
					</div>

					<div class="col sbp-box">
						<img src="https://ps.w.org/optimole-wp/assets/icon-128x128.png?rev=1975706">
						<div class="sbp-box__name"><?php esc_html_e( 'Optimole', 'speed-booster-pack' ); ?></div>
						<div class="sbp-box__description">
							<?php esc_html_e( 'Image optimization & resizing. Image acceleration through CDN. On-the-fly image handling. ', 'speed-booster-pack' ); ?>
						</div>

						<div class="sbp-box__action-bar">
							<span class="sbp-box__action-button">
								<a class="button" href="<?php echo esc_url( 'https://optimole.com' ); ?>" target="_blank"><?php esc_html_e( 'Check it out for free', 'speed-booster-pack' ); ?></a>
							</span>
						</div>
					</div>


					<div class="col sbp-box">
						<img src="<?php echo SPEED_BOOSTER_PACK_URL ?>/inc/images/hostinger-official.jpg">
						<div class="sbp-box__name"><?php esc_html_e( 'Hostinger', 'speed-booster-pack' ); ?></div>

						<div class="sbp-box__description">
							<?php esc_html_e( 'They are offering the best cheap WordPress hosting on the market right now at 1.45$ / month', 'speed-booster-pack' ); ?>
						</div>

						<div class="sbp-box__action-bar">
							<span class="sbp-box__action-button">
								<a class="button button-primary" href="<?php echo esc_url( 'https://www.hostg.xyz/SHA' ); ?>" target="_blank"><?php esc_html_e( 'Get a faster host now', 'speed-booster-pack' ); ?></a>
							</span>
						</div>
					</div>

					</div>
				</div><!--#optimize-more-->

				<div class="textright">
					<hr />
					<?php submit_button( '', 'button button-primary button-hero' ); ?>
				</div>

		</form>

	</div><!--/.sb-pack-->
</div> <!-- end wrap div -->