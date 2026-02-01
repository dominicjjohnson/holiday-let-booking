<?php
/**
 * Calendar Renderer
 * Renders the booking calendar with prices and availability
 *
 * @package Holiday_Let_Booking
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HLB_Calendar_Renderer {
    
    /**
     * Render calendar for specified month(s)
     */
    public static function render( $start_month = null, $start_year = null, $num_months = 3 ) {
        if ( ! $start_month ) {
            $start_month = (int) date( 'n' );
        }
        if ( ! $start_year ) {
            $start_year = (int) date( 'Y' );
        }
        
        $num_months = hlb_get_option( 'calendar_months_display', 3 );
        
        // Calculate months to display (current month first, then next 2 months)
        $months = array();
        for ( $i = 0; $i < 3; $i++ ) {
            $timestamp = mktime( 0, 0, 0, $start_month + $i, 1, $start_year );
            $months[] = array(
                'month'     => (int) date( 'n', $timestamp ),
                'year'      => (int) date( 'Y', $timestamp ),
                'name'      => date( 'F Y', $timestamp ),
                'timestamp' => $timestamp,
            );
        }
        
        // Get booked dates for this period
        $period_start = date( 'Y-m-d', $months[0]['timestamp'] );
        $period_end = date( 'Y-m-t', $months[ count( $months ) - 1 ]['timestamp'] );
        $booked_dates = hlb_get_booked_dates( $period_start, $period_end );
        
        // Get pricing
        $pricing = self::get_pricing( $period_start, $period_end );
        
        ob_start();
        ?>
        <div class="hlb-calendar-wrapper">
            <?php self::render_navigation( $start_month, $start_year ); ?>
            
            <div class="hlb-calendar-grid">
                <?php foreach ( $months as $month_data ) : ?>
                    <?php self::render_month( $month_data, $booked_dates, $pricing ); ?>
                <?php endforeach; ?>
            </div>
            
            <?php self::render_legend(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render month navigation
     */
    private static function render_navigation( $month, $year ) {
        $prev_month = $month - 1;
        $prev_year = $year;
        if ( $prev_month < 1 ) {
            $prev_month = 12;
            $prev_year--;
        }
        
        $next_month = $month + 1;
        $next_year = $year;
        if ( $next_month > 12 ) {
            $next_month = 1;
            $next_year++;
        }
        
        ?>
        <nav class="hlb-month-navigation">
            <div class="hlb-month-selector">
                <label for="hlb-jump-month"><?php esc_html_e( 'Jump to:', 'holiday-let-booking' ); ?></label>
                <select id="hlb-jump-month" name="month" class="hlb-month-select">
                    <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                        <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $m, $month ); ?>>
                            <?php echo esc_html( date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <select id="hlb-jump-year" name="year" class="hlb-year-select">
                    <?php for ( $y = $year; $y <= $year + 2; $y++ ) : ?>
                        <option value="<?php echo esc_attr( $y ); ?>" <?php selected( $y, $year ); ?>>
                            <?php echo esc_html( $y ); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="hlb-month-arrows">
                <button type="button" class="hlb-arrow hlb-prev" data-month="<?php echo esc_attr( $prev_month ); ?>" data-year="<?php echo esc_attr( $prev_year ); ?>" aria-label="<?php esc_attr_e( 'Previous month', 'holiday-let-booking' ); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button type="button" class="hlb-arrow hlb-next" data-month="<?php echo esc_attr( $next_month ); ?>" data-year="<?php echo esc_attr( $next_year ); ?>" aria-label="<?php esc_attr_e( 'Next month', 'holiday-let-booking' ); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </nav>
        <?php
    }
    
    /**
     * Render a single month
     */
    private static function render_month( $month_data, $booked_dates, $pricing ) {
        $month = $month_data['month'];
        $year = $month_data['year'];
        $first_day = mktime( 0, 0, 0, $month, 1, $year );
        $days_in_month = (int) date( 't', $first_day );
        $day_of_week = (int) date( 'w', $first_day );
        $is_winter = hlb_is_winter_period( $first_day );
        
        ?>
        <article class="hlb-calendar-month">
            <header class="hlb-month-header">
                <h3><?php echo esc_html( $month_data['name'] ); ?></h3>
                <span class="hlb-season-badge hlb-<?php echo $is_winter ? 'winter' : 'summer'; ?>">
                    <?php echo $is_winter ? esc_html__( 'Winter Rates', 'holiday-let-booking' ) : esc_html__( 'Summer Stays', 'holiday-let-booking' ); ?>
                </span>
            </header>
            
            <div class="hlb-calendar-table">
                <div class="hlb-weekday-headers">
                    <div class="hlb-weekday"><?php esc_html_e( 'Sun', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Mon', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Tue', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Wed', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Thu', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Fri', 'holiday-let-booking' ); ?></div>
                    <div class="hlb-weekday"><?php esc_html_e( 'Sat', 'holiday-let-booking' ); ?></div>
                </div>
                
                <div class="hlb-calendar-days">
                    <?php
                    // Empty cells before first day
                    for ( $i = 0; $i < $day_of_week; $i++ ) {
                        echo '<div class="hlb-calendar-day hlb-empty"></div>';
                    }
                    
                    // Days of month
                    for ( $day = 1; $day <= $days_in_month; $day++ ) {
                        $date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
                        $is_booked = in_array( $date, $booked_dates, true );
                        $price = isset( $pricing[ $date ] ) ? $pricing[ $date ] : null;
                        $is_past = strtotime( $date ) < strtotime( 'today' );
                        
                        $classes = array( 'hlb-calendar-day' );
                        if ( $is_booked ) {
                            $classes[] = 'hlb-booked';
                        }
                        if ( $is_past ) {
                            $classes[] = 'hlb-past';
                        }
                        if ( ! $is_booked && ! $is_past ) {
                            $classes[] = 'hlb-available';
                        }
                        
                        echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-date="' . esc_attr( $date ) . '">';
                        echo '<time datetime="' . esc_attr( $date ) . '" class="hlb-day-number">' . esc_html( $day ) . '</time>';
                        
                        if ( $price && ! $is_past ) {
                            echo '<span class="hlb-day-price">' . esc_html( hlb_format_price( $price ) ) . '</span>';
                        }
                        
                        
                        echo '</div>';
                    }
                    
                    // Empty cells after last day
                    $last_day_of_week = (int) date( 'w', mktime( 0, 0, 0, $month, $days_in_month, $year ) );
                    for ( $i = $last_day_of_week; $i < 6; $i++ ) {
                        echo '<div class="hlb-calendar-day hlb-empty"></div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="hlb-booking-rules">
                <?php if ( $is_winter ) : ?>
                    <p><strong><?php esc_html_e( 'Winter Bookings:', 'holiday-let-booking' ); ?></strong> 
                    <?php
                    printf(
                        esc_html__( 'Nightly stays available. %s cleaning & admin fee applies.', 'holiday-let-booking' ),
                        hlb_format_price( hlb_get_option( 'cleaning_fee', 150 ) )
                    );
                    ?>
                    </p>
                <?php else : ?>
                    <p><strong><?php esc_html_e( 'Summer Bookings:', 'holiday-let-booking' ); ?></strong> 
                    <?php esc_html_e( '3 nights (Fri-Mon), 4 nights (Mon-Fri), or weekly stays.', 'holiday-let-booking' ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
    
    /**
     * Render legend
     */
    private static function render_legend() {
        ?>
        <div class="hlb-calendar-legend">
            <div class="hlb-legend-item">
                <span class="hlb-legend-color hlb-available"></span>
                <span class="hlb-legend-text"><?php esc_html_e( 'Available', 'holiday-let-booking' ); ?></span>
            </div>
            <div class="hlb-legend-item">
                <span class="hlb-legend-color hlb-booked"></span>
                <span class="hlb-legend-text"><?php esc_html_e( 'Booked', 'holiday-let-booking' ); ?></span>
            </div>
            <div class="hlb-legend-item">
                <span class="hlb-legend-color hlb-past"></span>
                <span class="hlb-legend-text"><?php esc_html_e( 'Past', 'holiday-let-booking' ); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get pricing for date range
     */
    private static function get_pricing( $start, $end ) {
        // Check Google Sheets first
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $pricing = $sheets->get_pricing();
            if ( ! empty( $pricing ) ) {
                return $pricing;
            }
        }
        
        // Fallback to database
        global $wpdb;
        $table = $wpdb->prefix . 'hlb_pricing';
        
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT price_date, price FROM {$table} WHERE price_date >= %s AND price_date <= %s AND is_available = 1",
            $start,
            $end
        ), OBJECT_K );
        
        $pricing = array();
        foreach ( $results as $date => $row ) {
            $pricing[ $date ] = (float) $row->price;
        }
        
        return $pricing;
    }
}
