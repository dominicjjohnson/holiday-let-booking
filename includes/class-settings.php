<?php
/**
 * Settings Handler
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Settings {
    
    public static function register() {
        register_setting( 'hlb_settings', 'hlb_winter_months' );
        register_setting( 'hlb_settings', 'hlb_cleaning_fee' );
        register_setting( 'hlb_settings', 'hlb_dog_fee' );
        register_setting( 'hlb_settings', 'hlb_currency_symbol' );
        register_setting( 'hlb_settings', 'hlb_enable_google_sheets' );
        register_setting( 'hlb_settings', 'hlb_google_api_key' );
        register_setting( 'hlb_settings', 'hlb_google_sheet_id' );
        register_setting( 'hlb_settings', 'hlb_admin_email' );
        register_setting( 'hlb_settings', 'hlb_from_email' );
        register_setting( 'hlb_settings', 'hlb_from_name' );
        register_setting( 'hlb_settings', 'hlb_enable_notifications' );
        register_setting( 'hlb_settings', 'hlb_check_in_time' );
        register_setting( 'hlb_settings', 'hlb_check_out_time' );
        register_setting( 'hlb_settings', 'hlb_tier_weekly_rates' );
        register_setting( 'hlb_settings', 'hlb_stay_type_percentages' );
        register_setting( 'hlb_settings', 'hlb_calendar_months_display' );
        register_setting( 'hlb_settings', 'hlb_conversion_id' );
        register_setting( 'hlb_settings', 'hlb_success_message' );
        register_setting( 'hlb_settings', 'hlb_error_message' );
    }
}
