<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_Javascript_Deferrer extends SBP_Abstract_Module {

	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'js_defer' ) || ! sbp_get_option( 'module_assets' ) ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'js_deferrer' ] );
	}

	public function js_deferrer( $html ) {
		if ( is_embed() ) {
			return $html;
		}

		$placeholder_tag = 'noscript';

		$defer_worker_script = "<script data-cfasync='false'>
			'use strict';

			function ultimateDeferrer() {
				setTimeout(function() {
					var getAllElementsWithAttribute = function getAllElementsWithAttribute(attribute) {
						var matchingElements = [];
						var allElements = document.getElementsByTagName('noscript');
						for (var i = 0, n = allElements.length; i < n; i++) {
							if (allElements[i].getAttribute(attribute) !== null) {
								matchingElements.push(allElements[i]);
							}
						}
						return matchingElements;
					};
					var templates = getAllElementsWithAttribute('data-sbp-defer');
					var convertToScript = function convertToScript() {
						function getElementAttrs(el) {
							return [].slice.call(el.attributes).map(function(attr) {
								return {
									name: attr.name,
									value: attr.value
								};
							});
						}
						if (templates.length > 0) {
							//grab the next item on the stack
							var old_elem = templates.shift();
							var old_elem_attributes = getElementAttrs(old_elem);
							var new_elem = document.createElement('script');
							new_elem.innerHTML = old_elem.innerText;
							for (var j = 0, o = old_elem_attributes.length; j < o; j++) {
								new_elem.setAttribute(
									old_elem_attributes[j].name,
									old_elem_attributes[j].value
								);
							}
							new_elem.removeAttribute('data-sbp-defer'); //append the script tag to the <head></head>
							old_elem.after(new_elem);
							old_elem.remove();
							//when successful, inject the next script
							if (new_elem.src) {
								new_elem.onload = function(e) {convertToScript();};
								new_elem.onerror = function(e) {convertToScript();};
							} else convertToScript();
						} else return;
					};
					convertToScript();
				}, 300);
			}

			if (window.addEventListener)
				window.addEventListener('load', ultimateDeferrer, false);
			else if (window.attachEvent) window.attachEvent('onload', ultimateDeferrer);
			else window.onload = ultimateDeferrer;
			
			</script>";

		// Change noscripts with scripts
		$html = preg_replace( '/<script(\s?)/i', '<' . $placeholder_tag . ' data-sbp-defer\1', $html );
		$html = preg_replace( '/<\/script>/i', '</' . $placeholder_tag . '>', $html );
		// $html = preg_replace( '/<noscript data-sbp-defer(.*?)src=\'(.*?)\'(.*?)>/i', '<link rel="preload" href="\2" as="script" crossorigin /><' . $placeholder_tag . ' data-sbp-defer\1src="\2"\3>', $html );
		$html = preg_replace( '/<\/body/i', PHP_EOL . $defer_worker_script . PHP_EOL . '</body', $html );

		// Remove data-sbp-defer from exluded scripts
		$exclude_rules = SBP_Utils::explode_lines( sbp_get_option( 'js_exclude' ) );
		// Steps:
		/*
		 * 1. Get inline scripts
		 * 2. Check if it's excluded
		 * 3. If not excluded, convert to base64
		 * 4. Move base64 data to src
		 * 5. Check all scripts for exclusions
		 */
		preg_match_all('/<noscript(.*?)data-sbp-defer(.*?)>(.*?)<\/noscript>/is', $html, $inline_scripts);
		return print_r($inline_scripts[0], true);

		return $html;
	}
}
/*

TODO: 
+ find a better HTML tag instead of <ins>
+ fetch ALL parameters of <noscript> tags (not just "type", add to converted <script> tags:
	!! https://stackoverflow.com/a/37471808
+ queue all new <script> tags and load them synchronously
+ only parse real HTML tags!
- check browser compat for JS:
	https://babeljs.io/repl
	https://jshint.com
	replaceWith IE'de yok (fixed by @lahmacun)
- flying pages'daki inline script encode işlemini dene: https://plugins.trac.wordpress.org/browser/flying-scripts/tags/1.1.3/html-rewrite.php
*/