/**
 * The Code
 *
 * loosely based on Matty Theme QuickSwitch
 *
 * @since 0.5.1
 */

(function (jQuery) {
	CryoutThemeSwitch = {

		add_search_box: function () {
			var searchForm = jQuery( '<li class="search-form"> <form name="cryout-themeswitch-search"> <input type="text" class="search" placeholder="Quick search..."/> </form> </li>' ).addClass( 'search-form' );

			jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( 'li#wp-admin-bar-heading-child-themes' ).after( searchForm );
				/* Make sure the search field focuses when visible. */
				jQuery( '#wp-admin-bar-cryout-themeswitch > a, #wp-admin-bar-cryout-themeswitch > a > span.ab-label, #wp-admin-bar-cryout-themeswitch [id^="wp-admin-bar-heading-"]' ).on( 'mouseover', function() {
					setTimeout(
						function ( e ) {
							jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( 'input.search' ).trigger('focus');
						}, 200);
				});
		},

		perform_search: function ( searchText ) {
			CryoutThemeSwitch.reset_results();
			if ( searchText != '' && searchText.length >= 1 ) {

				jQuery( '#wp-admin-bar-cryout-themeswitch li.the_list' ).each( function ( i ) {
					var hayStack = jQuery( this ).text().toLowerCase();
					var needle = searchText.toLowerCase();

					if ( hayStack.indexOf( needle ) == -1 ) {
						jQuery( this ).addClass( 'hide-theme' );
					}
				});
			}

		},

		reset_results: function () {
			jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( '.hide-theme' ).removeClass( 'hide-theme' );
		},

		hide_all: function () {
			jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( '.the_list' ).addClass( 'hide-theme' );
		}

	}; /* End Object */

	jQuery(document).ready(function () {

		CryoutThemeSwitch.add_search_box();

		jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( 'input.search' ).on( 'keypress', function(e) {
			/* Don't submit on Enter */
			if (e.which==13) e.preventDefault();
		});
		jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( 'input.search' ).on( 'keyup', function(e) {
			if ( jQuery( this ).val() != '' ) {
				CryoutThemeSwitch.perform_search( jQuery( this ).val() );
			} else {
				CryoutThemeSwitch.hide_all();
				jQuery( '#wp-admin-bar-cryout-themeswitch' ).find( 'input.search' ).trigger('focus');
			}
		});

		jQuery('#wp-admin-bar-cryout-themeswitch ul ul li').on( 'mouseover', function() {
		    /* .position() uses position relative to the offset parent */
		    var posy = jQuery(this).position();
			posy = posy.top;

			var posx = jQuery('#wp-admin-bar-cryout-themeswitch').position();
			posx = posx.left;

		    var width = jQuery('#wp-admin-bar-cryout-themeswitch-default').outerWidth();
			var total_left = posx + 2*width;

			var window_width = jQuery(window).width() - 400;

			if ( total_left > window_width ) {
				total_left = posx - 1.5*width;
			}

		    jQuery(this).find('.themeswitch-screenshot').css({
		        top: posy + "px",
		        left: total_left + "px"
		    })
		});

	});
})(jQuery);

/* FIN */
