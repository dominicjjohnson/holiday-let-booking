<?php
/**
 * Google Sheets Integration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Google_Sheets {
    
    private $api_key;
    private $sheet_id;
    private $cache_time = 300; // 5 minutes
    
    public function __construct() {
        $this->api_key = hlb_get_option( 'google_api_key', '' );
        $this->sheet_id = hlb_get_option( 'google_sheet_id', '' );
    }
    
    /**
     * Get pricing data from Google Sheets
     * Now returns tier data (date => tier_name) instead of daily prices
     */
    public function get_pricing() {
        return $this->get_tiers();
    }
    
    /**
     * Get booked dates from Google Sheets
     */
    public function get_booked_dates() {
        $cache_key = 'hlb_sheets_bookings';
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }
        
        $bookings = $this->fetch_bookings();
        
        if ( ! empty( $bookings ) ) {
            set_transient( $cache_key, $bookings, $this->cache_time );
        }
        
        return $bookings;
    }
    
    /**
     * Get tiers data from Sheets API
     * Returns array of date => tier_name
     */
    public function get_tiers() {
        $cache_key = 'hlb_sheets_tiers';
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $tiers = $this->fetch_tiers();

        if ( ! empty( $tiers ) ) {
            set_transient( $cache_key, $tiers, $this->cache_time );
        }

        return $tiers;
    }

    /**
     * Fetch tiers from Sheets API
     */
    private function fetch_tiers() {
        if ( empty( $this->api_key ) || empty( $this->sheet_id ) ) {
            return array();
        }

        $range = 'Prices!A2:B1000';
        $data = $this->fetch_sheet_data( $range );

        $tiers = array();
        if ( $data && isset( $data['values'] ) ) {
            foreach ( $data['values'] as $row ) {
                if ( count( $row ) >= 2 ) {
                    $date = $this->parse_date( $row[0] );
                    $tier = hlb_normalize_tier( $row[1] );
                    if ( $date && $tier ) {
                        $tiers[ $date ] = $tier;
                    }
                }
            }
        }

        return $tiers;
    }

    /**
     * Get tier for a specific date
     */
    public function get_tier_for_date( $date ) {
        $tiers = $this->get_tiers();
        return isset( $tiers[ $date ] ) ? $tiers[ $date ] : null;
    }

    /**
     * Fetch bookings from Sheets API
     */
    private function fetch_bookings() {
        if ( empty( $this->api_key ) || empty( $this->sheet_id ) ) {
            return array();
        }
        
        $range = 'Bookings!A2:D1000';
        $data = $this->fetch_sheet_data( $range );
        
        $booked_dates = array();
        if ( $data && isset( $data['values'] ) ) {
            foreach ( $data['values'] as $row ) {
                if ( count( $row ) >= 4 ) {
                    $check_in = $this->parse_date( $row[0] );
                    $check_out = $this->parse_date( $row[1] );
                    $status = strtolower( $row[3] ?? 'confirmed' );
                    
                    if ( ( $status === 'confirmed' || $status === 'paid' ) && $check_in && $check_out ) {
                        $dates = hlb_get_date_range( $check_in, $check_out );
                        // Exclude check-out day — guests leave that day, so it's available for new check-ins
                        array_pop( $dates );
                        $booked_dates = array_merge( $booked_dates, $dates );
                    }
                }
            }
        }
        
        return array_unique( $booked_dates );
    }
    
    /**
     * Fetch data from Google Sheets API
     */
    private function fetch_sheet_data( $range ) {
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
            $this->sheet_id,
            urlencode( $range ),
            $this->api_key
        );
        
        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'HLB Google Sheets Error: ' . $response->get_error_message() );
            return null;
        }
        
        $body = wp_remote_retrieve_body( $response );
        return json_decode( $body, true );
    }
    
    /**
     * Parse date from various formats
     */
    private function parse_date( $date_string ) {
        $date_string = trim( $date_string );
        
        $formats = array( 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d' );
        
        foreach ( $formats as $format ) {
            $date = DateTime::createFromFormat( $format, $date_string );
            if ( $date !== false ) {
                return $date->format( 'Y-m-d' );
            }
        }
        
        $timestamp = strtotime( $date_string );
        if ( $timestamp !== false ) {
            return date( 'Y-m-d', $timestamp );
        }
        
        return null;
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        delete_transient( 'hlb_sheets_pricing' );
        delete_transient( 'hlb_sheets_bookings' );
        delete_transient( 'hlb_sheets_tiers' );
    }
}
