/**
 * The contents of this script only gets loaded on the plugin page
 */
(function( $ ) {

	'use strict';

	/**
	 * Function used to handle admin UI postboxes
	 */
	function admin_postboxes() {

		postboxes.add_postbox_toggles( pagenow );

		// set cursor to pointer
		$( '.postbox .hndle' ).css( 'cursor', 'pointer' );
	}

	/**
	 * Function used for the image compression slider under "Image Optimization"
	 */
	function admin_jquery_sliders() {

		var slider_selector = ".sbp-slider";
		var slider_amount = ".sbp-amount";
		var slider_integer = "#sbp_integer";

		if ( $( slider_selector ).length > 0 ) {

			$( slider_selector ).slider( {
				value: jpegCompression,
				min: 0,
				max: 100,
				step: 1,
				slide: function( event, ui ) {
					jQuery( slider_amount ).val( ui.value );
					jQuery( slider_integer ).val( ui.value );
				}
			} );

			$( slider_amount ).val( $( slider_selector ).slider( "value" ) );
		}
	}

	/**
	 * Handle UI tab switching via jQuery instead of relying on CSS only
	 */
	function admin_tab_switching() {

		var nav_tab_selector = '.nav-tab-wrapper a';

		/**
		 * Default tab handling
		 */

		// make the first tab active by default
		$( nav_tab_selector + ':first' ).addClass( 'nav-tab-active' );

		// get the first tab href
		var initial_tab_href = $( nav_tab_selector + ':first' ).attr( 'href' );

		// make all the tabs, except the first one hidden
		$( '.sb-pack-tab' ).each( function( index, value ) {
			if ( '#' + $( this ).attr( 'id' ) !== initial_tab_href ) {
				$( this ).hide();
			}
		} );

		/**
		 * Listen for click events on nav-tab links
		 */
		$( nav_tab_selector ).click( function( event ) {

			$( nav_tab_selector ).removeClass( 'nav-tab-active' ); // remove class from previous selector
			$( this ).addClass( 'nav-tab-active' ).blur(); // add class to currently clicked selector

			var clicked_tab = $( this ).attr( 'href' );

			$( '.sb-pack-tab' ).each( function( index, value ) {
				if ( '#' + $( this ).attr( 'id' ) !== clicked_tab ) {
					$( this ).hide();
				}

				$( clicked_tab ).fadeIn();

			} );

			// prevent default behavior
			event.preventDefault();

		} );
	}

	$( document ).ready( function() {
		admin_postboxes();
		admin_jquery_sliders();
		admin_tab_switching();
	} );

})( jQuery );