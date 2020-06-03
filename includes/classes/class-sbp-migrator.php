<?php

namespace SpeedBooster;

class SBP_Migrator {
	private $sbp_settings; // Old options
	private $sbp_options; // New options

	public function __construct() {
		$this->sbp_settings = get_option( 'sbp_settings' );
		if ( $this->sbp_settings ) {
			$this->sbp_options = get_option( 'sbp_options' );

			add_action( 'upgrader_process_complete', [ $this, 'upgrade_completed' ] );
			add_action( 'admin_init', [ $this, 'handle_migrate_request' ] );
			add_action( 'admin_notices', [ $this, 'display_update_notice' ] );
		}
	}

	public function handle_migrate_request() {
		if ( get_transient( 'sbp_upgraded' ) ) {
			$this->migrate_options();
			$this->delete_old_options();
			delete_transient( 'sbp_upgraded' );
		}
	}

	private function migrate_options() {
		$this->add_tracking_scripts();
	}

	public function add_tracking_scripts() {
		// Check for tracking scripts
		if ( $tracking_id = $this->sbp_settings['sbp_ga_tracking_id'] ) {
			if ( strpos( $tracking_id, "GTM-" ) === 0 || strpos( $tracking_id, 'UA-' ) === 0 ) {
				$analytics_script = '';
				if ( strpos( $tracking_id, "GTM-" ) === 0 ) {
					$analytics_script = "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','$tracking_id');</script>
<!-- End Google Tag Manager -->
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=$tracking_id\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";
				} else {
					$analytics_script = "<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', '$tracking_id', 'auto');
ga('send', 'pageview');
</script>
<!-- End Google Analytics -->";
				}

				// Create tracking script in custom code manager
				$custom_codes                      = [];
				$custom_codes[]                    = [
					'custom_codes_item'   => $analytics_script,
					'custom_codes_place'  => 'footer',
					'custom_codes_method' => 'normal',
				];
				$this->sbp_options['custom_codes'] = $custom_codes;
				update_option( 'sbp_options', $this->sbp_options );
				// Just in case. If migration works twice for any reason, custom code won't be added again.
				$this->sbp_settings['sbp_ga_tracking_id'] = '';
				update_option( 'sbp_settings', $this->sbp_settings );
			}
		}
	}

	public function upgrade_completed( $upgrader_object, $options ) {
		$our_plugin = plugin_basename( SBP_PATH );
		if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin == $our_plugin ) {
					set_transient( 'sbp_upgraded', 1 );
				}
			}
		}
	}

	public function display_update_notice() {
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'With the new version of %s, your settings are migrated to the plugin\'s new options framework. <a href="%s">Click here to review %1$s\'s options.</a>', 'speed-booster-pack' ), SBP_PLUGIN_NAME, admin_url( 'admin.php?page=sbp-settings' ) ) . '</p></div>';
	}

	public function delete_old_options() {
		delete_option( 'sbp_settings' );
		delete_option( 'sbp_css_exceptions' );
		delete_option( 'sbp_js_footer_exceptions' );
		delete_option( 'sbp_lazyload_exclusions' );
		delete_option( 'sbp_preboost' );
	}
}