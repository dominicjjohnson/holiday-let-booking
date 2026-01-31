<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Bookings_List {
    
    public static function init() {
        add_filter( 'manage_hlb_booking_posts_columns', array( __CLASS__, 'columns' ) );
        add_action( 'manage_hlb_booking_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
        add_filter( 'manage_edit-hlb_booking_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
    }
    
    public static function columns( $columns ) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => __( 'Booking', 'holiday-let-booking' ),
            'guest' => __( 'Guest', 'holiday-let-booking' ),
            'dates' => __( 'Dates', 'holiday-let-booking' ),
            'price' => __( 'Price', 'holiday-let-booking' ),
            'status' => __( 'Status', 'holiday-let-booking' ),
            'date' => __( 'Booked On', 'holiday-let-booking' ),
        );
        
        return $new_columns;
    }
    
    public static function column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'guest':
                $name = get_post_meta( $post_id, '_hlb_guest_name', true );
                $email = get_post_meta( $post_id, '_hlb_guest_email', true );
                echo esc_html( $name );
                if ( $email ) {
                    echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                }
                break;
                
            case 'dates':
                $check_in = get_post_meta( $post_id, '_hlb_check_in', true );
                $check_out = get_post_meta( $post_id, '_hlb_check_out', true );
                if ( $check_in && $check_out ) {
                    echo esc_html( date( 'M j', strtotime( $check_in ) ) ) . ' - ' . esc_html( date( 'M j, Y', strtotime( $check_out ) ) );
                }
                break;
                
            case 'price':
                $price = get_post_meta( $post_id, '_hlb_total_price', true );
                echo esc_html( hlb_format_price( $price ) );
                break;
                
            case 'status':
                $status = get_post_status( $post_id );
                $statuses = HLB_Booking_Post_Type::get_statuses();
                $color = HLB_Booking_Post_Type::get_status_color( $status );
                if ( isset( $statuses[ $status ] ) ) {
                    echo '<span style="display:inline-block;padding:4px 8px;background:' . esc_attr( $color ) . ';color:white;border-radius:3px;font-size:11px;">' . esc_html( $statuses[ $status ] ) . '</span>';
                }
                break;
        }
    }
    
    public static function sortable_columns( $columns ) {
        $columns['dates'] = 'check_in';
        $columns['price'] = 'price';
        return $columns;
    }
}
