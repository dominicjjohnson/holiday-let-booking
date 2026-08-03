<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Default to next month (so current month shows on left)
$next_month = new DateTime( 'first day of next month' );
$month = isset( $_GET['month'] ) ? (int) $_GET['month'] : (int) $next_month->format( 'n' );
$year = isset( $_GET['year'] ) ? (int) $_GET['year'] : (int) $next_month->format( 'Y' );

$refresh_url = wp_nonce_url(
    add_query_arg( array( 'hlb_refresh' => 1, 'month' => $month, 'year' => $year ) ),
    'hlb_refresh_calendar',
    'hlb_refresh_nonce'
);
?>

<div class="wrap">
    <h1>
        <?php _e( 'Booking Calendar', 'holiday-let-booking' ); ?>
        <a href="<?php echo esc_url( $refresh_url ); ?>" class="page-title-action hlb-refresh-calendar">
            <span class="dashicons dashicons-update" style="vertical-align: text-bottom;"></span>
            <?php esc_html_e( 'Refresh Calendar', 'holiday-let-booking' ); ?>
        </a>
    </h1>

    <?php echo HLB_Calendar_Renderer::render( $month, $year ); ?>
</div>
