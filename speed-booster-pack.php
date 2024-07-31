<?php
/**
 *
 * @wordpress-plugin
 * Plugin Name:       Speed Booster Pack
 * Plugin URI:        https://speedboosterpack.com
 * Description:       PageSpeed optimization is vital for SEO: A faster website equals better conversions. Optimize & cache your site with this smart plugin!
 * Version:           5.0.0
 * Author:            Optimocha
 * Author URI:        https://optimocha.com
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       speed-booster-pack
 *
 * Copyright 2015-2017 Tiguan (office@tiguandesign.com)
 * Copyright 05/05/2017 - 10/04/2017 ShortPixel (alex@shortpixel.com)
 * Copyright 2017-2019 MachoThemes (office@machothemes.com)
 * Copyright 2019-...  Optimocha (hey@optimocha.com)
 */

defined( 'ABSPATH' ) || exit;

use SpeedBoosterPack\App;
use SpeedBoosterPack\Configurations\DI;

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

//Run plugin
if ( class_exists( App::class ) ) {
	DI::container()->get( App::class )->run();
}

