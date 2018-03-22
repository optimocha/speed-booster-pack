(function( $ ) {

	'use strict';

	$( document ).ready( function() {

		postboxes.add_postbox_toggles( pagenow );

		// set cursor to pointer
		$('.postbox .hndle').css('cursor', 'pointer');


	} );

})( jQuery );