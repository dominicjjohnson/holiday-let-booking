<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Public {
    
    public static function init() {
        // Will be called from main plugin file
    }
    
    public static function enqueue_scripts() {
        if ( ! is_admin() ) {
            wp_enqueue_style( 'hlb-public', HLB_PLUGIN_URL . 'public/css/booking-calendar.css', array(), HLB_VERSION );
            wp_enqueue_script( 'hlb-public', HLB_PLUGIN_URL . 'public/js/booking-calendar.js', array( 'jquery' ), HLB_VERSION, true );
            
            wp_localize_script( 'hlb-public', 'hlbAjax', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'hlb_booking_nonce' ),
            ) );
        }
    }
}
