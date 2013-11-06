
this.jetpackModules = this.jetpackModules || {};

window.jetpackModules.models = (function( window, $, _, Backbone ) {
		'use strict';

		var models = {};

		models.Modules = Backbone.Model.extend({
			visibles : {},

			/**
			* Updates modules.items dataset to be a reflection of both the current
			* modules.raw data, as well as any filters or sorting that may be in effect.
			*/
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
				return this;
			},

			/**
			* Updates the status in the modules.raw dataset.
			*/
			activate_module : function( module_slug ) {
				var modules = _.clone( this.get( 'raw' ) );
				if ( modules[ module_slug ] && ! modules[ module_slug ].activated ) {
					modules[ module_slug ].activated = true;

					/* @todo: Update server via ajaxy goodness here. */

					this.set( 'raw', modules );
					this.trigger( 'change' );
				}
				return this;
			},

			/**
			 * Updates the status in the modules.raw dataset.
			 */
			deactivate_module : function( module_slug ) {
				var modules = _.clone( this.get( 'raw' ) );
				if ( modules[ module_slug ] && modules[ module_slug ].activated ) {
					modules[ module_slug ].activated = false;

					/* @todo: Update server via ajaxy goodness here. */

					this.set( 'raw', modules );
					this.trigger( 'change' );
				}
				return this;
			},

			/**
			 * Load the module modal.
			 */
			load_modal : function( module_slug ) {
				var modules = this.get( 'raw' );
				if ( modules[ module_slug ] ) {
					var module = modules[ module_slug ];
					var configure_url = module.configure_url + " #wpbody-content";
					$( "#module-settings-modal .settings" ).load( configure_url );
				}
			},

			initialize : function() {
				this.set( 'raw', this.get( 'items' ) );
			}

		});

		return models;

})( this, jQuery, _, Backbone );
