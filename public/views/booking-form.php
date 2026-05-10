<?php
/**
 * Booking Form Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="hlb-booking-form-wrapper">
    <!-- Price Display Section -->
    <div id="hlb-price-summary" class="hlb-price-summary" style="display:none;">
        <div class="hlb-price-summary-header">
            <h3><?php _e( 'Your Booking Price', 'holiday-let-booking' ); ?></h3>
        </div>
        <div id="hlb-price-content" class="hlb-price-content">
            <!-- Price details will be inserted here by JavaScript -->
        </div>
    </div>

    <header class="hlb-form-header">
        <h2><?php _e( 'Request Your Stay', 'holiday-let-booking' ); ?></h2>
        <p><?php _e( 'Fill in the details below and we\'ll get back to you within 24 hours', 'holiday-let-booking' ); ?></p>
    </header>

    <div id="hlb-booking-message" class="hlb-booking-message" style="display:none;"></div>

    <form id="hlb-booking-form" class="hlb-booking-form">
        <?php wp_nonce_field( 'hlb_booking_nonce', 'hlb_nonce' ); ?>
        <input type="hidden" id="hlb_stay_type" name="stay_type" value="">

        <div class="hlb-form-row">
            <div class="hlb-form-group">
                <label for="hlb_check_in"><?php _e( 'Check-in Date', 'holiday-let-booking' ); ?> *</label>
                <input type="date" id="hlb_check_in" name="check_in" required readonly min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" placeholder="<?php esc_attr_e( 'Select from calendar', 'holiday-let-booking' ); ?>">
            </div>

            <div class="hlb-form-group">
                <label for="hlb_check_out"><?php _e( 'Check-out Date', 'holiday-let-booking' ); ?> *</label>
                <input type="date" id="hlb_check_out" name="check_out" required readonly min="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>">
            </div>
        </div>

        <div class="hlb-form-group hlb-checkbox-group">
            <input type="checkbox" id="hlb_has_dog" name="has_dog" value="1">
            <label for="hlb_has_dog">
                <?php printf( __( 'I\'m bringing my dog <span class="hlb-additional-fee">(+%s)</span>', 'holiday-let-booking' ), hlb_format_price( hlb_get_option( 'dog_fee', 35 ) ) ); ?>
            </label>
        </div>

        <div class="hlb-form-group">
            <label for="hlb_guest_name"><?php _e( 'Your Name', 'holiday-let-booking' ); ?> *</label>
            <input type="text" id="hlb_guest_name" name="guest_name" required>
        </div>

        <div class="hlb-form-row">
            <div class="hlb-form-group">
                <label for="hlb_guest_email"><?php _e( 'Email Address', 'holiday-let-booking' ); ?> *</label>
                <input type="email" id="hlb_guest_email" name="guest_email" required>
            </div>

            <div class="hlb-form-group">
                <label for="hlb_guest_phone"><?php _e( 'Phone Number', 'holiday-let-booking' ); ?> *</label>
                <input type="tel" id="hlb_guest_phone" name="guest_phone" required>
            </div>
        </div>

        <div class="hlb-form-group">
            <label for="hlb_special_requests"><?php _e( 'Special Requests (optional)', 'holiday-let-booking' ); ?></label>
            <textarea id="hlb_special_requests" name="special_requests" rows="4" placeholder="<?php esc_attr_e( 'Any special requirements or questions?', 'holiday-let-booking' ); ?>"></textarea>
        </div>

        <button type="submit" class="hlb-submit-button">
            <?php _e( 'Request Booking', 'holiday-let-booking' ); ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </button>
    </form>

    <aside class="hlb-booking-info">
        <h3><?php _e( 'Booking Information', 'holiday-let-booking' ); ?></h3>
        <div id="hlb-booking-total-price" class="hlb-booking-total-price" style="display:none;"></div>
        <ul>
            <li><strong><?php _e( 'Check-in:', 'holiday-let-booking' ); ?></strong> <?php echo esc_html( hlb_get_option( 'check_in_time', '15:00' ) ); ?></li>
            <li><strong><?php _e( 'Check-out:', 'holiday-let-booking' ); ?></strong> <?php echo esc_html( hlb_get_option( 'check_out_time', '10:00' ) ); ?></li>
            <li><strong><?php _e( 'Deposit:', 'holiday-let-booking' ); ?></strong> <?php _e( '30% due on booking', 'holiday-let-booking' ); ?></li>
            <li><strong><?php _e( 'Balance due:', 'holiday-let-booking' ); ?></strong> <?php _e( '60 days before your holiday', 'holiday-let-booking' ); ?></li>
            <li><strong><?php _e( 'Cancellation:', 'holiday-let-booking' ); ?></strong> <?php _e( 'Full refund if we get another booking or under special circumstances', 'holiday-let-booking' ); ?></li>
            <li><strong><?php _e( 'Dog-friendly:', 'holiday-let-booking' ); ?></strong>
                <?php printf( __( 'One well-behaved dog welcome (%s fee)', 'holiday-let-booking' ), hlb_format_price( hlb_get_option( 'dog_fee', 35 ) ) ); ?>
            </li>
        </ul>
    </aside>
</div>
