<?php
/**
 * This class is responsible for enqueuing admin and public scripts and styles.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined( 'ABSPATH' ) || exit;

class AssetService {
	public function __construct() {
		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicScripts' ] );
	}

	/**
	 * Enqueue admin scripts and styles
	 * @return void
	 * @since 5.0.0
	 */
	public function enqueueAdminScripts() {
		// Enqueue admin styles and scripts
		wp_enqueue_script( SBP_PLUGIN_SLUG, SBP_URL . 'assets/admin/js/speed-booster-pack-admin.js', [ 'jquery' ], SBP_VERSION, true );
		wp_enqueue_style( SBP_PLUGIN_SLUG, SBP_URL . 'assets/admin/css/speed-booster-pack-admin.css', [], SBP_VERSION );

		wp_enqueue_script( 'deactivation-survey', SBP_URL . 'assets/admin/js/deactivation-survey.js', [
			'jquery',
			SBP_PLUGIN_SLUG
		], SBP_VERSION, true );
		wp_enqueue_style( 'deactivation-survey', SBP_URL . 'assets/admin/css/deactivation-survey.css', [], SBP_VERSION );
	}

	/**
	 * Enqueue public scripts and styles
	 * @return void
	 * @since 5.0.0
	 */
	public function enqueuePublicScripts() {
		// Enqueue public styles and scripts
		wp_enqueue_script( 'inspage', SBP_URL . 'assets/public/js/inspage.js', [ 'jquery' ], SBP_VERSION, true );
		wp_enqueue_script( 'lazyload', SBP_URL . 'assets/public/js/lazyload.js', [ 'jquery' ], SBP_VERSION, true );
	}
}
