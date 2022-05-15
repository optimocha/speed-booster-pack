<?php 

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Static methods for creating the settings page of the plugin, using Codestar Framework.
 *
 * @package    Speed_Booster_Pack
 * @author     Optimocha <info@speedboosterpack.com>
 */
class SBP_Settings {

	public static function content( $type = 'content', $text = '', $values = [], $translation_context = '' ) {

		return [
			'type'    => $type,
			'content' => sprintf( _x( $text, $translation_context, 'speed-booster-pack' ), ...$values ),
		];

	}

	public static function submessage( $text = '', $style = 'info', $values = [], $translation_context = '' ) {

		return [
			'type'    => 'submessage',
			'style' => $style,
			'content' => sprintf( _x( $text, $translation_context, 'speed-booster-pack' ), ...$values ),
		];

	}

	public static function switcher( $title = '', $id = '', $dependency = false, $description = '', $values = [], $translation_context = '', $extra_args = [] ) {

		if( $id == '' ) {
			return [];
		}

		$switcher_array = [
			'type' => 'switcher',
			'title' => __( $title, 'speed-booster-pack' ),
			'id' => $id,
			'class' => str_replace( '_', '-', $id ),
			'dependency' => [ $dependency, '==', '1', '', 'visible' ],
			'desc' => sprintf( _x( $description, $translation_context, 'speed-booster-pack' ), ...$values ),
			'sanitize'   => 'sbp_sanitize_boolean',
		];

		if ( false === $dependency ) {
			unset( $switcher_array[ 'dependency' ] );
		}

		return array_merge( $switcher_array, $extra_args );

	}


}