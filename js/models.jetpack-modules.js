
this.jetpackModules = this.jetpackModules || {};

window.jetpackModules.models = (function( window, $, _, Backbone ) {
		'use strict';

		var models = {};

		models.Modules = Backbone.Model.extend({
			visibles : {},

			filter_and_sort : function() {
				var subsubsub = $('.subsubsub .current');
				if ( subsubsub.closest('li').hasClass( 'all' ) ) {
					this.set( 'items', this.get( 'raw' ) );
				} else {
					var items = _.filter( this.get( 'raw' ), function( item ) {
						return _.contains( item.module_tags, subsubsub.data( 'title') );
					} );
					this.set( 'items', items );
				}
				console.log( this.attributes );
				this.trigger( 'change' );
				return this;
			},

			initialize : function() {
				this.set( 'raw', this.get( 'items' ) );
				this.filter_and_sort();
			}

		});

		return models;

})( this, jQuery, _, Backbone );
