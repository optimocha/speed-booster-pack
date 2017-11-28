<div class="wrap">

<div class="sb-pack">

<div id="poststuff">
<div id="postbox-container" class="postbox-container">
  <div class="meta-box-sortables ui-sortable" id="normal-sortables">


<h2 class="sbp-icon"><?php echo esc_html( get_admin_page_title() ); ?></h2>

<div class="welcome-panel">

<div class="welcome-panel-content">

<form method="post" action="options.php">

<?php settings_fields( 'speed_booster_settings_group' ); ?>
<div class="main-sbp-title"><h3 ><?php _e( 'Boost Your Website Speed!', 'sb-pack' ); ?></h3></div>
<div class="postbox" id="tiguan1">

<div title="Click to toggle" class="handlediv"><br></div>
<h3 class="hndle"><span><?php _e( 'General options', 'sb-pack' ); ?></span></h3>

<div class="inside">

<div class="welcome-panel-column-container">

<div class="welcome-panel-column">

<h4><?php _e( 'Main plugin options', 'sb-pack' ); ?></h4>

<p>
<input id="sbp_settings[jquery_to_footer]" name="sbp_settings[jquery_to_footer]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['jquery_to_footer'] ) ); ?> />
<label for="sbp_settings[jquery_to_footer]"><?php _e( 'Move scripts to the footer', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[use_google_libs]" name="sbp_settings[use_google_libs]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['use_google_libs'] ) ); ?> />
<label for="sbp_settings[use_google_libs]"><?php _e( 'Load JS from Google Libraries', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[defer_parsing]" name="sbp_settings[defer_parsing]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['defer_parsing'] ) ); ?> />
<label for="sbp_settings[defer_parsing]"><?php _e( 'Defer parsing of javascript files', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[query_strings]" name="sbp_settings[query_strings]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['query_strings'] ) ); ?> />
<label for="sbp_settings[query_strings]"><?php _e( 'Remove query strings', 'sb-pack' ); ?></label>
</p>

 <p>
     <?php if ( is_plugin_active('crazy-lazy/crazy-lazy.php') ) { ?>
         <input id="sbp_settings[lazy_load]" name="sbp_settings[lazy_load]" type="hidden" value="<?php echo(isset( $sbp_options['lazy_load'] )? '1' : '0' ); ?>" />
         <label for="sbp_settings[lazy_load]"><?php _e( 'Lazy loading already handled by CrazyLazy plugin', 'sb-pack' ); ?></label>
     <?php } else {?>
        <input id="sbp_settings[lazy_load]" name="sbp_settings[lazy_load]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['lazy_load'] ) ); ?> />
        <label for="sbp_settings[lazy_load]"><?php _e( 'Lazy load images to improve speed', 'sb-pack' ); ?></label>
     <?php } ?>
</p>


<p>
<input id="sbp_settings[font_awesome]" name="sbp_settings[font_awesome]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['font_awesome'] ) ); ?> />
<label for="sbp_settings[font_awesome]"><?php _e( 'Removes extra Font Awesome styles', 'sb-pack' ); ?></label>
</p>

</div> <!-- END welcome-panel-column -->


<div class="welcome-panel-column">
<h4> <?php _e( 'Other plugin settings', 'sb-pack' ); ?></h4>

<p>
<input id="sbp_settings[minify_html_js]" name="sbp_settings[minify_html_js]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['minify_html_js'] ) ); ?> />
<label for="sbp_settings[minify_html_js]"><?php _e( 'Minify HTML and JS', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[remove_wsl]" name="sbp_settings[remove_wsl]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_wsl'] ) ); ?> />
<label for="sbp_settings[remove_wsl]"><?php _e( 'Remove WordPress Shortlink', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[remove_adjacent]" name="sbp_settings[remove_adjacent]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_adjacent'] ) ); ?> />
<label for="sbp_settings[remove_adjacent]"><?php _e( 'Remove Adjacent Posts Links', 'sb-pack' ); ?></label>
</p>

<p>
    <input id="sbp_settings[wml_link]" name="sbp_settings[wml_link]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['wml_link'] ) ); ?> />
    <label for="sbp_settings[wml_link]"><?php _e( 'Remove Windows Manifest', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[wp_generator]" name="sbp_settings[wp_generator]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['wp_generator'] ) ); ?> />
<label for="sbp_settings[wp_generator]"><?php _e( 'Remove the WordPress Version', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[remove_all_feeds]" name="sbp_settings[remove_all_feeds]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['remove_all_feeds'] ) ); ?> />
<label for="sbp_settings[remove_all_feeds]"><?php _e( 'Remove all rss feed links', 'sb-pack' ); ?></label>
</p>

</div> <!-- END welcome-panel-column -->


<div class="welcome-panel-column  welcome-panel-last">

<h4> <?php _e( 'Home Page Load Stats', 'sb-pack' ); ?></h4>

<span class="sbp-stats"><?php _e( 'Page loading time in seconds:', 'sb-pack' ); ?></span>

<div class="sbp-progress time">
<span></span>
</div>

<div class="sbp-values">
<div class="sbp-numbers">
<?php echo $page_time; ?> <?php _e( 's', 'sb-pack' ); ?>
</div>
</div>

<span class="sbp-stats"><?php _e( 'Number of executed queries:', 'sb-pack' ); ?></span>

<div class="sbp-progress queries">
<span></span>
</div>

<div class="sbp-values">
<div class="sbp-numbers">
<?php echo $page_queries; ?> <?php _e( 'q', 'sb-pack' ); ?>
</div>
</div>

<div class="debug-info">
<strong><?php _e( 'Peak Memory Used:', 'sb-pack' ); ?></strong> <span><?php echo number_format( ( memory_get_peak_usage()/1024/1024 ), 2, ',', '' ) . ' / ' . ini_get( 'memory_limit' ), '<br />'; ?></span>
<strong><?php _e( 'Active Plugins:', 'sb-pack' ); ?></strong> <span><?php echo count( get_option( 'active_plugins' ) ) ; ?></span>
</div>

</div> <!-- END welcome-panel-column  welcome-panel-last -->

</div> <!-- END welcome-panel-column-container -->

 </div>
 </div>

          <div style="display: block;" class="postbox closed" id="tiguan2">
            <div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><?php _e( 'Exclude scripts from being moved to the footer', 'sb-pack' ); ?></h3>
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


</div>

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

</div>
</div>

<p class="description">
<?php _e('Enter one js handle per text field, in the left area and the correspondent html script in the right text fields.', 'sb-pack' ); ?> <?php _e( 'Read more', 'sb-pack' ); ?> <a href="http://tiguandesign.com/docs/speed-booster/#exclude-scripts-from-being-moved-to-the-footer-50" target="_blank" title="Documentation"><?php _e( 'detailed instructions', 'sb-pack' ); ?></a> <?php _e( 'on this option on plugin documentation.', 'sb-pack' ); ?> <br /> <?php _e( 'If you want to exclude more than 4 scripts, your page score will be hit and therefore the use of "Move scripts to footer" option will become useless so you can disable it.', 'sb-pack' ); ?>
</p>
<div class="td-border-last"></div>

<p>
<h4 class="hndle"><?php _e( 'As a guidance, here is a list of script handles and script paths of each enqueued script detected by our plugin:', 'sb-pack' ); ?></h4>
</p>

<div class="sbp-all-enqueued">

<div class="sbp-div-head">
<div class="sbp-title-scripts"><?php _e('Script Handle', 'sb-pack' ); ?></div>
<div class="sbp-title-scripts"><?php _e('Script Path', 'sb-pack' ); ?></div>
</div>

<div class="sbp-inline-wrap">

<div class="sbp-columns1 sbp-width">
<?php echo get_option( 'all_theme_scripts_handle' ) ; ?>
</div>

<div class="sbp-columns2 sbp-width">
<?php echo get_option( 'all_theme_scripts_src' ) ; ?>
 </div>


 </div>

</div>
<p class="description">
    <?php _e('*The list may be incomplete in some circumstances.', 'sb-pack' ); ?>
</p>
 </div>
 </div>



 <div style="display: block;" class="postbox closed" id="tiguan3">
    <div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><?php _e( 'Exclude scripts from being deferred', 'sb-pack' ); ?></h3>
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
            <?php _e('Enter one by text field, the final part of the js files that you want to be excluded from defer parsing option. For example: <code>jquery.min.js</code> If you want to exclude more than 4 scripts, your page score will be hit and therefore the use of "Defer parsing of javascript files" option will become useless so you can disable it', 'sb-pack' ); ?>
        </p>

    </div>
</div>


          <div style="display: block;" class="postbox closed" id="tiguan4">
            <div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle sbp-emphasize"><?php _e( 'Change the default image compression level', 'sb-pack' ); ?></h3>
            <div class="inside">



<script type='text/javascript'>
var jpegCompression = '<?php echo $this->image_compression; ?>';
</script>

<div>

<p class="sbp-amount">
<?php _e( 'Compression level:', 'sb-pack' ); ?><input type="text" class="sbp-amount" id="sbp-amount" />
</p>

<p>
<div class="sbp-slider" id="sbp-slider"></div>
<input type="hidden" name="sbp_integer" id="sbp_integer" value="<?php echo $this->image_compression; ?>" />
</p>

<p class="description">
<?php _e( 'The default image compression setting in WordPress is 90%. Compressing your images further than the default will make your file sizes even smaller and will boost your site performance. As a reference, a lower level of compression means more performance but might induce quality loss. We recommend you choose a compression level between 50 and 75.', 'sb-pack' ); ?><br />
</p>
<p class="description"><strong>
<?php _e( 'Note that any changes you make will only affect new images uploaded to your site. A specialized plugin can optimize all your present images and will also optimize new ones as they are added. ', 'sb-pack' ); ?>
</strong></p>
<br>
<p class="description sp-link"><strong>
   <a href="https://shortpixel.com/h/af/U3NQVWK31472" target="_blank">
       <?php _e( 'Test your website with  ShortPixel for free to see how much you could gain by optimizing your images.', 'sb-pack' ); ?>
   </a>
</strong></p>
<a href="https://shortpixel.com/h/af/U3NQVWK31472" target="_blank"><img src="<?php echo $this->plugin_url . "inc/images/sp.png"; ?>" class="sbp-sp"/></a>
<p class="description">
    <?php _e( 'ShortPixel is an easy to use, comprehensive, stable and frequently updated image optimization plugin supported by the friendly team that created it. Using a powerful set of specially tuned algorithms, it squeezes the most of each image striking the best balance between image size and quality. Current images can be all optimized with a single click. Newly added images are automatically resized/rescaled and optimized on the fly, in the background.', 'sb-pack' ); ?>
</p>
<p class="description-link">
    <a href="https://shortpixel.com/h/af/U3NQVWK31472" target="_blank">&gt;&gt; <?php _e( 'More info', 'sb-pack' ); ?></a>
</p>
</div>

 </div>
 </div>


          <div style="display: block;" class="postbox closed" id="tiguan5">
            <div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><?php _e( 'Still need more speed?', 'sb-pack' ); ?></h3>
            <div class="inside">


<p>
<input id="sbp_css_async" name="sbp_settings[sbp_css_async]" type="checkbox" value="1"  <?php checked( 1, isset( $sbp_options['sbp_css_async'] ) ); ?> />
<label for="sbp_css_async"><?php _e( 'Load CSS asynchronously', 'sb-pack' ); ?></label>
</p>


<div id="sbp-css-content">

<p>
<input id="sbp_settings[sbp_css_minify]" name="sbp_settings[sbp_css_minify]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_css_minify'] ) ); ?> />
<label for="sbp_settings[sbp_css_minify]"><?php _e( 'Minify all CSS styles', 'sb-pack' ); ?></label>
</p>

<div class="sbp-radio-content">

<p>
<input id="sbp_settings[sbp_footer_css]" name="sbp_settings[sbp_footer_css]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_footer_css'] ) ); ?> />
<label for="sbp_settings[sbp_footer_css]"><?php _e( 'Insert all CSS styles inline to the footer', 'sb-pack' ); ?></label>
</p>

<p>
<input id="sbp_settings[sbp_is_mobile]" name="sbp_settings[sbp_is_mobile]" type="checkbox" value="1" <?php checked( 1, isset( $sbp_options['sbp_is_mobile'] ) ); ?> />
<label for="sbp_settings[sbp_is_mobile]"><?php _e( 'Disable all above CSS options on mobile devices', 'sb-pack' ); ?></label>
</p>

<div class="td-border-last"></div>

<h4><?php _e( 'Exclude styles from asynchronously option: ', 'sb-pack' ); ?></h4>
<p><textarea cols="50" rows="3" name="sbp_css_exceptions" id="sbp_css_exceptions" value="<?php echo $css_exceptions; ?>" /><?php echo $css_exceptions; ?></textarea></p>
<p class="description">
<?php _e('Enter one by line, the handles of css files or the final part of the style URL. For example: <code>font-awesome</code> or <code>font-awesome.min.css</code>', 'sb-pack' ); ?>
</p>


<div class="td-border-last"></div>

<p>
<h4 class="hndle"><?php _e( 'As a guidance, here is a list of css handles of each enqueued style detected by our plugin:', 'sb-pack' ); ?></h4>
</p>

<div class="sbp-all-enqueued">

<div class="sbp-div-head">
<div class="sbp-title-scripts"><?php _e('CSS Handle', 'sb-pack' ); ?></div>
</div>

<div class="sbp-inline-wrap">
<div class="sbp-columns1 sbp-width">
<?php echo get_option( 'all_theme_styles_handle' ) ; ?>
</div>
</div>

</div>

<p class="description">
    <?php _e('*The list may be incomplete in some circumstances.', 'sb-pack' ); ?>
</p>



<div class="td-border-last"></div>
<h4 class="sbp-icon-information"><?php _e( 'Additional information:', 'sb-pack' ); ?></h4>
<p class ="description"><strong><?php _e( 'Insert all CSS styles inline to the footer: ', 'sb-pack' ); ?></strong><?php _e( 'this option will eliminate render-blocking CSS warning in Google Page Speed test. If there is something broken after activation, you need to disable this option. Please note that before enabling this sensitive option, it is strongly recommended that you also enable the "Move scripts to the footer" option.', 'sb-pack' ); ?></p>

 </div>
 </div>
</div><!-- END sbp-radio-content -->
</div><!-- END sbp-css-content -->



<?php submit_button() ?>

</form>

</div> <!-- END welcome-panel-content -->

</div> <!-- END welcome-panel -->

<div class="col-fulwidth feedback-box">
  <h3>
    <?php esc_html_e( 'Lend a hand & share your thoughts', 'saboxplugin' ); ?>
    <img src="<?php echo $this->plugin_url . "inc/images/handshake.png"; ?>"> 
  </h3>
  <p>
    <?php
    echo vsprintf(
      // Translators: 1 is Theme Name, 2 is opening Anchor, 3 is closing.
      __( 'We\'ve been working hard on making %1$s the best one out there. We\'re interested in hearing your thoughts about %1$s and what we could do to <u>make it even better</u>.<br/> <br/> %2$sHave your say%3$s', 'sb-pack' ),
      array(
        'Speed Booster Pack',
        '<a class="button button-feedback" target="_blank" href="http://bit.ly/feedback-speed-booster-pack">',
        '</a>',
      )
    );
    ?>
  </p>
</div>

<!-- START docs and version areas -->

<div class="sbp-title-div">
<div class="sbp-title">
<?php _e( 'What do these settings mean?', 'sb-pack' ); ?>
</div>
</div>

<div class="sbp-box"><!-- start sbp-box div 1 -->

<div class="sbp-box-legend">
<i class="sbp-icon-help"></i>
</div>

<p><a href="http://tiguandesign.com/docs/speed-booster/" target="_blank" title="Documentation"><?php _e( 'Read online plugin documentation', 'sb-pack' ); ?></a><?php _e( ' with guidelines to enhance your website performance.', 'sb-pack' ); ?></p>

</div> <!-- end sbp-box div 1-->

<!-- END docs and version areas -->
          </div>
        </div>
    </div>
</div> <!-- END sb-pack-->

</div> <!-- end wrap div -->
