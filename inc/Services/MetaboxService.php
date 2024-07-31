<?php
/**
 * This class is responsible for registering custom fields.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined( 'ABSPATH' ) || exit;

use CSF;
use SpeedBoosterPack\Common\Helper;

class MetaboxService {

	public function __construct() {
		add_action( 'csf_loaded', [ $this, 'createMetaboxes' ] );
	}

	public function createMetaboxes() {
		if ( function_exists( 'current_user_can' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
		}

		/* BEGIN Metaboxes */
		$metabox_prefix    = 'sbp_post_meta';
		$public_post_types = get_option( 'sbp_public_post_types' );
		if ( is_array( $public_post_types ) ) {
			CSF::createMetabox( $metabox_prefix,
				[
					'title'     => SBP_PLUGIN_NAME,
					'post_type' => $public_post_types,
				]
			);

			// BEGIN CONTENT SPECIFIC PRELOAD
			$meta_fields = [
				[
					'id'       => 'sbp_preload',
					'type'     => 'code_editor',
					'title'    => __( 'Preload rules for this content', 'speed-booster-pack' ),
					'desc'     => __( 'Enter full URLs of files to preload only for this content.', 'speed-booster-pack' ),
					'settings' => [ 'lineWrapping' => true ],
					'sanitize' => 'sbp_sanitize_strip_tags',
				],
			];

			if ( ! Helper::getOption( 'module_assets' ) || ! Helper::getOption( 'preboost' ) || ( Helper::getOption( 'preboost' ) && ! Helper::getOption( 'preboost' )['preboost_enable'] && ! Helper::getOption( 'preboost' )['preboost_enable'] ) ) {
				$meta_fields[] = [
					'id'    => 'sbp_csp_warning',
					'type'  => 'notice',
					'style' => 'warning',
					'title' => __( sprintf( 'Warning: Preloading isn\'t active in %1$s%2$s settings.%3$s', '<a href="admin.php?page=sbp-settings#tab=assets" target="_blank">', SBP_PLUGIN_NAME, '</a>' ) ),
				];
			}
			// END CONTENT SPECIFIC PRELOAD

			// BEGIN CONTENT SPECIFIC CRITICALCSS
			$meta_fields[] = [
				'id'      => 'sbp_criticalcss_status',
				'type'    => 'button_set',
				'title'   => __( 'Critical CSS for this content', 'speed-booster-pack' ),
				'options' => array(
					'main_setting' => 'Main setting',
					'off'          => 'Off',
					'custom'       => 'Custom',
				),
				'default' => 'main_setting',
				'class'   => 'sbp-gap-top',
			];

			$meta_fields[] = [
				'id'         => 'sbp_criticalcss',
				'type'       => 'code_editor',
				'desc'       => __( 'Paste the critical CSS rules generated for this exact URL.', 'speed-booster-pack' ),
				'settings'   => [ 'lineWrapping' => true ],
				'dependency' => [ 'sbp_criticalcss_status', '==', 'custom', '', 'visible' ],
				'class'      => 'meta_box_critical_css_excludes',
			];

			if ( ! Helper::getOption( 'module_css' ) || ! Helper::getOption( 'enable_criticalcss' ) ) {
				$meta_fields[] = [
					'id'    => 'sbp_criticalcss_warning',
					'type'  => 'notice',
					'style' => 'warning',
					'title' => __( sprintf( 'Warning: Critical CSS isn\'t active in %1$s%2$s settings.%3$s', '<a href="admin.php?page=sbp-settings#tab=optimize-css" target="_blank">', SBP_PLUGIN_NAME, '</a>' ) ),
				];
			}
			// END CONTENT SPECIFIC CRITICALCSS

			// BEGIN CONTENT SPECIFIC JS DEFER
			$meta_fields[] = [
				'title'   => __( 'Optimize JS for this content', 'speed-booster-pack' ),
				'id'      => 'js_optimize',
				'desc'    => __( 'Improves JavaScript loading by deferring all JS files and inline JS, avoiding render blocking issues. You can either defer everything and exclude some JS, or only defer some JS with the Custom option. Be sure what you\'re doing and use the exclude/include lists, or you might break your front-end JavaScript!', 'speed-booster-pack' ),
				'type'    => 'button_set',
				'options' => [
					'main_setting' => __( 'Main setting', 'speed-booster-pack' ),
					'off'          => __( 'Off', 'speed-booster-pack' ),
					'everything'   => __( 'Everything', 'speed-booster-pack' ),
					'custom'       => __( 'Custom', 'speed-booster-pack' ),
				],
				'default' => 'main_setting',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to exclude from deferring', 'speed-booster-pack' ),
				'id'         => 'js_exclude',
				'class'      => 'js-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from deferring.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'js_optimize', '==', 'everything', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to defer', 'speed-booster-pack' ),
				'id'         => 'js_include',
				'class'      => 'js-include',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to defer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => '',
				'dependency' => [ 'js_optimize', '==', 'custom', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];
			// END CONTENT SPECIFIC JS DEFER

			// BEGIN CONTENT SPECIFIC JS MOVE TO FOOTER
			$meta_fields[] = [
				'title'   => __( 'Move JS to footer for this content', 'speed-booster-pack' ),
				'id'      => 'js_footer_status',
				'class'   => 'js-footer',
				'type'    => 'button_set',
				'options' => [
					'main_setting' => __( 'Main setting', 'speed-booster-pack' ),
					'off'          => __( 'Off', 'speed-booster-pack' ),
					'on'           => __( 'On', 'speed-booster-pack' ),
				],
				'desc'    => __( 'Moves all JS files and inline JS to the bottom of your page sources. Has a high chance to break your website, so be sure to exclude things! If you\'re using the defer setting, you probably don\'t need to enable this.', 'speed-booster-pack' ),
				'default' => 'main_setting',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to exclude from moving to footer', 'speed-booster-pack' ),
				'id'         => 'js_footer_exclude',
				'class'      => 'js-footer-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from moving to footer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'js_footer_status', '==', 'on', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];
			// END CONTENT SPECIFIC JS MOVE TO FOOTER

			CSF::createSection( $metabox_prefix,
				array(
					'fields' => $meta_fields,
				) );
		}
	}
}