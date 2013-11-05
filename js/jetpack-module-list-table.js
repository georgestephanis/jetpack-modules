
(function( window, $, modules ) {

	$( '.wp-list-table.jetpack-modules' ).on( 'click', '.more-info-link', function( event ){
		event.preventDefault();
		$( this ).siblings( '.more-info' ).toggle();
	});

})( this, jQuery, jetpackModules.modules );
