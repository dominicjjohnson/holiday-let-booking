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
            'message'    => __( 'Booking request received! We\'ll be in touch within 24 hours.', 'holiday-let-booking' ),
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

        if ( empty( $check_in ) || empty( $check_out ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please select dates.', 'holiday-let-booking' ),
            ) );
        }

        $price_data = self::calculate_price( $check_in, $check_out, $has_dog );

        wp_send_json_success( $price_data );
    }

    /**
     * Get price preview for check-in date (2, 3, 7 nights)
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
        $day_of_week = (int) $start->format( 'N' ); // 1=Mon, 5=Fri, 7=Sun
        $is_friday = ( $day_of_week === 5 );

        // Get pricing data
        $pricing = self::get_pricing_for_dates( $check_in, date( 'Y-m-d', strtotime( $check_in . ' +14 days' ) ) );

        // Check if this is high/peak season (look at first date's tier from spreadsheet)
        $is_high_season = false;
        $tier = '';
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $tier = $sheets->get_tier_for_date( $check_in );
            $is_high_season = in_array( strtolower( $tier ), array( 'high', 'peak' ), true );
        }

        // Calculate prices for 2, 3, and 7 nights
        $prices = array();

        foreach ( array( 2, 3, 7 ) as $nights ) {
            $end_date = date( 'Y-m-d', strtotime( $check_in . ' +' . $nights . ' days' ) );
            $price_data = self::calculate_price( $check_in, $end_date, false );
            $prices[ $nights ] = $price_data['subtotal'];
        }

        wp_send_json_success( array(
            'check_in'       => $check_in,
            'day_of_week'    => $day_of_week,
            'is_friday'      => $is_friday,
            'is_high_season' => $is_high_season,
            'tier'           => $tier,
            'prices'         => $prices,
            'currency'       => hlb_get_option( 'currency_symbol', '£' ),
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
     * Calculate booking price
     */
    public static function calculate_price( $check_in, $check_out, $has_dog = false ) {
        $start = new DateTime( $check_in );
        $end = new DateTime( $check_out );
        $nights = $start->diff( $end )->days;
        $month = (int) $start->format( 'n' );
        
        $is_winter = hlb_is_winter_period( $check_in );
        
        // Get pricing (from Google Sheets or database)
        $pricing = self::get_pricing_for_dates( $check_in, $check_out );
        
        $subtotal = 0;
        $current = clone $start;
        
        while ( $current < $end ) {
            $date_str = $current->format( 'Y-m-d' );
            $daily_price = isset( $pricing[ $date_str ] ) ? $pricing[ $date_str ] : hlb_get_option( 'default_price_per_night', 100 );
            $subtotal += $daily_price;
            $current->modify( '+1 day' );
        }
        
        // Add cleaning fee for winter
        $cleaning_fee = 0;
        if ( $is_winter ) {
            $cleaning_fee = hlb_get_option( 'cleaning_fee', 150 );
        }
        
        // Add dog fee
        $dog_fee = 0;
        if ( $has_dog ) {
            $dog_fee = hlb_get_option( 'dog_fee', 35 );
        }
        
        $total = $subtotal + $cleaning_fee + $dog_fee;
        
        return array(
            'subtotal'      => $subtotal,
            'cleaning_fee'  => $cleaning_fee,
            'dog_fee'       => $dog_fee,
            'total'         => $total,
            'nights'        => $nights,
            'is_winter'     => $is_winter,
            'per_night_avg' => $nights > 0 ? round( $subtotal / $nights, 2 ) : 0,
        );
    }
    
    /**
     * Get pricing for date range
     */
    private static function get_pricing_for_dates( $start, $end ) {
        // Check if Google Sheets is enabled
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $pricing = $sheets->get_pricing();
            if ( ! empty( $pricing ) ) {
                return $pricing;
            }
        }
        
        // Fallback to database pricing
        global $wpdb;
        $table = $wpdb->prefix . 'hlb_pricing';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT price_date, price FROM {$table} WHERE price_date >= %s AND price_date <= %s AND is_available = 1",
            $start,
            $end
        ), OBJECT_K );
        
        $pricing = array();
        foreach ( $results as $date => $row ) {
            $pricing[ $date ] = (float) $row->price;
        }
        
        return $pricing;
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
     * Validate booking rules (3/4/7 nights for summer, etc.)
     */
    private static function validate_booking_rules( $check_in, $check_out ) {
        $start = new DateTime( $check_in );
        $end = new DateTime( $check_out );
        $nights = $start->diff( $end )->days;
        $is_winter = hlb_is_winter_period( $check_in );
        
        if ( $is_winter ) {
            // Winter: minimum nights check
            $min_nights = hlb_get_option( 'min_nights_winter', 1 );
            if ( $nights < $min_nights ) {
                return new WP_Error(
                    'invalid_nights',
                    sprintf(
                        __( 'Winter bookings must be at least %d night(s).', 'holiday-let-booking' ),
                        $min_nights
                    )
                );
            }
        } else {
            // Summer: must be 3, 4, or 7 nights
            $allowed_lengths = hlb_get_option( 'allowed_stay_lengths', array( 3, 4, 7 ) );
            if ( ! in_array( $nights, $allowed_lengths, true ) ) {
                return new WP_Error(
                    'invalid_nights',
                    sprintf(
                        __( 'Summer bookings must be %s nights.', 'holiday-let-booking' ),
                        implode( ', ', $allowed_lengths )
                    )
                );
            }
            
            // Check day of week rules (Friday-Monday or Monday-Friday)
            $check_in_day = (int) $start->format( 'N' ); // 1 = Monday, 5 = Friday
            
            if ( $nights === 3 && $check_in_day !== 5 ) {
                return new WP_Error(
                    'invalid_day',
                    __( '3-night stays must start on Friday.', 'holiday-let-booking' )
                );
            }
            
            if ( $nights === 4 && $check_in_day !== 1 ) {
                return new WP_Error(
                    'invalid_day',
                    __( '4-night stays must start on Monday.', 'holiday-let-booking' )
                );
            }
            
            if ( $nights === 7 && ! in_array( $check_in_day, array( 1, 5 ), true ) ) {
                return new WP_Error(
                    'invalid_day',
                    __( 'Weekly stays must start on Monday or Friday.', 'holiday-let-booking' )
                );
            }
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
            'post_author' => 1,
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
        
        return $post_id;
    }
}
