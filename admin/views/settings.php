<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['hlb_save_settings'] ) ) {
    check_admin_referer( 'hlb_settings' );
    
    $fields = array(
        'winter_months', 'cleaning_fee', 'dog_fee', 'currency_symbol',
        'enable_google_sheets', 'google_api_key', 'google_sheet_id',
        'admin_email', 'from_email', 'from_name', 'enable_notifications',
        'check_in_time', 'check_out_time'
    );
    
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ 'hlb_' . $field ] ) ) {
            hlb_update_option( $field, sanitize_text_field( $_POST[ 'hlb_' . $field ] ) );
        }
    }
    
    echo '<div class="notice notice-success"><p>' . __( 'Settings saved.', 'holiday-let-booking' ) . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php _e( 'Holiday Let Booking Settings', 'holiday-let-booking' ); ?></h1>
    
    <form method="post">
        <?php wp_nonce_field( 'hlb_settings' ); ?>
        
        <table class="form-table">
            <tr>
                <th colspan="2"><h2><?php _e( 'Pricing Settings', 'holiday-let-booking' ); ?></h2></th>
            </tr>
            <tr>
                <th><label for="hlb_cleaning_fee"><?php _e( 'Winter Cleaning Fee', 'holiday-let-booking' ); ?></label></th>
                <td><input type="number" id="hlb_cleaning_fee" name="hlb_cleaning_fee" value="<?php echo esc_attr( hlb_get_option( 'cleaning_fee', 150 ) ); ?>" step="0.01" class="regular-text"></td>
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
                    <p class="description"><?php _e( 'Sync pricing and bookings from Google Sheets', 'holiday-let-booking' ); ?></p>
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
