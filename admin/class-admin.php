<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Admin {
    
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
        add_action( 'admin_init', array( 'HLB_Settings', 'register' ) );
    }
    
    public static function add_menu_pages() {
        add_submenu_page(
            'edit.php?post_type=hlb_booking',
            __( 'Calendar View', 'holiday-let-booking' ),
            __( 'Calendar', 'holiday-let-booking' ),
            'manage_options',
            'hlb-calendar',
            array( __CLASS__, 'calendar_page' )
        );
        
        add_submenu_page(
            'edit.php?post_type=hlb_booking',
            __( 'Settings', 'holiday-let-booking' ),
            __( 'Settings', 'holiday-let-booking' ),
            'manage_options',
            'hlb-settings',
            array( __CLASS__, 'settings_page' )
        );
    }
    
    public static function calendar_page() {
        include HLB_PLUGIN_DIR . 'admin/views/admin-calendar.php';
    }
    
    public static function settings_page() {
        include HLB_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public static function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'hlb' ) !== false || get_post_type() === 'hlb_booking' ) {
            wp_enqueue_style( 'hlb-admin', HLB_PLUGIN_URL . 'public/css/booking-calendar.css', array(), HLB_VERSION );
        }
    }
}
