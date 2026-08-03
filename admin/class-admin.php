<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Admin {
    
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
        add_action( 'admin_init', array( 'HLB_Settings', 'register' ) );
    }
    
    public static function add_menu_pages() {
        $calendar_hook = add_submenu_page(
            'edit.php?post_type=hlb_booking',
            __( 'Calendar View', 'holiday-let-booking' ),
            __( 'Calendar', 'holiday-let-booking' ),
            'manage_options',
            'hlb-calendar',
            array( __CLASS__, 'calendar_page' )
        );
        // Handle the cache-refresh redirect on load, before any admin HTML has been output.
        add_action( "load-{$calendar_hook}", array( __CLASS__, 'maybe_refresh_calendar' ) );

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

    /**
     * Clear the Google Sheets cache and redirect back to the calendar,
     * dropping the refresh args from the URL. Runs on load-{hook}, before
     * any admin HTML is sent, so redirecting here is safe.
     */
    public static function maybe_refresh_calendar() {
        if ( ! isset( $_GET['hlb_refresh'] ) ) {
            return;
        }

        check_admin_referer( 'hlb_refresh_calendar', 'hlb_refresh_nonce' );

        $sheets = new HLB_Google_Sheets();
        $sheets->clear_cache();

        $redirect_args = array( 'post_type' => 'hlb_booking', 'page' => 'hlb-calendar' );
        if ( isset( $_GET['month'] ) ) {
            $redirect_args['month'] = (int) $_GET['month'];
        }
        if ( isset( $_GET['year'] ) ) {
            $redirect_args['year'] = (int) $_GET['year'];
        }

        wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
        exit;
    }
    
    public static function settings_page() {
        include HLB_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public static function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'hlb' ) !== false || get_post_type() === 'hlb_booking' ) {
            wp_enqueue_style( 'hlb-admin', HLB_PLUGIN_URL . 'public/css/booking-calendar.css', array(), HLB_VERSION );
        }

        if ( strpos( $hook, 'page_hlb-calendar' ) !== false ) {
            wp_enqueue_script( 'hlb-admin-calendar', HLB_PLUGIN_URL . 'admin/js/admin-calendar.js', array( 'jquery' ), HLB_VERSION, true );
        }
    }
}
