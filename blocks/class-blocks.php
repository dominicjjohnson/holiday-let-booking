<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HLB_Blocks {
    
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_blocks' ) );
    }
    
    public static function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        
        register_block_type( 'holiday-let-booking/calendar', array(
            'render_callback' => array( 'HLB_Shortcodes', 'calendar_shortcode' ),
        ) );
    }
}
