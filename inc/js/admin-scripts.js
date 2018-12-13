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
		admin_tab_switching();
	} );

})( jQuery );