<?php
/**
 * This class is responsible for localization of the plugin.
 *
 *  Loads and defines the internationalization files for this plugin
 *  so that it is ready for translation.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined( 'ABSPATH' ) || exit;

class i18nService {
	public function __construct() {
		load_plugin_textdomain( 'speed-booster-pack' );
	}

}
