<?php
/**
 * Booking Custom Post Type
 *
 * @package Holiday_Let_Booking
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HLB_Booking_Post_Type {
    
    /**
     * Register custom post type and taxonomies
     */
    public static function register() {
        self::register_post_type();
        self::register_post_statuses();
    }
    
    /**
     * Register the booking post type
     */
    private static function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Bookings', 'Post Type General Name', 'holiday-let-booking' ),
            'singular_name'         => _x( 'Booking', 'Post Type Singular Name', 'holiday-let-booking' ),
            'menu_name'             => __( 'Bookings', 'holiday-let-booking' ),
            'name_admin_bar'        => __( 'Booking', 'holiday-let-booking' ),
            'archives'              => __( 'Booking Archives', 'holiday-let-booking' ),
            'attributes'            => __( 'Booking Attributes', 'holiday-let-booking' ),
            'parent_item_colon'     => __( 'Parent Booking:', 'holiday-let-booking' ),
            'all_items'             => __( 'All Bookings', 'holiday-let-booking' ),
            'add_new_item'          => __( 'Add New Booking', 'holiday-let-booking' ),
            'add_new'               => __( 'Add New', 'holiday-let-booking' ),
            'new_item'              => __( 'New Booking', 'holiday-let-booking' ),
            'edit_item'             => __( 'Edit Booking', 'holiday-let-booking' ),
            'update_item'           => __( 'Update Booking', 'holiday-let-booking' ),
            'view_item'             => __( 'View Booking', 'holiday-let-booking' ),
            'view_items'            => __( 'View Bookings', 'holiday-let-booking' ),
            'search_items'          => __( 'Search Booking', 'holiday-let-booking' ),
            'not_found'             => __( 'Not found', 'holiday-let-booking' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'holiday-let-booking' ),
            'featured_image'        => __( 'Featured Image', 'holiday-let-booking' ),
            'set_featured_image'    => __( 'Set featured image', 'holiday-let-booking' ),
            'remove_featured_image' => __( 'Remove featured image', 'holiday-let-booking' ),
            'use_featured_image'    => __( 'Use as featured image', 'holiday-let-booking' ),
            'insert_into_item'      => __( 'Insert into booking', 'holiday-let-booking' ),
            'uploaded_to_this_item' => __( 'Uploaded to this booking', 'holiday-let-booking' ),
            'items_list'            => __( 'Bookings list', 'holiday-let-booking' ),
            'items_list_navigation' => __( 'Bookings list navigation', 'holiday-let-booking' ),
            'filter_items_list'     => __( 'Filter bookings list', 'holiday-let-booking' ),
        );
        
        $args = array(
            'label'                 => __( 'Booking', 'holiday-let-booking' ),
            'description'           => __( 'Holiday let bookings', 'holiday-let-booking' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => false,
            'map_meta_cap'          => true,
        );
        
        register_post_type( 'hlb_booking', $args );
    }
    
    /**
     * Register custom post statuses for bookings
     */
    private static function register_post_statuses() {
        // Pending status
        register_post_status( 'hlb-pending', array(
            'label'                     => _x( 'Pending', 'booking status', 'holiday-let-booking' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Pending <span class="count">(%s)</span>',
                'Pending <span class="count">(%s)</span>',
                'holiday-let-booking'
            ),
        ) );
        
        // Confirmed status
        register_post_status( 'hlb-confirmed', array(
            'label'                     => _x( 'Confirmed', 'booking status', 'holiday-let-booking' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Confirmed <span class="count">(%s)</span>',
                'Confirmed <span class="count">(%s)</span>',
                'holiday-let-booking'
            ),
        ) );
        
        // Paid status
        register_post_status( 'hlb-paid', array(
            'label'                     => _x( 'Paid', 'booking status', 'holiday-let-booking' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Paid <span class="count">(%s)</span>',
                'Paid <span class="count">(%s)</span>',
                'holiday-let-booking'
            ),
        ) );
        
        // Cancelled status
        register_post_status( 'hlb-cancelled', array(
            'label'                     => _x( 'Cancelled', 'booking status', 'holiday-let-booking' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Cancelled <span class="count">(%s)</span>',
                'Cancelled <span class="count">(%s)</span>',
                'holiday-let-booking'
            ),
        ) );
        
        // Completed status
        register_post_status( 'hlb-completed', array(
            'label'                     => _x( 'Completed', 'booking status', 'holiday-let-booking' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Completed <span class="count">(%s)</span>',
                'Completed <span class="count">(%s)</span>',
                'holiday-let-booking'
            ),
        ) );
    }
    
    /**
     * Get available booking statuses
     */
    public static function get_statuses() {
        return array(
            'hlb-pending'    => __( 'Pending', 'holiday-let-booking' ),
            'hlb-confirmed'  => __( 'Confirmed', 'holiday-let-booking' ),
            'hlb-paid'       => __( 'Paid', 'holiday-let-booking' ),
            'hlb-cancelled'  => __( 'Cancelled', 'holiday-let-booking' ),
            'hlb-completed'  => __( 'Completed', 'holiday-let-booking' ),
        );
    }
    
    /**
     * Get status color for admin display
     */
    public static function get_status_color( $status ) {
        $colors = array(
            'hlb-pending'    => '#f0ad4e',
            'hlb-confirmed'  => '#5bc0de',
            'hlb-paid'       => '#5cb85c',
            'hlb-cancelled'  => '#d9534f',
            'hlb-completed'  => '#999999',
        );
        
        return isset( $colors[ $status ] ) ? $colors[ $status ] : '#999999';
    }
}
