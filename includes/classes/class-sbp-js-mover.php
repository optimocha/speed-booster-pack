<?php

namespace SpeedBooster;

class SBP_JS_Mover extends SBP_Abstract_Module {
	const SCRIPT_TYPES = [
		"application/ecmascript",
		"application/javascript",
		"application/x-ecmascript",
		"application/x-javascript",
		"text/ecmascript",
		"text/javascript",
		"text/javascript1.0",
		"text/javascript1.1",
		"text/javascript1.2",
		"text/javascript1.3",
		"text/javascript1.4",
		"text/javascript1.5",
		"text/jscript",
		"text/livescript",
		"text/x-ecmascript",
		"text/x-javascript",
	];

	private $exclude_rules = [];

	private $scripts_to_move = [];

	public function __construct() {
		if ( ! $this->should_run() ) {
			return;
		}

		// TODO: This function should run before HTML minifier
		add_filter( 'sbp_output_buffer', [ $this, 'move_scripts_to_footer' ] );
	}

	private function should_run() {
		return true;
	}

	public function move_scripts_to_footer( $html ) {
		$this->exclude_rules = SBP_Utils::explode_lines( sbp_get_option( 'js_move_exceptions' ) );

		$this->get_scripts_to_move( $html );
		$this->remove_scripts_to_move( $html );

		return $html;
	}

	/**
	 * Searches for the non-excluded scripts and return them as array
	 *
	 * Steps:
	 * 1. Find all script tags and comments
	 * 2. Find types in each found script
	 * 3. Check if it's comment line or not.
	 * 4. If it's comment line, exclude it directly
	 * 5. If no type found, add script to move list
	 * 6. If type exists, check for if script type is in script types list, if it's in the list, add script to move list
	 * 7. Check for exclude rules
	 *
	 * @param $html buffer html output
	 */
	private function get_scripts_to_move( $html ) {
		preg_match_all( '/<!--[\s\S]*?-->|<script[\s\S]*?>[\s\S]*?<\/script>/im', $html, $result );
		$scripts = $result[0];

		// Check types
		foreach ( $scripts as $script ) {
			preg_match( '/<script[\s\S]*?type=[\'|"](.*?)[\'|"][\s\S]*?>/im', $script, $result );

			if ( substr( $script, 0, 4 ) != '<!--' ) {
				if ( count( $result ) == 0 ) {
					$this->scripts_to_move[] = $script;
				} else {
					$type = trim( str_replace( [ '"', "'" ], '', $result[1] ) );
					if ( in_array( $type, self::SCRIPT_TYPES ) ) { // Move scripts if only have one of the specified types
						$this->scripts_to_move[] = $script;
					}
				}
			}
		}

		$this->check_for_excludes();
	}

	/**
	 * Checks script move list to found excludes and remove them
	 * Steps:
	 * 1. Find script tags with src attributes
	 * 2. Remove line breaks and tabs
	 * 3. Loop all exclude rules
	 * 4. Check if script source includes exclude rule
	 * 5. If it includes, remove script from move list
	 * 6. Find inline scripts
	 * 7. loop exclude rules
	 * 8. Check if script includes exclude rule
	 * 9. If it includes, remove script from move list
	 */
	private function check_for_excludes( ) {
		for ( $i = 0; $i < count( $this->scripts_to_move ); $i ++ ) {
			// Check if in excluded scripts
			$script = $this->scripts_to_move[ $i ];
			$script = trim( str_replace( [ '\n', '\r' ], '', $script ) );
			// Find script tags with src
			preg_match( '/<script[\s\S]*?src=?[\'|"](.*?)[\'|"][\s\S]*?>/im', $script, $result );
			if ( isset( $result[1] ) && trim( $result[1] ) ) {
				$src = $result[1];

				$src = str_replace( [ '\r', '\n' ], '', $src );
				foreach ( $this->exclude_rules as $exclude ) {
					if ( strpos( $src, $exclude ) !== false ) {
						unset( $this->scripts_to_move[ $i ] );
					}
				}

			}

			// Find inline scripts
			preg_match( '/<script[\s\S]*?>(.*?)<\/script>/ims', $script, $result );
			if ( isset( $result[1] ) && trim( $result[1] ) ) {
				foreach ( $this->exclude_rules as $exclude ) {
					if ( substr( $exclude, 0, 1 ) !== '/' && strpos( trim( $result[1] ),
							trim( $exclude ) ) !== false ) {
						unset( $this->scripts_to_move[ $i ] );
					}
				}
			}
		}
	}

	/**
	 * Removes the found (not excluded) script tags.
	 *
	 * @param $html string buffer html output
	 */
	private function remove_scripts_to_move( &$html ) {
		foreach ( $this->scripts_to_move as $script ) {
			$html = str_ireplace( $script, '', $html );
		}

		$html = str_ireplace( '</body>', implode( PHP_EOL, $this->scripts_to_move ) . PHP_EOL . '</body>', $html );
	}
}

new SBP_JS_Mover();