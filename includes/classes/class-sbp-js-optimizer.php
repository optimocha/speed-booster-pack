<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_JS_Optimizer extends SBP_Abstract_Module {
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
		'adsbygoogle',
	];

	private $comments = [];
	private $all_scripts = []; // scripts that doesn't have defer attribute
	private $included_scripts = [];
	private $changed_scripts = [];
	private $comment_placeholder = '<!-- SBP/JS_Optimizer_Comment_Placeholder -->';
	private $exclude_rules = [];
	private $optimize_strategy = 'off';

	public function __construct() {
		$this->optimize_strategy = sbp_get_option( 'js_optimize' );

		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_assets' ) || $this->optimize_strategy == 'off' ) {
			return;
		}

		$this->exclude_rules = array_merge( SBP_Utils::explode_lines( sbp_get_option( 'js_exclude' ) ), $this->default_excludes );

		add_filter( 'sbp_output_buffer', [ $this, 'optimize_scripts' ] );
	}

	public function optimize_scripts( $html ) {
		$this->replace_comments_with_placeholders( $html );
		$this->find_scripts_without_defer( $html );
		$this->check_script_types();
		$this->remove_excluded_scripts();

		if ( $this->optimize_strategy == 'move' ) {
			$this->move_scripts( $html );
		} else if ( $this->optimize_strategy == 'defer' ) {
			$this->add_defer_attribute();
			$this->convert_inline_to_base64();
			$html = str_replace( $this->included_scripts, $this->changed_scripts, $html );
		}

		$this->replace_placeholders_with_comments( $html );

		return $html;
	}

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

	private function replace_placeholders_with_comments( &$html ) {
		foreach ( $this->comments as $comment ) {
			$pos = strpos( $html, $this->comment_placeholder );
			if ( $pos !== false ) {
				$html = substr_replace( $html, $comment, $pos, strlen( $this->comment_placeholder ) );
			}
		}
	}

	private function find_scripts_without_defer( &$html ) {
		preg_match_all( '/<script(((?!\bdefer\b).)*?)>(?:(.*?))<\/script>/mis', $html, $scripts );
		if ( count( $scripts[0] ) ) {
			$this->all_scripts = $scripts[0];
		}
	}

	private function check_script_types() {
		foreach ( $this->all_scripts as $script ) {
			preg_match( '/<script[\s\S]*?type=[\'|"](.*?)[\'|"][\s\S]*?>/im', $script, $result );
			// If type is not exists or type is in SCRIPT_TYPES constant, then add scripts to running scripts
			if ( count( $result ) == 0 ) {
				$this->included_scripts[] = $script;
			} else {
				$type = trim( str_replace( [ '"', "'" ], '', $result[1] ) );
				if ( in_array( $type, self::SCRIPT_TYPES ) ) {
					$this->included_scripts[] = $script;
				}
			}
		}
	}

	private function remove_excluded_scripts() {
		$script_count = count( $this->included_scripts );
		for ( $i = 0; $i < $script_count; $i ++ ) {
			foreach ( $this->exclude_rules as $rule ) {
				if ( strpos( $this->included_scripts[ $i ], $rule ) !== false ) {
					unset( $this->included_scripts[ $i ] );
				}
			}
		}
	}

	private function move_scripts( &$html ) {
		foreach ( $this->included_scripts as $script ) {
			$html = str_ireplace( $script, '', $html );
		}

		$html = str_ireplace( '</body>', implode( PHP_EOL, $this->included_scripts ) . PHP_EOL . '</body>', $html );
	}

	private function add_defer_attribute() {
		foreach ( $this->included_scripts as $script ) {
			$this->changed_scripts[] = str_ireplace( '<script', '<script defer', $script );
		}
	}

	private function convert_inline_to_base64() {
		foreach ( $this->changed_scripts as &$script ) {
			preg_match( '/<script((?:(?!src=).)*?)>(.*?)<\/script>/mis', $script, $matches );
			if ( isset( $matches[2] ) ) {
				$script_content = $matches[2];
				$base64_script  = base64_encode( $script_content );
				$script         = str_replace( $script_content, '', $script );
				$script         = str_replace( '<script defer', '<script defer src="data:text/javascript;base64,' . $base64_script . '"', $script );
			}
		}
	}
}