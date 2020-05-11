<?php

namespace SpeedBooster;

class SBP_Utils extends SBP_Abstract_Module {
	public static function explode_lines( $text ) {
		if ( $text === '' ) {
			return [];
		}

		return array_map( 'trim', explode( PHP_EOL, $text ) );
	}
}