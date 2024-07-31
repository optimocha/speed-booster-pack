<?php
/**
 * This class is responsible for post type operations.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined( 'ABSPATH' ) || exit;
class PostTypeService {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'savePostTypes' ] );
	}

	/**
	 * Save public post types
	 * @since 5.0.0
	 */
	public function savePostTypes() {
		$postTypes      = array_keys( get_post_types( [ 'public' => true ] ) );
		$savedPostTypes = get_option( 'sbp_public_post_types' );

		if ( ! $savedPostTypes || $savedPostTypes != $postTypes ) {
			update_option( 'sbp_public_post_types', $postTypes );
		} else {
			add_option( 'sbp_public_post_types', $postTypes );
		}
	}
}