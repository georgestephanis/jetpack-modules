
( function( window, $, items, models, views ) {
	'use strict';

	var modules, list_table, handle_module_tag_click;

	modules = new models.Modules( {
		items : items
	} );

	list_table = new views.List_Table( {
		el    : '.wp-list-table.jetpack-modules tbody',
		model : modules
	} );

	handle_module_tag_click = function( event ) {
		$('.subsubsub').find('a[data-title="' + $(this).data('title') + '"]').addClass('current')
			.closest('li').siblings().find('a.current').removeClass('current');

		event.preventDefault();
		event.data.modules.filter_and_sort();
	}

	$('.subsubsub a').on( 'click', { modules : modules }, handle_module_tag_click );

	$('.wp-list-table.jetpack-modules').on( 'click', '.module_tags a', { modules : modules }, handle_module_tag_click );

	$( '.wp-list-table.jetpack-modules' ).on( 'click', '.more-info-link', function( event ){
		event.preventDefault();
		$( this ).siblings( '.more-info' ).toggle();
	} );

} ) ( this, jQuery, window.jetpackModulesData, this.jetpackModules.models, this.jetpackModules.views );
