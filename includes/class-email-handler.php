<?php
/**
 * Email Handler
 * Sends booking confirmations and admin notifications
 *
 * @package Holiday_Let_Booking
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HLB_Email_Handler {
    
    /**
     * Send booking confirmation to guest
     */
    public static function send_booking_confirmation( $booking_id ) {
        if ( ! hlb_get_option( 'enable_notifications', true ) ) {
            return false;
        }
        
        $booking = get_post( $booking_id );
        if ( ! $booking ) {
            return false;
        }
        
        $guest_email = get_post_meta( $booking_id, '_hlb_guest_email', true );
        $guest_name = get_post_meta( $booking_id, '_hlb_guest_name', true );
        $check_in = get_post_meta( $booking_id, '_hlb_check_in', true );
        $check_out = get_post_meta( $booking_id, '_hlb_check_out', true );
        $total_price = get_post_meta( $booking_id, '_hlb_total_price', true );
        
        $subject = sprintf(
            __( 'Booking Confirmation - %s', 'holiday-let-booking' ),
            get_bloginfo( 'name' )
        );
        
        $message = self::get_confirmation_template( array(
            'guest_name'  => $guest_name,
            'booking_id'  => $booking_id,
            'check_in'    => date( 'F j, Y', strtotime( $check_in ) ),
            'check_out'   => date( 'F j, Y', strtotime( $check_out ) ),
            'total_price' => hlb_format_price( $total_price ),
            'check_in_time' => hlb_get_option( 'check_in_time', '15:00' ),
            'check_out_time' => hlb_get_option( 'check_out_time', '10:00' ),
        ) );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . hlb_get_option( 'from_name', get_bloginfo( 'name' ) ) . ' <' . hlb_get_option( 'from_email', get_option( 'admin_email' ) ) . '>',
        );
        
        return wp_mail( $guest_email, $subject, $message, $headers );
    }
    
    /**
     * Send notification to admin
     */
    public static function send_admin_notification( $booking_id ) {
        if ( ! hlb_get_option( 'enable_notifications', true ) ) {
            return false;
        }
        
        $booking = get_post( $booking_id );
        if ( ! $booking ) {
            return false;
        }
        
        $admin_email = hlb_get_option( 'admin_email', get_option( 'admin_email' ) );
        
        $guest_name = get_post_meta( $booking_id, '_hlb_guest_name', true );
        $guest_email = get_post_meta( $booking_id, '_hlb_guest_email', true );
        $guest_phone = get_post_meta( $booking_id, '_hlb_guest_phone', true );
        $check_in = get_post_meta( $booking_id, '_hlb_check_in', true );
        $check_out = get_post_meta( $booking_id, '_hlb_check_out', true );
        $total_price = get_post_meta( $booking_id, '_hlb_total_price', true );
        $has_dog = get_post_meta( $booking_id, '_hlb_has_dog', true );
        $special_requests = get_post_meta( $booking_id, '_hlb_special_requests', true );
        
        $subject = sprintf(
            __( 'New Booking: %s - %s', 'holiday-let-booking' ),
            $guest_name,
            date( 'M j', strtotime( $check_in ) )
        );
        
        $message = self::get_admin_notification_template( array(
            'booking_id'       => $booking_id,
            'guest_name'       => $guest_name,
            'guest_email'      => $guest_email,
            'guest_phone'      => $guest_phone,
            'check_in'         => date( 'F j, Y', strtotime( $check_in ) ),
            'check_out'        => date( 'F j, Y', strtotime( $check_out ) ),
            'total_price'      => hlb_format_price( $total_price ),
            'has_dog'          => $has_dog === 'yes' ? __( 'Yes', 'holiday-let-booking' ) : __( 'No', 'holiday-let-booking' ),
            'special_requests' => $special_requests,
            'edit_link'        => admin_url( 'post.php?post=' . $booking_id . '&action=edit' ),
        ) );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );
        
        return wp_mail( $admin_email, $subject, $message, $headers );
    }
    
    /**
     * Guest confirmation email template
     */
    private static function get_confirmation_template( $data ) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #8B6F47; color: white; padding: 30px; text-align: center; }
                .content { background: #fff; padding: 30px; }
                .booking-details { background: #FAF8F5; padding: 20px; margin: 20px 0; border-left: 4px solid #8B6F47; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                h1 { margin: 0; }
                .detail-row { margin: 10px 0; }
                .label { font-weight: 600; color: #8B6F47; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e( 'Booking Confirmed!', 'holiday-let-booking' ); ?></h1>
                </div>
                
                <div class="content">
                    <p><?php printf( esc_html__( 'Dear %s,', 'holiday-let-booking' ), esc_html( $data['guest_name'] ) ); ?></p>
                    
                    <p><?php esc_html_e( 'Thank you for your booking request! We\'re excited to host you.', 'holiday-let-booking' ); ?></p>
                    
                    <div class="booking-details">
                        <h2><?php esc_html_e( 'Booking Details', 'holiday-let-booking' ); ?></h2>
                        
                        <div class="detail-row">
                            <span class="label"><?php esc_html_e( 'Booking Reference:', 'holiday-let-booking' ); ?></span>
                            #<?php echo esc_html( $data['booking_id'] ); ?>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php esc_html_e( 'Check-in:', 'holiday-let-booking' ); ?></span>
                            <?php echo esc_html( $data['check_in'] ); ?> at <?php echo esc_html( $data['check_in_time'] ); ?>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php esc_html_e( 'Check-out:', 'holiday-let-booking' ); ?></span>
                            <?php echo esc_html( $data['check_out'] ); ?> at <?php echo esc_html( $data['check_out_time'] ); ?>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label"><?php esc_html_e( 'Total Price:', 'holiday-let-booking' ); ?></span>
                            <?php echo esc_html( $data['total_price'] ); ?>
                        </div>
                    </div>
                    
                    <p><?php esc_html_e( 'We\'ll be in touch within 24 hours to confirm your booking and arrange payment.', 'holiday-let-booking' ); ?></p>
                    
                    <p><?php esc_html_e( 'If you have any questions, please don\'t hesitate to contact us.', 'holiday-let-booking' ); ?></p>
                    
                    <p><?php esc_html_e( 'Best regards,', 'holiday-let-booking' ); ?><br>
                    <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
                </div>
                
                <div class="footer">
                    <p>&copy; <?php echo esc_html( date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Admin notification email template
     */
    private static function get_admin_notification_template( $data ) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #5cb85c; color: white; padding: 20px; }
                .content { background: #fff; padding: 20px; border: 1px solid #ddd; }
                .detail-row { margin: 10px 0; padding: 10px; background: #f9f9f9; }
                .label { font-weight: 600; display: inline-block; width: 150px; }
                .button { display: inline-block; padding: 10px 20px; background: #8B6F47; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php esc_html_e( 'New Booking Received', 'holiday-let-booking' ); ?></h2>
                </div>
                
                <div class="content">
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Guest Name:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['guest_name'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Email:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['guest_email'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Phone:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['guest_phone'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Check-in:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['check_in'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Check-out:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['check_out'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Total Price:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['total_price'] ); ?>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Dog:', 'holiday-let-booking' ); ?></span>
                        <?php echo esc_html( $data['has_dog'] ); ?>
                    </div>
                    
                    <?php if ( $data['special_requests'] ) : ?>
                    <div class="detail-row">
                        <span class="label"><?php esc_html_e( 'Special Requests:', 'holiday-let-booking' ); ?></span>
                        <br><?php echo esc_html( $data['special_requests'] ); ?>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url( $data['edit_link'] ); ?>" class="button">
                        <?php esc_html_e( 'View Booking in Admin', 'holiday-let-booking' ); ?>
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
