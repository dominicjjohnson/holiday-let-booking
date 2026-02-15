<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Meta_Boxes {
    
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_hlb_booking', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
    }
    
    public static function add_meta_boxes() {
        add_meta_box(
            'hlb_booking_details',
            __( 'Booking Details', 'holiday-let-booking' ),
            array( __CLASS__, 'booking_details_meta_box' ),
            'hlb_booking',
            'normal',
            'high'
        );
    }
    
    public static function booking_details_meta_box( $post ) {
        wp_nonce_field( 'hlb_save_booking', 'hlb_booking_nonce' );
        
        $check_in = get_post_meta( $post->ID, '_hlb_check_in', true );
        $check_out = get_post_meta( $post->ID, '_hlb_check_out', true );
        $guest_name = get_post_meta( $post->ID, '_hlb_guest_name', true );
        $guest_email = get_post_meta( $post->ID, '_hlb_guest_email', true );
        $guest_phone = get_post_meta( $post->ID, '_hlb_guest_phone', true );
        $has_dog = get_post_meta( $post->ID, '_hlb_has_dog', true );
        $total_price = get_post_meta( $post->ID, '_hlb_total_price', true );
        $stay_type = get_post_meta( $post->ID, '_hlb_stay_type', true );

        ?>
        <table class="form-table">
            <tr>
                <th><label for="hlb_check_in"><?php _e( 'Check-in Date', 'holiday-let-booking' ); ?></label></th>
                <td><input type="date" id="hlb_check_in" name="hlb_check_in" value="<?php echo esc_attr( $check_in ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_check_out"><?php _e( 'Check-out Date', 'holiday-let-booking' ); ?></label></th>
                <td><input type="date" id="hlb_check_out" name="hlb_check_out" value="<?php echo esc_attr( $check_out ); ?>" class="regular-text"></td>
            </tr>
            <?php if ( $stay_type ) : ?>
            <tr>
                <th><?php _e( 'Stay Type', 'holiday-let-booking' ); ?></th>
                <td><?php echo esc_html( function_exists( 'hlb_get_stay_type_label' ) ? hlb_get_stay_type_label( $stay_type ) : $stay_type ); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="hlb_guest_name"><?php _e( 'Guest Name', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_guest_name" name="hlb_guest_name" value="<?php echo esc_attr( $guest_name ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_guest_email"><?php _e( 'Guest Email', 'holiday-let-booking' ); ?></label></th>
                <td><input type="email" id="hlb_guest_email" name="hlb_guest_email" value="<?php echo esc_attr( $guest_email ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_guest_phone"><?php _e( 'Guest Phone', 'holiday-let-booking' ); ?></label></th>
                <td><input type="text" id="hlb_guest_phone" name="hlb_guest_phone" value="<?php echo esc_attr( $guest_phone ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="hlb_has_dog"><?php _e( 'Has Dog', 'holiday-let-booking' ); ?></label></th>
                <td><input type="checkbox" id="hlb_has_dog" name="hlb_has_dog" value="yes" <?php checked( $has_dog, 'yes' ); ?>></td>
            </tr>
            <tr>
                <th><label for="hlb_total_price"><?php _e( 'Total Price', 'holiday-let-booking' ); ?></label></th>
                <td><input type="number" id="hlb_total_price" name="hlb_total_price" value="<?php echo esc_attr( $total_price ); ?>" step="0.01" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }
    
    public static function save_meta_boxes( $post_id, $post ) {
        if ( ! isset( $_POST['hlb_booking_nonce'] ) || ! wp_verify_nonce( $_POST['hlb_booking_nonce'], 'hlb_save_booking' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $fields = array( 'check_in', 'check_out', 'guest_name', 'guest_email', 'guest_phone', 'has_dog', 'total_price' );
        
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ 'hlb_' . $field ] ) ) {
                update_post_meta( $post_id, '_hlb_' . $field, sanitize_text_field( $_POST[ 'hlb_' . $field ] ) );
            }
        }
    }
}
