<?php
/**
 * Shortcodes
 *
 * @package Holiday_Let_Booking
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HLB_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public static function init() {
        add_shortcode( 'holiday_booking_calendar', array( __CLASS__, 'calendar_shortcode' ) );
        add_shortcode( 'holiday_booking_form', array( __CLASS__, 'form_shortcode' ) );
    }
    
    /**
     * Calendar shortcode
     */
    public static function calendar_shortcode( $atts ) {
        // Default to next month (so current month is on the left)
        $next_month = new DateTime( 'first day of next month' );
        
        $atts = shortcode_atts( array(
            'month' => isset( $_GET['month'] ) ? (int) $_GET['month'] : (int) $next_month->format( 'n' ),
            'year'  => isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) $next_month->format( 'Y' ),
        ), $atts );
        
        ob_start();
        include HLB_PLUGIN_DIR . 'public/views/calendar-view.php';
        return ob_get_clean();
    }
    
    /**
     * Form shortcode
     */
    public static function form_shortcode( $atts ) {
        ob_start();
        include HLB_PLUGIN_DIR . 'public/views/booking-form.php';
        return ob_get_clean();
    }
}
