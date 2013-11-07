
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
			* /
			activate_module : function( module_slug ) {
				var modules = _.clone( this.get( 'raw' ) );
				if ( modules[ module_slug ] && ! modules[ module_slug ].activated ) {
					modules[ module_slug ].activated = true;

					// @todo: Update server via ajaxy goodness here.

					this.set( 'raw', modules );
					this.trigger( 'change' );
				}
				return this;
			},

			/**
			 * Updates the status in the modules.raw dataset.
			 * /
			deactivate_module : function( module_slug ) {
				var modules = _.clone( this.get( 'raw' ) );
				if ( modules[ module_slug ] && modules[ module_slug ].activated ) {
					modules[ module_slug ].activated = false;

					// @todo: Update server via ajaxy goodness here.

					this.set( 'raw', modules );
					this.trigger( 'change' );
				}
				return this;
			},

			/**
			 * Load the module modal.
			 */
			render_configure : function( module_slug ) {
				var modules = this.get( 'raw' );
				if ( modules[ module_slug ] ) {
					var module = modules[ module_slug ];
					var settings_template = _.template( $( '#jetpack-configure-module-template' ).html() );
					module.settings_html = settings_template( { module: module } );
					var modal = $( _.template( $( '#jetpack-modal-template' ).html() )( { content: module.settings_html }) );
					$( document.body ).addClass('jetpack-lb').append( modal );
					//$('.jetpack-light-box').html( $( this ).closest( '.jetpack-module' ).find( '.more-info' ).html() );
					
					$('.jetpack-light-box-wrap').on( 'click', function( event ) {
						if ( $( event.target ).hasClass( 'jetpack-light-box-wrap' ) ) {
							$( document.body ).removeClass( 'jetpack-lb' ).children( '.jetpack-light-box-wrap' ).remove();
						}
					} );
					if ( ! module.settings ) {
						modal.find( '.jetpack-module-settings' ).load( 
							module.configure_url + ' #wpbody-content .wrap form',
							function( response, status, xhr ) {
								if ( status != 'error' ) {
									module.settings = response;
									modal.find( '.jetpack-module-settings form' ).submit( function(e) {
									    var postData = $(this).serializeArray();
									    var formURL = ( $(this).attr("action") ) ? $(this).attr("action") : module.configure_url ;
									    $.ajax(
									    {
									        url : formURL,
									        type: "POST",
									        data : postData,
									        success:function(data, textStatus, jqXHR) 
									        {
									            console.log( 'Settings changed' );
									        },
									        error: function(jqXHR, textStatus, errorThrown) 
									        {
									            console.log( 'Settings not changed :(' );
									        }
									    });
									    e.preventDefault();
									});
								} 
							}
						);
					}
				}
			},
			
			initialize : function() {
				this.set( 'raw', this.get( 'items' ) );
			}

		});

		return models;

})( this, jQuery, _, Backbone );
