<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Optimize JavaScripts. Move JS files to footer or add defer attribute to all script tags.
 *
 * Class SBP_JS_Optimizer
 * @package SpeedBooster
 */
class SBP_JS_Optimizer extends SBP_Abstract_Module {
	/**
	 * If script tag has any other type attribute except below types, it won't be optimized.
	 */
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

	/**
	 * JS files in this list won't be optimized.
	 *
	 * @var string[] $default_excludes
	 */
	private $default_excludes = [
		'html5.js',
		'show_ads.js',
		'histats.com/js',
		'ws.amazon.com/widgets',
		'/ads/',
		'intensedebate.com',
		'scripts.chitika.net/',
		'jotform.com/',
		'gist.github.com',
		'forms.aweber.com',
		'video.unrulymedia.com',
		'stats.wp.com',
		'stats.wordpress.com',
		'widget.rafflecopter.com',
		'widget-prime.rafflecopter.com',
		'releases.flowplayer.org',
		'c.ad6media.fr',
		'cdn.stickyadstv.com',
		'www.smava.de',
		'contextual.media.net',
		'app.getresponse.com',
		'adserver.reklamstore.com',
		's0.wp.com',
		'wprp.zemanta.com',
		'files.bannersnack.com',
		'smarticon.geotrust.com',
		'js.gleam.io',
		'ir-na.amazon-adsystem.com',
		'web.ventunotech.com',
		'verify.authorize.net',
		'ads.themoneytizer.com',
		'embed.finanzcheck.de',
		'imagesrv.adition.com',
		'js.juicyads.com',
		'form.jotformeu.com',
		'speakerdeck.com',
		'content.jwplatform.com',
		'ads.investingchannel.com',
		'app.ecwid.com',
		'www.industriejobs.de',
		's.gravatar.com',
		'googlesyndication.com',
		'a.optmstr.com',
		'a.optmnstr.com',
		'a.opmnstr.com',
		'adthrive.com',
		'mediavine.com',
		'js.hsforms.net',
		'googleadservices.com',
		'f.convertkit.com',
		'recaptcha/api.js',
		'mailmunch.co',
		'apps.shareaholic.com',
		'dsms0mj1bbhn4.cloudfront.net',
		'nutrifox.com',
		'code.tidio.co',
		'www.uplaunch.com',
		'widget.reviewability.com',
		'embed-cdn.gettyimages.com/widgets.js',
		'app.mailerlite.com',
		'ck.page',
		'window.adsbygoogle',
		'google_ad_client',
		'googletag.display',
		'document.write',
		'google_ad',
		'<!--',
		'JS_Optimizer_Comment_Placeholder',
		'window.lazyLoadOptions',
	];

	/**
	 * @var array $default_includes
	 */
	private $default_includes = [];

	/**
	 * Comment lines
	 *
	 * @var array $comments
	 */
	private $comments = [];

	/**
	 * This array will keep the whole script tags in the html code except ones with defer attribute
	 * @var array $all_scripts
	 */
	private $all_scripts = []; // scripts that doesn't have defer attribute

	/**
	 * This array will keep all the scripts that's not in the exclusion list
	 * @var array $included_scripts
	 */
	private $included_scripts = [];

	/**
	 * This array will keep all the scripts that's not in the move to footer exclusion list
	 * @var array $footer_included_scripts
	 */
	private $footer_included_scripts = [];

	/**
	 * This array will keep changed versions of $included_scripts
	 * @var array $changed_scripts
	 */
	private $changed_scripts = [];

	/**
	 * Placeholder for comment lines
	 * @var string $comment_placeholder
	 */
	private $comment_placeholder = '<!-- SBP/JS_Optimizer_Comment_Placeholder -->';

	/**
	 * JavaScript exclusion rules
	 *
	 * @var array $exclude_rules
	 */
	private $exclude_rules = [];

	/**
	 * @var array $js_footer_exclude_rules
	 */
	private $js_footer_exclude_rules = [];

	/**
	 * JavaScript inclusion rules
	 *
	 * @var array $include_rules
	 */
	private $include_rules = [];

	/**
	 *
	 * @var mixed|null $js_optimize_strategy
	 */
	private $js_optimize_strategy = 'off';

	/**
	 * @var mixed|null
	 */
	private $js_footer = false;

	public function __construct() {
		parent::__construct();

		$this->js_optimize_strategy = sbp_get_option( 'js_optimize', 'off' );
		$this->js_footer            = sbp_get_option( 'js_footer' );

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			add_filter( 'sbp_output_buffer', [ $this, 'optimize_scripts' ] );
		}
	}

	public function optimize_scripts( $html ) {
		if ( ( ! sbp_get_option( 'module_assets' ) || ( $this->js_optimize_strategy == 'off' && ! $this->js_footer ) ) && ! is_singular() ) {
			return $html;
		}

		$this->js_footer_exclude_rules = array_merge( SBP_Utils::explode_lines( sbp_get_option( 'js_footer_exclude' ) ), $this->default_excludes );
		$this->exclude_rules           = array_merge( SBP_Utils::explode_lines( sbp_get_option( 'js_exclude' ) ), $this->default_excludes );
		$this->include_rules           = array_merge( SBP_Utils::explode_lines( sbp_get_option( 'js_include' ) ), $this->default_includes );

		if ( is_singular() ) {
			$js_optimization_status = sbp_get_post_meta( get_the_ID(), 'js_optimize', 'main_setting' );
			$js_footer_status       = sbp_get_post_meta( get_the_ID(), 'js_footer_status', 'main_setting' );

			if ( $js_optimization_status == 'off' || ( ! sbp_get_option( 'module_assets' ) && $js_optimization_status == 'main_setting' ) ) {
				$this->js_optimize_strategy = 'off';
			} elseif ( $js_optimization_status != 'main_setting' ) {
				$this->exclude_rules        = array_merge( SBP_Utils::explode_lines( sbp_get_post_meta( get_the_ID(), 'js_exclude' ) ), $this->default_excludes );
				$this->include_rules        = array_merge( SBP_Utils::explode_lines( sbp_get_post_meta( get_the_ID(), 'js_include' ) ), $this->default_includes );
				$this->js_optimize_strategy = sbp_get_post_meta( get_the_ID(), 'js_optimize' );
			}

			if ( $js_footer_status == 'off' || ( ! sbp_get_option( 'module_assets' ) && $js_footer_status == 'main_setting' ) ) {
				$this->js_footer = false;
			} elseif ( $js_footer_status == 'on' ) {
				$this->js_footer               = true;
				$this->js_footer_exclude_rules = array_merge( SBP_Utils::explode_lines( sbp_get_post_meta( get_the_ID(), 'js_footer_exclude' ) ), $this->default_excludes );
			}
		}

		$this->replace_comments_with_placeholders( $html );
		$this->find_scripts_without_defer( $html );
		$this->check_script_types();

		if ( $this->js_footer ) {
			$this->remove_footer_excluded_scripts();
			$this->move_scripts( $html );
		}

		if ( $this->js_optimize_strategy !== 'off' ) {
			$this->remove_excluded_scripts();
			$this->add_defer_attribute();
			$this->convert_inline_to_base64();
			$html = str_replace( $this->included_scripts, $this->changed_scripts, $html );
		}

		$this->replace_placeholders_with_comments( $html );

		return $html;
	}

	/**
	 * Replaces all comment lines with placeholders. So comment scripts won't be affected from optimization
	 *
	 * @param $html
	 */
	private function replace_comments_with_placeholders( &$html ) {
		preg_match_all( '/<!--[\s\S]*?-->/im', $html, $result );
		if ( count( $result[0] ) > 0 ) {
			$comments = $result[0];

			foreach ( $comments as $comment ) {
				$this->comments[] = $comment;
				$html             = str_replace( $comment, $this->comment_placeholder, $html );
			}
		}
	}

	/**
	 * Replaced comment lines will be reverted after JS optimization process
	 *
	 * @param $html
	 */
	private function replace_placeholders_with_comments( &$html ) {
		foreach ( $this->comments as $comment ) {
			$pos = strpos( $html, $this->comment_placeholder );
			if ( $pos !== false ) {
				$html = substr_replace( $html, $comment, $pos, strlen( $this->comment_placeholder ) );
			}
		}
	}

	/**
	 * Finds all scripts without defer attribute
	 *
	 * @param $html
	 */
	private function find_scripts_without_defer( &$html ) {
		preg_match_all( '/<script(((?!\bdefer\b).)*?)>(?:(.*?))<\/script>/mis', $html, $scripts );
		if ( count( $scripts[0] ) ) {
			$this->all_scripts = $scripts[0];
		}
	}

	/**
	 * Checks the script type. If it's in the SCRIPT_TYPES list or doesn't exists, script tag will be added to included scripts
	 */
	private function check_script_types() {
		foreach ( $this->all_scripts as $script ) {
			preg_match( '/<script[\s\S]*?type=[\'|"](.*?)[\'|"][\s\S]*?>/im', $script, $result );
			// If type is not exists or type is in SCRIPT_TYPES constant, then add scripts to running scripts
			if ( count( $result ) == 0 ) {
				$this->included_scripts[]        = $script;
				$this->footer_included_scripts[] = $script;
			} else {
				$type = trim( str_replace( [ '"', "'" ], '', $result[1] ) );
				if ( in_array( $type, self::SCRIPT_TYPES ) ) {
					$this->included_scripts[]        = $script;
					$this->footer_included_scripts[] = $script;
				}
			}
		}
	}

	/**
	 * Removes excluded script tags from included_scripts array
	 */
	private function remove_excluded_scripts() {
		$script_count = count( $this->included_scripts );
		for ( $i = 0; $i < $script_count; $i ++ ) {
			if ( $this->js_optimize_strategy == 'everything' ) {
				foreach ( $this->exclude_rules as $rule ) {
					if ( isset( $this->included_scripts[ $i ] ) ) {
						if ( strpos( $this->included_scripts[ $i ], $rule ) !== false ) {
							unset( $this->included_scripts[ $i ] );
						}
					}
				}
			} elseif ( $this->js_optimize_strategy == 'custom' ) {
				$has_found = false;
				foreach ( $this->include_rules as $rule ) {
					if ( isset( $this->included_scripts[ $i ] ) ) {
						if ( strpos( $this->included_scripts[ $i ], $rule ) !== false ) {
							$has_found = true;
							continue;
						}
					}
				}
				if ( $has_found === false ) {
					unset( $this->included_scripts[ $i ] );
				}
			}
		}
	}

	/**
	 * Removes excluded script tags from included_scripts array
	 */
	private function remove_footer_excluded_scripts() {
		$script_count = count( $this->footer_included_scripts );
		for ( $i = 0; $i < $script_count; $i ++ ) {
			foreach ( $this->js_footer_exclude_rules as $rule ) {
				if ( isset( $this->footer_included_scripts[ $i ] ) ) {
					if ( strpos( $this->footer_included_scripts[ $i ], $rule ) !== false ) {
						unset( $this->footer_included_scripts[ $i ] );
					}
				}
			}
		}
	}

	/**
	 * Removes all script tags in included_scripts array and puts them right before the </body> tag.
	 *
	 * @param $html
	 */
	private function move_scripts( &$html ) {
		foreach ( $this->footer_included_scripts as $script ) {
			$html = str_ireplace( $script, '', $html );
		}

		$html = str_ireplace( '</body>', implode( PHP_EOL, $this->footer_included_scripts ) . PHP_EOL . '</body>', $html );
	}

	private function add_defer_attribute() {
		foreach ( $this->included_scripts as $script ) {
			if ( str_ireplace( array( ' async', ' defer', 'data-noptimize="1"', 'data-cfasync="false"', 'data-pagespeed-no-defer' ), '', $script ) === $script ) {
				$this->changed_scripts[] = str_ireplace( '<script', '<script defer', $script );
			} else {
				$this->changed_scripts[] = $script;
			}
		}
	}

	private function convert_inline_to_base64() {
		foreach ( $this->changed_scripts as &$script ) {
			preg_match( '/<script((?:(?!src=).)*?)>(.*?)<\/script>/mis', $script, $matches );
			if ( isset( $matches[2] ) && str_replace( array( 'data-noptimize="1"', 'data-cfasync="false"', 'data-pagespeed-no-defer' ), '', $matches[0] ) === $matches[0] ) {
				$script_content = $matches[2];
				$base64_script  = base64_encode( $script_content );
				$script         = str_replace( $script_content, '', $script );
				$script         = str_replace( '<script defer', '<script defer src="data:text/javascript;base64,' . $base64_script . '"', $script );
			}
		}
	}
}