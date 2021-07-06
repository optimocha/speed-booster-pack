<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SBP_Database_Optimizer
 * @package SpeedBooster
 * @since 4.2.0
 */
class SBP_Database_Optimizer extends SBP_Abstract_Module {
	/**
	 * SBP_Database_Optimizer constructor.
	 * @since 4.2.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_sbp_database_action', [ $this, 'handle_ajax_request' ] );
	}

	/**
	 * @since 4.2.0
	 */
	private function fetch_non_innodb_tables() {
		global $wpdb;

		$tableStatuses = $wpdb->get_results( 'SHOW TABLE STATUS' );

		$tables = [];

		foreach ( $tableStatuses as $table ) {
			if ( strtolower( $table->Engine ) !== "innodb" ) {
				$tables['tables'][] = [
					'table_name'     => $table->Name,
					'storage_engine' => $table->Engine,
				];
			}
		}

		echo wp_json_encode( $tables );
		wp_die();
	}

	/**
	 * @param $table_name
	 *
	 * @since 4.2.0
	 */
	private function convert_table_to_innodb( $table_name ) {
		global $wpdb;
		$wpdb->hide_errors();

		$result = $wpdb->get_results( 'ALTER TABLE ' . $table_name . ' ENGINE=INNODB' );
		if ( $wpdb->last_error ) {
			echo wp_json_encode( [
				'status'  => 'failure',
				'message' => __( 'Error occurred while converting. Error details: ' . $wpdb->last_error, 'speed-booster-pack' ),
			] );
		} else {
			echo wp_json_encode( [
				'status'  => 'success',
				'message' => __( 'Table converted successfully.', 'speed-booster-pack' ),
			] );
		}
		exit;
	}

	public function handle_ajax_request() {
		if ( current_user_can( 'manage_options' ) && isset( $_GET['sbp_action'] ) ) {
			if ( ! wp_verify_nonce( $_GET['nonce'], 'sbp_ajax_nonce' ) ) {
				echo wp_json_encode( [
					'status'  => 'failure',
					'message' => __( 'Invalid nonce.', 'speed-booster-pack' ),
				] );
				wp_die();
			}

			switch ( $_GET['sbp_action'] ) {
				case "fetch_non_innodb_tables":
					$this->fetch_non_innodb_tables();
					break;
				case "convert_tables":
					$table_name = $_GET['sbp_convert_table_name'];
					$this->convert_table_to_innodb( $table_name );
					break;
			}
		}
	}
}