<?php
/**
 * Booking Handler
 * Processes bookings, calculates prices, checks availability
 *
 * @package Holiday_Let_Booking
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HLB_Booking_Handler {
    
    /**
     * Submit booking via AJAX
     */
    public static function ajax_submit_booking() {
        check_ajax_referer( 'hlb_booking_nonce', 'nonce' );
        
        $check_in = sanitize_text_field( $_POST['check_in'] ?? '' );
        $check_out = sanitize_text_field( $_POST['check_out'] ?? '' );
        $guest_name = sanitize_text_field( $_POST['guest_name'] ?? '' );
        $guest_email = sanitize_email( $_POST['guest_email'] ?? '' );
        $guest_phone = sanitize_text_field( $_POST['guest_phone'] ?? '' );
        $has_dog = isset( $_POST['has_dog'] ) && $_POST['has_dog'] === 'true';
        $special_requests = sanitize_textarea_field( $_POST['special_requests'] ?? '' );
        
        // Validate required fields
        if ( empty( $check_in ) || empty( $check_out ) || empty( $guest_name ) || empty( $guest_email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please fill in all required fields.', 'holiday-let-booking' ),
            ) );
        }
        
        // Validate email
        if ( ! is_email( $guest_email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a valid email address.', 'holiday-let-booking' ),
            ) );
        }
        
        // Validate dates
        if ( strtotime( $check_out ) <= strtotime( $check_in ) ) {
            wp_send_json_error( array(
                'message' => __( 'Check-out date must be after check-in date.', 'holiday-let-booking' ),
            ) );
        }
        
        // Check availability
        if ( ! self::check_availability( $check_in, $check_out ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sorry, those dates are not available. Please select different dates.', 'holiday-let-booking' ),
            ) );
        }
        
        // Validate booking rules
        $validation = self::validate_booking_rules( $check_in, $check_out );
        if ( is_wp_error( $validation ) ) {
            wp_send_json_error( array(
                'message' => $validation->get_error_message(),
            ) );
        }
        
        // Calculate price
        $price_data = self::calculate_price( $check_in, $check_out, $has_dog );
        
        // Create booking
        $booking_id = self::create_booking( array(
            'check_in'         => $check_in,
            'check_out'        => $check_out,
            'guest_name'       => $guest_name,
            'guest_email'      => $guest_email,
            'guest_phone'      => $guest_phone,
            'has_dog'          => $has_dog,
            'special_requests' => $special_requests,
            'total_price'      => $price_data['total'],
            'price_breakdown'  => $price_data,
        ) );
        
        if ( is_wp_error( $booking_id ) ) {
            wp_send_json_error( array(
                'message' => $booking_id->get_error_message(),
            ) );
        }
        
        // Send emails
        HLB_Email_Handler::send_booking_confirmation( $booking_id );
        HLB_Email_Handler::send_admin_notification( $booking_id );
        
        wp_send_json_success( array(
            'message'    => hlb_get_option( 'success_message', __( 'Booking request received! We\'ll be in touch within 24 hours.', 'holiday-let-booking' ) ),
            'booking_id' => $booking_id,
            'price'      => $price_data,
        ) );
    }
    
    /**
     * Get price via AJAX
     */
    public static function ajax_get_price() {
        check_ajax_referer( 'hlb_booking_nonce', 'nonce' );

        $check_in = sanitize_text_field( $_POST['check_in'] ?? '' );
        $check_out = sanitize_text_field( $_POST['check_out'] ?? '' );
        $has_dog = isset( $_POST['has_dog'] ) && $_POST['has_dog'] === 'true';

        // Allow stay_type parameter to auto-calculate check-out
        if ( empty( $check_out ) && ! empty( $_POST['stay_type'] ) ) {
            $stay_type = sanitize_text_field( $_POST['stay_type'] );
            $nights_map = array( 'mon_fri' => 4, 'fri_mon' => 3, 'fri_sun' => 2 );
            if ( isset( $nights_map[ $stay_type ] ) ) {
                $check_out = date( 'Y-m-d', strtotime( $check_in . ' +' . $nights_map[ $stay_type ] . ' days' ) );
            }
        }

        if ( empty( $check_in ) || empty( $check_out ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please select dates.', 'holiday-let-booking' ),
            ) );
        }

        $price_data = self::calculate_price( $check_in, $check_out, $has_dog );

        wp_send_json_success( $price_data );
    }

    /**
     * Get price preview for check-in date
     * Monday: shows Mon-Fri option
     * Friday: shows Fri-Mon and Fri-Sun options
     */
    public static function ajax_get_price_preview() {
        check_ajax_referer( 'hlb_booking_nonce', 'nonce' );

        $check_in = sanitize_text_field( $_POST['check_in'] ?? '' );

        if ( empty( $check_in ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please select a date.', 'holiday-let-booking' ),
            ) );
        }

        $start = new DateTime( $check_in );
        $day_of_week = (int) $start->format( 'N' ); // 1=Mon, 5=Fri

        // Get tier for this date
        $tier = 'low';
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $sheet_tier = $sheets->get_tier_for_date( $check_in );
            if ( $sheet_tier ) {
                $tier = $sheet_tier;
            }
        }

        $weekly_rate = hlb_get_tier_weekly_rate( $tier );
        $options = array();

        if ( $day_of_week === 1 ) { // Monday
            $pct = hlb_get_stay_type_percentage( 'mon_fri' );
            $options[] = array(
                'stay_type' => 'mon_fri',
                'label'     => hlb_get_stay_type_label( 'mon_fri' ),
                'nights'    => 4,
                'price'     => round( $weekly_rate * $pct, 2 ),
                'check_out' => date( 'Y-m-d', strtotime( $check_in . ' +4 days' ) ),
            );
        } elseif ( $day_of_week === 5 ) { // Friday
            $pct3 = hlb_get_stay_type_percentage( 'fri_mon' );
            $pct2 = hlb_get_stay_type_percentage( 'fri_sun' );
            $options[] = array(
                'stay_type' => 'fri_mon',
                'label'     => hlb_get_stay_type_label( 'fri_mon' ),
                'nights'    => 3,
                'price'     => round( $weekly_rate * $pct3, 2 ),
                'check_out' => date( 'Y-m-d', strtotime( $check_in . ' +3 days' ) ),
            );
            $options[] = array(
                'stay_type' => 'fri_sun',
                'label'     => hlb_get_stay_type_label( 'fri_sun' ),
                'nights'    => 2,
                'price'     => round( $weekly_rate * $pct2, 2 ),
                'check_out' => date( 'Y-m-d', strtotime( $check_in . ' +2 days' ) ),
            );
        }

        wp_send_json_success( array(
            'check_in'    => $check_in,
            'day_of_week' => $day_of_week,
            'tier'        => $tier,
            'weekly_rate' => $weekly_rate,
            'options'     => $options,
            'currency'    => hlb_get_option( 'currency_symbol', '£' ),
        ) );
    }
    
    /**
     * Check availability via AJAX
     */
    public static function ajax_check_availability() {
        check_ajax_referer( 'hlb_booking_nonce', 'nonce' );
        
        $check_in = sanitize_text_field( $_POST['check_in'] ?? '' );
        $check_out = sanitize_text_field( $_POST['check_out'] ?? '' );
        
        if ( empty( $check_in ) || empty( $check_out ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please select dates.', 'holiday-let-booking' ),
            ) );
        }
        
        $available = self::check_availability( $check_in, $check_out );
        
        wp_send_json_success( array(
            'available' => $available,
            'message'   => $available
                ? __( 'These dates are available!', 'holiday-let-booking' )
                : __( 'These dates are not available.', 'holiday-let-booking' ),
        ) );
    }
    
    /**
     * Calculate booking price using tier-based percentage model
     */
    public static function calculate_price( $check_in, $check_out, $has_dog = false ) {
        $start = new DateTime( $check_in );
        $end = new DateTime( $check_out );
        $nights = $start->diff( $end )->days;
        $day_of_week = (int) $start->format( 'N' );

        // Determine stay type
        $stay_type = hlb_determine_stay_type( $day_of_week, $nights );
        if ( ! $stay_type ) {
            return array(
                'subtotal'    => 0,
                'dog_fee'     => 0,
                'total'       => 0,
                'nights'      => $nights,
                'stay_type'   => null,
                'tier'        => null,
                'weekly_rate' => 0,
                'percentage'  => 0,
                'error'       => __( 'Invalid stay type.', 'holiday-let-booking' ),
            );
        }

        // Get tier for check-in date
        $tier = 'low';
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $sheet_tier = $sheets->get_tier_for_date( $check_in );
            if ( $sheet_tier ) {
                $tier = $sheet_tier;
            }
        }

        // Calculate price: weekly_rate * percentage
        $weekly_rate = hlb_get_tier_weekly_rate( $tier );
        $percentage = hlb_get_stay_type_percentage( $stay_type );
        $subtotal = round( $weekly_rate * $percentage, 2 );

        // Dog fee
        $dog_fee = $has_dog ? (float) hlb_get_option( 'dog_fee', 35 ) : 0;
        $total = $subtotal + $dog_fee;

        return array(
            'subtotal'    => $subtotal,
            'dog_fee'     => $dog_fee,
            'total'       => $total,
            'nights'      => $nights,
            'stay_type'   => $stay_type,
            'stay_label'  => hlb_get_stay_type_label( $stay_type ),
            'tier'        => $tier,
            'weekly_rate' => $weekly_rate,
            'percentage'  => $percentage * 100,
        );
    }
    
    /**
     * Check if dates are available
     */
    public static function check_availability( $check_in, $check_out ) {
        $booked_dates = hlb_get_booked_dates( $check_in, $check_out );
        $requested_dates = hlb_get_date_range( $check_in, $check_out );
        
        // Remove the check-out day from requested dates (it's not occupied)
        array_pop( $requested_dates );
        
        // Check for overlap
        $overlap = array_intersect( $booked_dates, $requested_dates );
        
        return empty( $overlap );
    }
    
    /**
     * Validate booking rules — only Mon-Fri (4n), Fri-Mon (3n), Fri-Sun (2n)
     */
    private static function validate_booking_rules( $check_in, $check_out ) {
        $start = new DateTime( $check_in );
        $end = new DateTime( $check_out );
        $nights = $start->diff( $end )->days;
        $day_of_week = (int) $start->format( 'N' );

        $stay_type = hlb_determine_stay_type( $day_of_week, $nights );

        if ( ! $stay_type ) {
            return new WP_Error(
                'invalid_stay',
                __( 'Only the following stay types are available: Mon-Fri (4 nights), Fri-Mon (3 nights), or Fri-Sun (2 nights). Check in on Mondays or Fridays only.', 'holiday-let-booking' )
            );
        }

        return true;
    }
    
    /**
     * Create booking post
     */
    public static function create_booking( $data ) {
        // Create post
        $post_id = wp_insert_post( array(
            'post_type'   => 'hlb_booking',
            'post_title'  => sprintf(
                '%s - %s to %s',
                $data['guest_name'],
                date( 'M j', strtotime( $data['check_in'] ) ),
                date( 'M j, Y', strtotime( $data['check_out'] ) )
            ),
            'post_status' => 'hlb-pending',
            'post_author' => get_current_user_id() ?: 1,
        ) );
        
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }
        
        // Save meta data
        update_post_meta( $post_id, '_hlb_check_in', $data['check_in'] );
        update_post_meta( $post_id, '_hlb_check_out', $data['check_out'] );
        update_post_meta( $post_id, '_hlb_guest_name', $data['guest_name'] );
        update_post_meta( $post_id, '_hlb_guest_email', $data['guest_email'] );
        update_post_meta( $post_id, '_hlb_guest_phone', $data['guest_phone'] );
        update_post_meta( $post_id, '_hlb_has_dog', $data['has_dog'] ? 'yes' : 'no' );
        update_post_meta( $post_id, '_hlb_special_requests', $data['special_requests'] );
        update_post_meta( $post_id, '_hlb_total_price', $data['total_price'] );
        update_post_meta( $post_id, '_hlb_price_breakdown', $data['price_breakdown'] );
        update_post_meta( $post_id, '_hlb_booking_date', current_time( 'mysql' ) );
        if ( isset( $data['price_breakdown']['stay_type'] ) ) {
            update_post_meta( $post_id, '_hlb_stay_type', $data['price_breakdown']['stay_type'] );
        }
        
        return $post_id;
    }
}
