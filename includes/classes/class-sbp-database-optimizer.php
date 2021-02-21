<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Database_Optimizer extends SBP_Abstract_Module {
	/**
	 * Checks the database tables storage engines.
	 * @since 4.2.0
	 */
	public static function check_database_storage_engine() {
		global $wpdb;

//		$engines = $wpdb->get_results('SHOW TABLE STATUS');
//		echo '<pre>' , print_r($engines), '</pre>';exit;
	}
}