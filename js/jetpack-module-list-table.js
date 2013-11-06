
( function( window, $, items, models, views ) {
	'use strict';

	var modules, list_table, handle_module_tag_click, $the_table;

	$the_table = $('.wp-list-table.jetpack-modules');

	modules = new models.Modules( {
		items : items
	} );

	list_table = new views.List_Table( {
		el    : '#the-list',
		model : modules
	} );

	handle_module_tag_click = function( event ) {
		$('.subsubsub').find('a[data-title="' + $(this).data('title') + '"]').addClass('current')
			.closest('li').siblings().find('a.current').removeClass('current');

		event.preventDefault();
		event.data.modules.filter_and_sort();
	}
	$('.subsubsub a').on( 'click', { modules : modules }, handle_module_tag_click );
	$the_table.on( 'click', '.module_tags a', { modules : modules }, handle_module_tag_click );

	$the_table.on( 'click', '.row-actions .activate a', { modules : modules }, function( event ) {
		event.preventDefault();
		event.data.modules.activate_module( $(this).closest('.jetpack-module').attr('id') );
	} );

	$the_table.on( 'click', '.row-actions .delete a', { modules : modules }, function( event ) {
		event.preventDefault();
		event.data.modules.deactivate_module( $(this).closest('.jetpack-module').attr('id') );
	} );

	$the_table.on( 'click', '.more-info-link', function( event ) {
		event.preventDefault();
		$( this ).closest( '.jetpack-module' ).find( '.more-info' ).toggle();
	} );

	$the_table.on( 'click', 'a.thickbox', { modules : modules }, function( event ) {
		event.data.modules.load_modal( $(this).closest('.jetpack-module').attr('id') );
	} );

} ) ( this, jQuery, window.jetpackModulesData, this.jetpackModules.models, this.jetpackModules.views );
