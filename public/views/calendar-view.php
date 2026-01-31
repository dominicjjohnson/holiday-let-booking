<?php
/**
 * Public Calendar View Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Default to next month (so current month shows on left)
$next_month = new DateTime( 'first day of next month' );
$month = isset( $atts['month'] ) ? (int) $atts['month'] : (int) $next_month->format( 'n' );
$year = isset( $atts['year'] ) ? (int) $atts['year'] : (int) $next_month->format( 'Y' );
?>

<div class="hlb-booking-wrapper">
    <?php echo HLB_Calendar_Renderer::render( $month, $year ); ?>
    
    <div class="hlb-booking-form-section">
        <?php include HLB_PLUGIN_DIR . 'public/views/booking-form.php'; ?>
    </div>
</div>
