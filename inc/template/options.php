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
			echo esc_html__( 'Speed Booster Pack is a lightweight, frequently updated, easy to use and well supported plugin which allows you to improve your website’s loading speed. Visitors usually close a website if it doesn’t load in a few seconds and the slower a site loads the greater the chances are that the visitors will leave. And you don’t want that to happen, do you? 
', 'sb-pack' );
			?>
		</div>
		<div class="wp-badge sbp-welcome-logo"></div>

		<div class="sbp-fast-as-a-rabbit">
			<div class="sbp-speed-page-load">
				<?php _e( 'Page load: ', 'sb-pack' ); ?>
				<span><?php echo esc_html( $page_time ) . __( ' sec', 'sb-pack' ); ?></span>
				<span class="sbp-progress-bar">
					<?php if ( $page_time < 10 ) { ?>
					<progress max="100" value="<?php echo $page_time * 10; ?>">
						<?php } else { ?>
						<progress max="100" value="100" class="sbp-progress-red">
							<?php } ?>
						</progress>

						<?php if ( ( $page_time ) > 10 ) { ?>
							<img draggable="false" class="emoji" title="Your page loading time just made us cry" alt="😥" src="https://s.w.org/images/core/emoji/2.4/svg/1f625.svg">
						<?php } else if ( ( $page_time ) > 8 && ( $page_time ) < 10 ) { ?>
							<img draggable="false" class="emoji" title="Your page loading time just made us cry" alt="😥" src="https://s.w.org/images/core/emoji/2.4/svg/1f625.svg">
						<?php } else if ( ( $page_time ) > 6 && ( $page_time ) < 8 ) { ?>
							<img draggable="false" class="emoji" title="I'm not gonna lie, things aren't looking to good" alt="😮" src="https://s.w.org/images/core/emoji/2.4/svg/1f62e.svg">
						<?php } else if ( ( $page_time ) > 4 && ( $page_time ) < 6 ) { ?>
							<img draggable="false" class="emoji" title="Could be better" alt="🙄" src="https://s.w.org/images/core/emoji/2.4/svg/1f644.svg">
						<?php } else if ( ( $page_time ) > 2 && ( $page_time ) < 4 ) { ?>
							<img draggable="false" class="emoji" title="Going strong" alt="😥" src="https://s.w.org/images/core/emoji/2.4/svg/1f625.svg">
						<?php } else if ( ( $page_time ) < 2 ) { ?>
							<img draggable="false" title="You're a star" class="emoji" alt="⭐" src="https://s.w.org/images/core/emoji/2.4/svg/2b50.svg">
						<?php } ?>
				</span>

			</div>
			<div class="sbp-speed-db-queries">
				<i class="dashicons dashicons-dashboard"></i><span><?php echo esc_html( $page_queries ); ?></span> <?php _e( 'DB Queries', 'sb-pack' ); ?>
			</div>

			<div class="sbp-speed-memory-usage">
				<i class="dashicons dashicons-chart-pie"></i>
				<?php _e( 'Memory: ', 'sb-pack' ); ?>
				<span><?php echo number_format( ( memory_get_peak_usage() / 1024 / 1024 ), 1, ',', '' ) . ' / ' . ini_get( 'memory_limit' ), '<br />'; ?></span>

			</div>

			<div class="sbp-speed-plugin-number">
				<i class="dashicons dashicons-admin-plugins"></i>
				<?php _e( 'Plugins: ', 'sb-pack' ); ?>
				<span><?php echo count( get_option( 'active_plugins' ) ); ?></span>
			</div>

			<div class="sbp-speed-info-analyze-container">
				<!-- <a href="#" class="button button-secondary"><i class="dashicons dashicons-info"></i></a> -->
				<!-- <a href="#" class="button button-primary"><i class="dashicons dashicons-search"></i><?php _e( 'Analyse', 'sb-pack' ); ?> -->
				</a>
			</div>
		</div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a class="nav-tab" href="#general-options"><?php _e( 'General', 'sb-pack' ); ?></a>
			<a class="nav-tab" href="#advanced-options"><?php _e( 'Advanced', 'sb-pack' ); ?></a>
			<a class="nav-tab" href="#image-options"><?php _e( 'Image Optimization', 'sb-pack' ); ?></a>
			<a class="nav-tab" href="#support"><?php _e( 'Support', 'sb-pack' ); ?></a>
		</h2>

		<form method="post" action="options.php">

			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			<?php settings_fields( 'speed_booster_settings_group' ); ?>

			<div id="general-options" class="sb-pack-tab">

				<h3><?php _e( 'General', 'sb-pack' ); ?></h3>

				<p>
					<input id="sbp_settings[jquery_to_footer]" name="sbp_settings[jquery_to_footer]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['jquery_to_footer'] ) ); ?> />
					<label for="sbp_settings[jquery_to_footer]"><?php _e( 'Move scripts to the footer', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'This option move all scripts to the footer while keeping stylesheets in the header to improve page loading speed and get a higher score on the major speed testing sites such as GTmetrix or other website speed testing tools.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>

				</p>

				<p>
					<input id="sbp_settings[use_google_libs]" name="sbp_settings[use_google_libs]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['use_google_libs'] ) ); ?> />
					<label for="sbp_settings[use_google_libs]"><?php _e( 'Load JS from Google Libraries', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Loading WordPress javascript files from Google’s Libraries rather than serving it from your WordPress install directly, will reduce latency, increase parallelism and improve caching.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[minify_html_js]" name="sbp_settings[minify_html_js]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['minify_html_js'] ) ); ?> />
					<label for="sbp_settings[minify_html_js]"><?php _e( 'Minify HTML and JS', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Activate this option only if you don’t want to use other minify plugins or other speed optimization plugin that has minify option included. If something goes wrong, simply uncheck this option and save the settings.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[defer_parsing]" name="sbp_settings[defer_parsing]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['defer_parsing'] ) ); ?> />
					<label for="sbp_settings[defer_parsing]"><?php _e( 'Defer parsing of javascript files', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'By deferring parsing of unneeded JavaScript until it needs to be executed, you can reduce the initial load time of your page. Please note that this option will not defer the main WordPress jQuery script if Load JS from Google Libraries option is not checked.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[query_strings]" name="sbp_settings[query_strings]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['query_strings'] ) ); ?> />
					<label for="sbp_settings[query_strings]"><?php _e( 'Remove query strings', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Since most proxies do not cache resources with a ? in their URL, this option allows you to remove any query strings (version numbers) from static resources like CSS & JS files, thus improving your speed scores in services like GTmetrix, PageSpeed, YSlow and Pingdoom.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<?php if ( is_plugin_active( 'crazy-lazy/crazy-lazy.php' ) ) { ?>
						<input id="sbp_settings[lazy_load]" name="sbp_settings[lazy_load]" type="hidden" value="<?php echo( isset( $sbp_options['lazy_load'] ) ? '1' : '0' ); ?>" />
						<label for="sbp_settings[lazy_load]"><?php _e( 'Lazy loading already handled by CrazyLazy plugin', 'sb-pack' ); ?></label>
						<span class="tooltip-right"
						      data-tooltip="<?php echo __( 'This option will improve the web page loading times of your images. When is checked, you will notice that your images will be loaded only when they become visible to the user viewport. If you experience issues with some slideshow or slider plugins, you may need to uncheck this option.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
					<?php } else { ?>
						<input id="sbp_settings[lazy_load]" name="sbp_settings[lazy_load]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['lazy_load'] ) ); ?> />
						<label for="sbp_settings[lazy_load]"><?php _e( 'Lazy load images to improve speed', 'sb-pack' ); ?></label>
						<span class="tooltip-right"
						      data-tooltip="<?php echo __( 'This option will improve the web page loading times of your images. When is checked, you will notice that your images will be loaded only when they become visible to the user viewport. If you experience issues with some slideshow or slider plugins, you may need to uncheck this option.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
					<?php } ?>
				</p>

				<p>
					<input id="sbp_settings[font_awesome]" name="sbp_settings[font_awesome]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['font_awesome'] ) ); ?> />
					<label for="sbp_settings[font_awesome]"><?php _e( 'Removes extra Font Awesome styles', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Use this option only if your theme uses Font Awesome, to prevent other plugins that uses Font Awesome, to add their stylesheets to your theme. In other words, this option removes extra Font Awesome stylesheets added to your theme by certain plugins.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<h3> <?php _e( 'More settings', 'sb-pack' ); ?></h3>


				<p>
					<input id="sbp_settings[remove_emojis]" name="sbp_settings[remove_emojis]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_emojis'] ) ); ?> />
					<label for="sbp_settings[remove_emojis]"><?php _e( 'Remove WordPress Emoji scripts', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Emojis are fun and all, but if you are aren’t using them they actually load a JavaScript file (wp-emoji-release.min.js) on every page of your website. For a lot of businesses, this is not needed and simply adds load time to your site. So we recommend disabling this.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>


				<p>
					<input id="sbp_settings[remove_wsl]" name="sbp_settings[remove_wsl]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_wsl'] ) ); ?> />
					<label for="sbp_settings[remove_wsl]"><?php _e( 'Remove WordPress Shortlink', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'WordPress URL shortening is sometimes useful, but it automatically adds an ugly code in your header, so you can remove it.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[remove_adjacent]" name="sbp_settings[remove_adjacent]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_adjacent'] ) ); ?> />
					<label for="sbp_settings[remove_adjacent]"><?php _e( 'Remove Adjacent Posts Links', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'WordPress incorrectly implements this feature that supposedly should fix a pagination issues but it messes up, so there is no reason to keep these around. However, some browsers may use Adjacent Posts Links to navigate your site, although you can remove it if you run a well designed theme.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[wml_link]" name="sbp_settings[wml_link]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['wml_link'] ) ); ?> />
					<label for="sbp_settings[wml_link]"><?php _e( 'Remove Windows Manifest', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Windows Live Writer (WLW) is a Microsoft application for composing and managing blog posts offline and publish them later. If you are not using Windows Live Writer application, you can remove it from the WP head.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[rsd_link]" name="sbp_settings[rsd_link]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['rsd_link'] ) ); ?> />
					<label for="sbp_settings[rsd_link]"><?php _e( 'Remove RSD(Really Simple Discovery) Link', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'This type of link is used by blog clients. If you edit your site from your browser then you don’t need this. It is also used by some 3rd party applications that utilize XML-RPC requests. In most cases, this is just unnecessary code.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[wp_generator]" name="sbp_settings[wp_generator]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['wp_generator'] ) ); ?> />
					<label for="sbp_settings[wp_generator]"><?php _e( 'Remove the WordPress Version', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'This option is added for security reasons and cleaning the header.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[remove_all_feeds]" name="sbp_settings[remove_all_feeds]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_all_feeds'] ) ); ?> />
					<label for="sbp_settings[remove_all_feeds]"><?php _e( 'Remove all rss feed links', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'This option wil remove all rss feed links to cleanup your WordPress header. It is also useful on Unicorn – The W3C Markup Validation Service to get rid out the “feed does not validate” error.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<p>
					<input id="sbp_settings[disable_xmlrpc]" name="sbp_settings[disable_xmlrpc]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['disable_xmlrpc'] ) ); ?> />
					<label for="sbp_settings[disable_xmlrpc]"><?php _e( 'Disable XML-RPC', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'XML-RPC was added in WordPress 3.5 and allows for remote connections, and unless you are using your mobile device to post to WordPress it does more bad than good. In fact, it can open your site up to a bunch of security risks. There are a few plugins that utilize this such as JetPack, but we don’t recommend using JetPack for performance reasons.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
					</span>
				</p>

				<h3><?php _e( 'Need even more speed?', 'sb-pack' ); ?></h3>

				<p>
					<input id="sbp_css_async" name="sbp_settings[sbp_css_async]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_css_async'] ) ); ?> />
					<label for="sbp_css_async"><?php _e( 'Load CSS asynchronously', 'sb-pack' ); ?></label>
					<span class="tooltip-right"
					      data-tooltip="<?php echo __( 'Loading CSS asynchronously will render your page more quickly to get a higher score on the major speed testing services.', 'sb-pack' ); ?>">
								<i class="dashicons dashicons-editor-help"></i>
							</span>
				</p>



				<div id="sbp-css-content">

					<p>
						<input id="sbp_settings[sbp_css_minify]" name="sbp_settings[sbp_css_minify]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_css_minify'] ) ); ?> />
						<label for="sbp_settings[sbp_css_minify]"><?php _e( 'Minify all CSS styles', 'sb-pack' ); ?></label>
						<span class="tooltip-right"
						      data-tooltip="<?php echo __( 'Minifying and inline all CSS styles will optimize the CSS delivery and will eliminate the anoying message on Google Page Speed regarding to render-blocking css.', 'sb-pack' ); ?>">
								<i class="dashicons dashicons-editor-help"></i>
							</span>
					</p>

					<p>
						<input id="sbp_settings[sbp_footer_css]" name="sbp_settings[sbp_footer_css]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_footer_css'] ) ); ?> />
						<label for="sbp_settings[sbp_footer_css]"><?php _e( 'Insert all CSS styles inline to the footer', 'sb-pack' ); ?></label>
						<span class="tooltip-right"
						      data-tooltip="<?php echo __( 'Inserting all CSS styles inline to the footer is a sensitive option that will eliminate render-blocking CSS warning in Google Page Speed test. If there is something broken after activation, you need to disable this option. Please note that before enabling this sensitive option, it is strongly recommended that you also enable the “ Move scripts to the footer” option.', 'sb-pack' ); ?>">
								<i class="dashicons dashicons-editor-help"></i>
								</span>
					</p>

					<p>
						<input id="sbp_settings[sbp_is_mobile]" name="sbp_settings[sbp_is_mobile]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_is_mobile'] ) ); ?> />
						<label for="sbp_settings[sbp_is_mobile]"><?php _e( 'Disable all above CSS options on mobile devices', 'sb-pack' ); ?></label>
						<span class="tooltip-right"
						      data-tooltip="<?php echo __( 'Disable all above CSS options on mobile devices: this option was added to avoid some appearance issues on mobile devices.', 'sb-pack' ); ?>">
						<i class="dashicons dashicons-editor-help"></i>
						</span>
					</p>

					<div class="td-border-last"></div>

					<h4><?php _e( 'Exclude styles from asynchronously option: ', 'sb-pack' ); ?></h4>
					<p>
						<textarea cols="50" rows="3" name="sbp_css_exceptions" id="sbp_css_exceptions" value="<?php echo $css_exceptions; ?>" /><?php echo $css_exceptions; ?></textarea>
					</p>
					<p class="description">
						<?php _e( 'Enter one by line, the handles of css files or the final part of the style URL. For example: <code>font-awesome</code> or <code>font-awesome.min.css</code>', 'sb-pack' ); ?>
					</p>

					<div class="td-border-last"></div>

					<p>
					<h4><?php _e( 'As a guidance, here is a list of css handles of each enqueued style detected by our plugin:', 'sb-pack' ); ?></h4>
					</p>

					<div class="sbp-all-enqueued">

						<div class="sbp-div-head">
							<div class="sbp-title-scripts"><?php _e( 'CSS Handle', 'sb-pack' ); ?></div>
						</div>

						<div class="sbp-inline-wrap">
							<div class="sbp-columns1 sbp-width">
								<?php print_r( get_option( 'all_theme_styles_handle' ) ); ?>
							</div>
						</div>
					</div>

					<p class="description">
						<?php _e( '*The list may be incomplete in some circumstances.', 'sb-pack' ); ?>
					</p>

				</div><!--#sbp-css-content-->

			</div>
			<div id="advanced-options" class="sb-pack-tab">

				<br />
				<div id="poststuff">
					<div id="postbox-container-1" class="postbox-container">
						<div class="meta-box-sortables" id="normal-sortables">
							<?php if ( isset( $closed ) && $closed == true ) { ?>
							<div class="postbox closed" id="exclude-from-footer">
								<?php } else { ?>
								<div class="postbox" id="exclude-from-footer">
									<?Php } ?>
									<button type="button" class="handlediv" aria-expanded="true">
										<span class="screen-reader-text"><?php _e( 'Exclude scripts from being moved to the footer', 'sb-pack' ); ?></span>
										<span class="toggle-indicator" aria-hidden="true"></span>
									</button>
									<h3 class="hndle ui-sortable-handle">
										<span><?php _e( 'Exclude scripts from being moved to the footer', 'sb-pack' ); ?></span>
									</h3>
									<div class="inside">
										<div class="sbp-inline-wrap">

											<div class="sbp-columns1">

												<h4><?php _e( 'Script Handle', 'sb-pack' ); ?></h4>

												<p>
													<input type="text" name="sbp_js_footer_exceptions1" id="sbp_js_footer_exceptions1" value="<?php echo $js_footer_exceptions1; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_js_footer_exceptions2" id="sbp_js_footer_exceptions2" value="<?php echo $js_footer_exceptions2; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_js_footer_exceptions3" id="sbp_js_footer_exceptions3" value="<?php echo $js_footer_exceptions3; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_js_footer_exceptions4" id="sbp_js_footer_exceptions4" value="<?php echo $js_footer_exceptions4; ?>" />
												</p>

											</div><!--/.sbp-columns1-->

											<div class="sbp-columns2">

												<h4><?php _e( 'Copy the HTML code of the script from your page source and add it below', 'sb-pack' ); ?></h4>

												<p>
													<input type="text" name="sbp_head_html_script1" id="sbp_head_html_script1" class="regular-text" value="<?php echo $sbp_html_script1; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_head_html_script2" id="sbp_head_html_script2" class="regular-text" value="<?php echo $sbp_html_script2; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_head_html_script3" id="sbp_head_html_script3" class="regular-text" value="<?php echo $sbp_html_script3; ?>" />
												</p>

												<p>
													<input type="text" name="sbp_head_html_script4" id="sbp_head_html_script4" class="regular-text" value="<?php echo $sbp_html_script4; ?>" />
												</p>
											</div><!--/.sbp-columns2-->

											<p class="description">
												<?php _e( 'Enter one js handle per text field, in the left area and the correspondent html script in the right text fields.', 'sb-pack' ); ?> <?php _e( 'Read more', 'sb-pack' ); ?>
												<a href="https://docs.machothemes.com/article/119-plugin-options-explained#exclude-scripts-from-being-moved-to-the-footer-50" target="_blank" title="Documentation"><?php _e( 'detailed instructions', 'sb-pack' ); ?></a> <?php _e( 'on this option on plugin documentation.', 'sb-pack' ); ?>
												<br /> <?php _e( 'If you want to exclude more than 4 scripts, your page score will be hit and therefore the use of "Move scripts to footer" option will become useless so you can disable it.', 'sb-pack' ); ?>
											</p>
											<div class="td-border-last"></div>

											<p>
											<h4><?php _e( 'As a guidance, here is a list of script handles and script paths of each enqueued script detected by our plugin:', 'sb-pack' ); ?></h4>
											</p>

											<div class="sbp-all-enqueued">

												<div class="sbp-div-head">
													<div class="sbp-title-scripts"><?php _e( 'Script Handle', 'sb-pack' ); ?></div>
													<div class="sbp-title-scripts"><?php _e( 'Script Path', 'sb-pack' ); ?></div>
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

										</div><!--/.sbp-inline-wrap-->

										<p class="description">
											<?php _e( '*The list may be incomplete in some circumstances.', 'sb-pack' ); ?>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<div class="meta-box-sortables" id="normal-sortables">
								<?php if ( isset( $closed_defer ) && $closed_defer == true ) { ?>
								<div class="postbox closed" id="defer-from-footer">
									<?php } else { ?>
									<div class="postbox" id="defer-from-footer">
										<?Php } ?>
										<button type="button" class="handlediv" aria-expanded="true">
											<span class="screen-reader-text"><?php _e( 'Exclude scripts from being deferred', 'sb-pack' ); ?></span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
										<h3 class="hndle ui-sortable-handle">
											<span><?php _e( 'Exclude scripts from being deferred', 'sb-pack' ); ?></span>
										</h3>
										<div class="inside">

											<div class="sbp-inline-wrap">
												<p>
													<input type="text" class="sbp-more-width" name="sbp_defer_exceptions1" id="sbp_defer_exceptions1" value="<?php echo $defer_exceptions1; ?>" />
												</p>

												<p>
													<input type="text" class="sbp-more-width" name="sbp_defer_exceptions2" id="sbp_defer_exceptions2" value="<?php echo $defer_exceptions2; ?>" />
												</p>

												<p>
													<input type="text" class="sbp-more-width" name="sbp_defer_exceptions3" id="sbp_defer_exceptions3" value="<?php echo $defer_exceptions3; ?>" />
												</p>

												<p>
													<input type="text" class="sbp-more-width" name="sbp_defer_exceptions4" id="sbp_defer_exceptions4" value="<?php echo $defer_exceptions4; ?>" />
												</p>
											</div>
											<p class="description">
												<?php _e( 'Enter one by text field, the final part of the js files that you want to be excluded from defer parsing option. For example: <code>jquery.min.js</code> If you want to exclude more than 4 scripts, your page score will be hit and therefore the use of "Defer parsing of javascript files" option will become useless so you can disable it', 'sb-pack' ); ?>
											</p>

										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="image-options" class="sb-pack-tab">

						<br />
						<?php
						$plugins = array(
							'shortpixel-image-optimiser' => array(
								'title'       => esc_html__( 'ShortPixel Image Optimizer', 'sb-pack' ),
								'description' => esc_html__( 'Increase your website’s SEO ranking, number of visitors and ultimately your sales by optimizing any image or PDF document on your website. ', 'sb-pack' ),
								'more'        => 'https://shortpixel.com/h/af/IVAKFSX31472',
							),

						);

						if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
							require_once ABSPATH . 'wp-admin/includes/plugin.php';
						}

						$installed_plugins = get_plugins();

						function sbp_get_plugin_basename_from_slug( $slug, $installed_plugins ) {
							$keys = array_keys( $installed_plugins );
							foreach ( $keys as $key ) {
								if ( preg_match( '|^' . $slug . '/|', $key ) ) {
									return $key;
								}
							}

							return $slug;
						}

						?>

						<div class="sbp-recommended-plugins">
							<?php
							foreach ( $plugins as $slug => $plugin ) {

								$label       = __( 'Install + Activate & get 500 free credits', 'sb-pack' );
								$action      = 'install';
								$plugin_path = sbp_get_plugin_basename_from_slug( $slug, $installed_plugins );
								$url         = '#';
								$class       = '';

								if ( file_exists( ABSPATH . 'wp-content/plugins/' . $plugin_path ) ) {

									if ( is_plugin_active( $plugin_path ) ) {
										$label  = __( 'Activated', 'sb-pack' );
										$action = 'disable';
										$class  = 'disabled';
									} else {
										$label  = __( 'Activate & get 500 free credits', 'sb-pack' );
										$action = 'activate';
										$url    = wp_nonce_url( add_query_arg( array(
											'action' => 'activate',
											'plugin' => $plugin_path,
										), admin_url( 'plugins.php' ) ), 'activate-plugin_' . $plugin_path );
									}
								}

								?>
								<div class="sbp-recommended-plugin">
									<div class="plugin-image">
										<img src="https://ps.w.org/shortpixel-image-optimiser/assets/icon-128x128.png?rev=1038819">
									</div>
									<div class="plugin-information">
										<h3 class="plugin-name">
											<strong><?php echo esc_html( $plugin['title'] ); ?></strong></h3>
										<p class="plugin-description"><?php echo esc_html( $plugin['description'] ); ?></p>

										<a href="<?php echo esc_url( $url ); ?>" data-action="<?php echo esc_attr( $action ); ?>" data-slug="<?php echo esc_attr( $plugin_path ); ?>" data-message="<?php esc_html_e( 'Activated', 'sb-pack' ); ?>" class="button-primary sbp-plugin-button <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></a>
										<?php if ( isset( $plugin['more'] ) ) : ?>
											<a href="<?php echo esc_url( $plugin['more'] ); ?>" class="button-secondary" target="_blank"><?php esc_html_e( 'Test your site for free', 'sb-pack' ); ?></a>
										<?php endif ?>
									</div>
								</div>
							<?php } ?>
						</div>

						<h3><?php _e( 'Change the default image compression level', 'sb-pack' ); ?></h3>

						<script type='text/javascript'>
							var jpegCompression = '<?php echo $this->image_compression; ?>';
						</script>

						<p class="sbp-amount">
							<?php _e( 'Compression level:', 'sb-pack' ); ?>
							<input type="text" class="sbp-amount" id="sbp-amount" />
						</p>

						<p>
						<div class="sbp-slider" id="sbp-slider"></div>
						<input type="hidden" name="sbp_integer" id="sbp_integer" value="<?php echo $this->image_compression; ?>" />
						</p>

						<p class="description">
							<?php _e( 'The default image compression setting in WordPress is 90%. Compressing your images further than the default will make your file sizes even smaller and will boost your site performance. As a reference, a lower level of compression means more performance but might induce quality loss. We recommend you choose a compression level between 50 and 75.', 'sb-pack' ); ?>
							<br />
						</p>
						<p class="description"><strong>
								<?php _e( 'Note that any changes you make will only affect new images uploaded to your site. A specialized plugin can optimize all your present images and will also optimize new ones as they are added. ', 'sb-pack' ); ?>
							</strong></p>
						<br>

					</div>
					<div id="support" class="sb-pack-tab">

						<?php
						if ( ! defined( 'WPINC' ) ) {
							die;
						}
						?>
						<div class="feature-section sbp-support">
							<div class="row two-col center-support">

								<h3>
									<i class="dashicons dashicons-sos" style="display: inline-block;vertical-align: middle;margin-right: 5px"></i><?php esc_html_e( 'Contact Support', 'sb-pack' ); ?>
								</h3>
								<p>
									<i><?php esc_html_e( 'We offer support through WordPress.org\'s support forums.', 'sb-pack' ); ?></i>
								</p>
								<p>
									<a target="_blank" class="button button-hero button-primary" href="<?php echo esc_url( 'https://wordpress.org/support/plugin/speed-booster-pack#new-post' ); ?>"><?php esc_html_e( 'Post on our support forums', 'sb-pack' ); ?></a>
								</p>

							</div>
							<div class="row">
								<h2 class="sbp-title">Looking for better WP hosting ?</h2>
							</div>
							<div class="row sbp-blog three-col">
								<div class="col">
									<h3>
										<i class="dashicons dashicons-performance" style="display: inline-block;vertical-align: middle;margin-right: 5px"></i><?php esc_html_e( 'Our Bluehost Hosting Review', 'sb-pack' ); ?>
									</h3>
									<p>
										<i><?php esc_html_e( 'Despite its popularity, though, Bluehost often carries a negative perception among WordPress professionals. So as we dig into this Bluehost review, we\'ll be looking to figure out whether Bluehost\'s performance and features actually justify that reputation.', 'sb-pack' ); ?></i>
									</p>
									<p>
										<a target="_blank" href="<?php echo esc_url( 'https://www.machothemes.com/blog/bluehost-review/?utm_source=sbp&utm_medium=about-page&utm_campaign=blog-links' ); ?>"><?php esc_html_e( 'Read more', 'sb-pack' ); ?></a>
									</p>
								</div><!--/.col-->

								<div class="col">
									<h3>
										<i class="dashicons dashicons-performance" style="display: inline-block;vertical-align: middle;margin-right: 5px"></i><?php esc_html_e( 'Our InMotion Hosting Review', 'sb-pack' ); ?>
									</h3>
									<p>
										<i><?php esc_html_e( 'InMotion Hosting is a popular independent web host that serves over 300,000 customers. They\'re notably not a part of the EIG behemoth (the parent company behind Bluehost, HostGator, and more), which is a plus in my book.', 'sb-pack' ); ?></i>
									</p>
									<p>
										<a target="_blank" href="<?php echo esc_url( 'https://www.machothemes.com/blog/inmotion-hosting-review/?utm_source=sbp&utm_medium=about-page&utm_campaign=blog-links' ); ?>"><?php esc_html_e( 'Read more', 'sb-pack' ); ?></a>
									</p>
								</div><!--/.col-->

								<div class="col">
									<h3>
										<i class="dashicons dashicons-performance" style="display: inline-block;vertical-align: middle;margin-right: 5px"></i><?php esc_html_e( 'Our A2 Hosting Review', 'sb-pack' ); ?>
									</h3>
									<p>
										<i><?php esc_html_e( 'When it comes to affordable WordPress hosting, A2 Hosting is a name that often comes up in various WordPress groups for offering quick-loading performance that belies its low price tag.', 'sb-pack' ); ?></i>
									</p>
									<p>
										<a target="_blank" href="<?php echo esc_url( 'https://www.machothemes.com/blog/a2-hosting-review/?utm_source=sbp&utm_medium=about-page&utm_campaign=blog-links' ); ?>"><?php esc_html_e( 'Read more', 'sb-pack' ); ?></a>
									</p>
								</div><!--/.col-->
							</div>
						</div><!--/.feature-section-->

						<div class="col-fulwidth feedback-box">
							<h3>
								<?php esc_html_e( 'Lend a hand & share your thoughts', 'sb-pack' ); ?>
								<img src="<?php echo $this->plugin_url . "inc/images/handshake.png"; ?>">
							</h3>
							<p>
								<?php
								echo vsprintf( // Translators: 1 is Theme Name, 2 is opening Anchor, 3 is closing.
									__( 'We\'ve been working hard on making %1$s the best one out there. We\'re interested in hearing your thoughts about %1$s and what we could do to <u>make it even better</u>.<br/> <br/> %2$sHave your say%3$s', 'sb-pack' ), array(
									'Speed Booster Pack',
									'<a class="button button-feedback" target="_blank" href="http://bit.ly/feedback-speed-booster-pack">',
									'</a>',
								) );
								?>
							</p>
						</div>
					</div>

					<br />
					<div class="textright">
						<hr />
						<?php submit_button( '', 'button button-primary button-hero' ) ?>
					</div>

		</form>

	</div><!--/.sb-pack-->
</div> <!-- end wrap div -->