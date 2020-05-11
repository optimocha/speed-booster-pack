<?php

namespace SpeedBooster;

abstract class SBP_Abstract_Module {
	public function __construct() {
		if (!$this->should_plugin_run()) {
			return;
		}
	}

	protected function should_plugin_run() {
		$page_builders = [
			"fb-edit"                       => "1", // fusion builder
			"et_fb"                         => "1", // divi builder
			"PageSpeed"                     => "off", // mod_pagespeed
			"ao_noptimize"                  => "1", // autoptimize
			"ao_noptirocket"                => "1", // autoptimize & wp rocket
			"sbp_disable"                   => "1", // speed booster pack
			"fl_builder"                    => null, // beaver builder 1
			"bt-beaverbuildertheme"         => "show", // beaver builder 2
			"ct_builder"                    => "true", // oxygen builder
			"tve"                           => "true", // thrive architect
			"preview"                       => "true", // wordpress core preview
			"customize_changeset_uuid"      => null, // wordpress core customizer
			"action"                        => "elementor", // elementor
			"ai-debug-blocks"               => "1", // ad inserter
			"tipi_builder"                  => "1", // tipi builder?
			"vc_action"                     => "vc_inline", // wpbakery page builder
			"brizy"                         => "edit", // brizy builder
			"siteorigin_panels_live_editor" => null, // siteorigin page builder
			"elementor-preview"             => null, // Elementor Preview
		];

		foreach ( $page_builders as $page_builder => $value ) {
			if ( isset( $_GET[ $page_builder ] ) && ( $value == $_GET[ $page_builder ] || null == $value ) ) {
				return false;
			}
		}

		return true;
	}
}