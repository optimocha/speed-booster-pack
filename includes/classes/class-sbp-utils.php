<?php

class SBP_Utils {
	public static function explode_lines( $text ) {
		if ( $text === '' ) {
			return [];
		}

		return array_map( 'trim', explode( PHP_EOL, $text ) );
	}
}