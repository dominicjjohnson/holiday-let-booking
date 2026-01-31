<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Default to next month (so current month shows on left)
$next_month = new DateTime( 'first day of next month' );
$month = isset( $_GET['month'] ) ? (int) $_GET['month'] : (int) $next_month->format( 'n' );
$year = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) $next_month->format( 'Y' );
?>

<div class="wrap">
    <h1><?php _e( 'Booking Calendar', 'holiday-let-booking' ); ?></h1>
    
    <?php echo HLB_Calendar_Renderer::render( $month, $year ); ?>
</div>
