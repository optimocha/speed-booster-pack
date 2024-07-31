<?php
/**
 * Booster Service is responsible fore registering all main (caching, speed optimizations etc.) actions and filters into WordPress.
 *
 * All classes references for this service is stored in the inc/Booster folder.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SpeedBoosterPack\Booster\Cache;
use SpeedBoosterPack\Booster\CacheWarmup;
use SpeedBoosterPack\Booster\Cdn;
use SpeedBoosterPack\Booster\Cloudflare;
use SpeedBoosterPack\Booster\CompatibilityChecker;
use SpeedBoosterPack\Booster\CriticalCss;
use SpeedBoosterPack\Booster\CssMinifier;
use SpeedBoosterPack\Booster\DatabaseOptimizer;
use SpeedBoosterPack\Booster\FontOptimizer;
use SpeedBoosterPack\Booster\HtmlMinifier;
use SpeedBoosterPack\Booster\ImageDimensions;
use SpeedBoosterPack\Booster\JsOptimizer;
use SpeedBoosterPack\Booster\LazyLoader;
use SpeedBoosterPack\Booster\LiteSpeedCache;
use SpeedBoosterPack\Booster\LocalizeTracker;
use SpeedBoosterPack\Booster\Migrator;
use SpeedBoosterPack\Booster\Newsletter;
use SpeedBoosterPack\Booster\NoticeManager;
use SpeedBoosterPack\Booster\Preboost;
use SpeedBoosterPack\Booster\Sucuri;
use SpeedBoosterPack\Booster\Tweaks;
use SpeedBoosterPack\Booster\Woocommerce;
use SpeedBoosterPack\Booster\WpAdmin;
use SpeedBoosterPack\Configurations\DI;

defined( 'ABSPATH' ) || exit;

class BoosterService {
	/**
	 * List of classes have actions and filters to be registered in inc/Booster folder
	 *
	 * @var array
	 * @since 5.0.0
	 */
	private array $classes = [
		WpAdmin::class,
		DatabaseOptimizer::class,
		Newsletter::class,
		//Migrator::class,
		CompatibilityChecker::class,
		Cloudflare::class,
		Sucuri::class,
		NoticeManager::class,
		CacheWarmup::class,
		JsOptimizer::class,
		Tweaks::class,
		FontOptimizer::class,
		Preboost::class,
		Cdn::class,
		LazyLoader::class,
		CssMinifier::class,
		CriticalCss::class,
		ImageDimensions::class,
		HtmlMinifier::class,
		LocalizeTracker::class,
		Woocommerce::class,
		Cache::class,
		LiteSpeedCache::class
	];

	/**
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws Exception
	 */
	public function __construct() {
		$this->registerActionsAndFilters();
	}

	/**
	 * Register all actions and filters
	 *
	 * @throws DependencyException
	 * @throws NotFoundException
	 * @throws Exception
	 * @since 5.0.0
	 */
	public function registerActionsAndFilters() {
		foreach ( $this->classes as $class ) {
			DI::container()->get( $class );
		}
	}
}