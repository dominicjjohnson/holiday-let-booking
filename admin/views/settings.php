<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['hlb_save_settings'] ) ) {
    check_admin_referer( 'hlb_settings' );

    $fields = array(
        'dog_fee', 'currency_symbol',
        'enable_google_sheets', 'google_api_key', 'google_sheet_id',
        'admin_email', 'from_email', 'from_name', 'enable_notifications',
        'check_in_time', 'check_out_time', 'conversion_id',
        'success_message', 'error_message'
    );

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ 'hlb_' . $field ] ) ) {
            hlb_update_option( $field, sanitize_text_field( $_POST[ 'hlb_' . $field ] ) );
        }
    }

    // Save tier weekly rates
    if ( isset( $_POST['hlb_tier_weekly_rates'] ) && is_array( $_POST['hlb_tier_weekly_rates'] ) ) {
        $rates = array_map( 'floatval', $_POST['hlb_tier_weekly_rates'] );
        hlb_update_option( 'tier_weekly_rates', $rates );
    }

    // Save stay type percentages
    if ( isset( $_POST['hlb_stay_type_percentages'] ) && is_array( $_POST['hlb_stay_type_percentages'] ) ) {
        $pcts = array_map( 'intval', $_POST['hlb_stay_type_percentages'] );
        hlb_update_option( 'stay_type_percentages', $pcts );
    }

    // Clear pricing cache when settings change
    delete_transient( 'hlb_sheets_pricing' );
    delete_transient( 'hlb_sheets_tiers' );

    echo '<div class="notice notice-success"><p>' . __( 'Settings saved.', 'holiday-let-booking' ) . '</p></div>';
}

$tier_rates = hlb_get_option( 'tier_weekly_rates', array( 'low' => 835, 'medium' => 0, 'high' => 0, 'peak' => 0 ) );
$stay_pcts = hlb_get_option( 'stay_type_percentages', array( 'mon_fri' => 60, 'fri_mon' => 65, 'fri_sun' => 60 ) );
?>

<div class="wrap">
    <h1><?php _e( 'Holiday Let Booking Settings', 'holiday-let-booking' ); ?></h1>

    <form method="post">
        <?php wp_nonce_field( 'hlb_settings' ); ?>

        <table class="form-table">
            <tr>
                <th colspan="2"><h2><?php _e( 'Tier Weekly Rates', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label><?php _e( 'Low Season Weekly Rate', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <?php echo esc_html( hlb_get_option( 'currency_symbol', '£' ) ); ?>
                    <input type="number" name="hlb_tier_weekly_rates[low]" value="<?php echo esc_attr( $tier_rates['low'] ?? 0 ); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Medium Season Weekly Rate', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <?php echo esc_html( hlb_get_option( 'currency_symbol', '£' ) ); ?>
                    <input type="number" name="hlb_tier_weekly_rates[medium]" value="<?php echo esc_attr( $tier_rates['medium'] ?? 0 ); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'High Season Weekly Rate', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <?php echo esc_html( hlb_get_option( 'currency_symbol', '£' ) ); ?>
                    <input type="number" name="hlb_tier_weekly_rates[high]" value="<?php echo esc_attr( $tier_rates['high'] ?? 0 ); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Peak Season Weekly Rate', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <?php echo esc_html( hlb_get_option( 'currency_symbol', '£' ) ); ?>
                    <input type="number" name="hlb_tier_weekly_rates[peak]" value="<?php echo esc_attr( $tier_rates['peak'] ?? 0 ); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Stay Type Pricing (% of Weekly Rate)', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label><?php _e( 'Mon-Fri (4 nights)', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="number" name="hlb_stay_type_percentages[mon_fri]" value="<?php echo esc_attr( $stay_pcts['mon_fri'] ?? 60 ); ?>" min="0" max="100" class="small-text">%
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Fri-Mon (3 nights)', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="number" name="hlb_stay_type_percentages[fri_mon]" value="<?php echo esc_attr( $stay_pcts['fri_mon'] ?? 65 ); ?>" min="0" max="100" class="small-text">%
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Fri-Sun (2 nights)', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="number" name="hlb_stay_type_percentages[fri_sun]" value="<?php echo esc_attr( $stay_pcts['fri_sun'] ?? 60 ); ?>" min="0" max="100" class="small-text">%
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Full Week (7 nights)', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="number" name="hlb_stay_type_percentages[weekly]" value="<?php echo esc_attr( $stay_pcts['weekly'] ?? 100 ); ?>" min="0" max="200" class="small-text">%
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Other Fees', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_dog_fee"><?php _e( 'Dog Fee', 'holiday-let-booking' ); ?></label></th>
                <td><input type="number" id="hlb_dog_fee" name="hlb_dog_fee" value="<?php echo esc_attr( hlb_get_option( 'dog_fee', 35 ) ); ?>" step="0.01" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_currency_symbol"><?php _e( 'Currency Symbol', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_currency_symbol" name="hlb_currency_symbol" value="<?php echo esc_attr( hlb_get_option( 'currency_symbol', '£' ) ); ?>" class="small-text"></td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Google Sheets Integration', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_enable_google_sheets"><?php _e( 'Enable Google Sheets', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="checkbox" id="hlb_enable_google_sheets" name="hlb_enable_google_sheets" value="1" <?php checked( hlb_get_option( 'enable_google_sheets', false ), 1 ); ?>>
                    <p class="description"><?php _e( 'Sync tier assignments and bookings from Google Sheets. Sheet needs columns: Date | Tier (low, mid/medium, high, peak)', 'holiday-let-booking' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="hlb_google_api_key"><?php _e( 'Google API Key', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_google_api_key" name="hlb_google_api_key" value="<?php echo esc_attr( hlb_get_option( 'google_api_key', '' ) ); ?>" class="large-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_google_sheet_id"><?php _e( 'Google Sheet ID', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_google_sheet_id" name="hlb_google_sheet_id" value="<?php echo esc_attr( hlb_get_option( 'google_sheet_id', '' ) ); ?>" class="large-text"></td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Email Settings', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_enable_notifications"><?php _e( 'Enable Email Notifications', 'holiday-let-booking' ); ?></label></th>
                <td><input type="checkbox" id="hlb_enable_notifications" name="hlb_enable_notifications" value="1" <?php checked( hlb_get_option( 'enable_notifications', true ), 1 ); ?>></td>
            </tr>
            <tr>
                <th><label for="hlb_admin_email"><?php _e( 'Admin Email', 'holiday-let-booking' ); ?></label></th>
                <td><input type="email" id="hlb_admin_email" name="hlb_admin_email" value="<?php echo esc_attr( hlb_get_option( 'admin_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_from_email"><?php _e( 'From Email', 'holiday-let-booking' ); ?></label></th>
                <td><input type="email" id="hlb_from_email" name="hlb_from_email" value="<?php echo esc_attr( hlb_get_option( 'from_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_from_name"><?php _e( 'From Name', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_from_name" name="hlb_from_name" value="<?php echo esc_attr( hlb_get_option( 'from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Conversion Tracking', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_conversion_id"><?php _e( 'Google Ads Conversion ID', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="text" id="hlb_conversion_id" name="hlb_conversion_id" value="<?php echo esc_attr( hlb_get_option( 'conversion_id', '' ) ); ?>" class="large-text" placeholder="AW-XXXXXXXXX/XXXXXXXXXXXXXXXXXXXX">
                    <p class="description"><?php _e( 'Paste the <code>send_to</code> value from your Google Ads conversion snippet (e.g. <code>AW-881717585/7oLvCO-A84AcENHit6QD</code>). The conversion will fire automatically on successful booking with the actual booking value.', 'holiday-let-booking' ); ?></p>
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Booking Messages', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_success_message"><?php _e( 'Success Message', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="text" id="hlb_success_message" name="hlb_success_message" value="<?php echo esc_attr( hlb_get_option( 'success_message', "Booking request received! We'll be in touch within 24 hours." ) ); ?>" class="large-text">
                    <p class="description"><?php _e( 'Shown to the guest after a successful booking submission.', 'holiday-let-booking' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="hlb_error_message"><?php _e( 'General Error Message', 'holiday-let-booking' ); ?></label></th>
                <td>
                    <input type="text" id="hlb_error_message" name="hlb_error_message" value="<?php echo esc_attr( hlb_get_option( 'error_message', 'An error occurred. Please try again.' ) ); ?>" class="large-text">
                    <p class="description"><?php _e( 'Shown when a generic AJAX error occurs (network failure etc).', 'holiday-let-booking' ); ?></p>
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2><?php _e( 'Booking Settings', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_check_in_time"><?php _e( 'Check-in Time', 'holiday-let-booking' ); ?></label></th>
                <td><input type="time" id="hlb_check_in_time" name="hlb_check_in_time" value="<?php echo esc_attr( hlb_get_option( 'check_in_time', '15:00' ) ); ?>"></td>
            </tr>
            <tr>
                <th><label for="hlb_check_out_time"><?php _e( 'Check-out Time', 'holiday-let-booking' ); ?></label></th>
                <td><input type="time" id="hlb_check_out_time" name="hlb_check_out_time" value="<?php echo esc_attr( hlb_get_option( 'check_out_time', '10:00' ) ); ?>"></td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Settings', 'holiday-let-booking' ), 'primary', 'hlb_save_settings' ); ?>
    </form>
</div>
