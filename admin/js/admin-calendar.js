/**
 * Admin Calendar Navigation
 * Handles the prev/next arrows, "Jump to" selects, and "Next N months" button
 * on the Bookings > Calendar admin page.
 */
jQuery(function( $ ) {
    'use strict';

    function navigateToMonth( month, year ) {
        var url = new URL( window.location.href );
        url.searchParams.set( 'month', month );
        url.searchParams.set( 'year', year );
        window.location.href = url.toString();
    }

    $( document ).on( 'click', '.hlb-arrow, .hlb-next-period-btn', function( e ) {
        e.preventDefault();
        navigateToMonth( $( this ).data( 'month' ), $( this ).data( 'year' ) );
    } );

    $( '#hlb-jump-month, #hlb-jump-year' ).on( 'change', function() {
        navigateToMonth( $( '#hlb-jump-month' ).val(), $( '#hlb-jump-year' ).val() );
    } );
} );
