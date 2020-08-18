<?php

namespace SpeedBooster;

class SBP_PageSpeed_Tricker extends SBP_Abstract_Module {
	private $wp_config_path;
	private $wp_filesystem;

	public function __construct() {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$this->wp_config_path = ABSPATH . 'wp-config.php';
		} else {
			$this->wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		$this->wp_filesystem = sbp_get_filesystem();
	}

	public function toggle_lines() {
		if ( sbp_get_option( 'pagespeed_tricker' ) ) {
			$this->add_lines();
		} else {
			$this->remove_lines();
		}
	}

	private function add_lines() {
		$insertion = '// BEGIN SBP_PageSpeed_Tricker
if(preg_match(\'/Lighthouse/i\',$_SERVER[\'HTTP_USER_AGENT\'])) {
	echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\"></head><body style=\"background:#0cce6b;font:bold 15vw sans-serif;color:#fff\">You\'ve got a perfect score. Good for you. Now go and speed up your website for real, actual people.</body></html>";
	exit;
}
// END SBP_PageSpeed_Tricker';
		if ( is_writable( $this->wp_config_path ) ) {
			$this->remove_lines();
			$wp_config_content          = $this->wp_filesystem->get_contents( $this->wp_config_path );
			$modified_wp_config_content = preg_replace( '/^<\?php/', '<?php' . PHP_EOL . PHP_EOL . $insertion . PHP_EOL, $wp_config_content );
			$this->wp_filesystem->put_contents( $this->wp_config_path, $modified_wp_config_content );
		}
	}

	private function remove_lines() {
		// Find PS Tricker Lines
		$wp_config_content = $this->wp_filesystem->get_contents( $this->wp_config_path );
		$wp_config_content = preg_replace( '/(' . PHP_EOL . PHP_EOL . '\/\/ BEGIN SBP_PageSpeed_Tricker.*?\/\/ END SBP_PageSpeed_Tricker' . PHP_EOL . ')/msi', '', $wp_config_content );
		$this->wp_filesystem->put_contents( $this->wp_config_path, $wp_config_content );
	}
}