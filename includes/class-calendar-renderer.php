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
        
        // Calculate months to display (current month first, then following months)
        $months = array();
        for ( $i = 0; $i < $num_months; $i++ ) {
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
            <?php self::render_next_period_link( $start_month, $start_year, $num_months ); ?>
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
    private static function render_month( $month_data, $booked_dates, $tiers ) {
        $month = $month_data['month'];
        $year = $month_data['year'];
        $first_day = mktime( 0, 0, 0, $month, 1, $year );
        $days_in_month = (int) date( 't', $first_day );
        $day_of_week = (int) date( 'w', $first_day );

        ?>
        <article class="hlb-calendar-month">
            <header class="hlb-month-header">
                <h3><?php echo esc_html( $month_data['name'] ); ?></h3>
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
                        $is_past = strtotime( $date ) < strtotime( 'today' );
                        $dow = (int) date( 'N', strtotime( $date ) ); // 1=Mon, 5=Fri
                        $is_start_day = ( $dow === 1 || $dow === 5 );
                        $tier = isset( $tiers[ $date ] ) ? $tiers[ $date ] : 'low';

                        $classes = array( 'hlb-calendar-day' );
                        if ( $is_booked ) {
                            $classes[] = 'hlb-booked';
                        }
                        if ( $is_past ) {
                            $classes[] = 'hlb-past';
                        }
                        if ( ! $is_booked && ! $is_past ) {
                            $classes[] = 'hlb-available';
                            if ( $is_start_day ) {
                                $classes[] = 'hlb-start-day';
                            } else {
                                $classes[] = 'hlb-non-start';
                            }
                        }

                        // Build data attributes
                        $data_attrs = ' data-date="' . esc_attr( $date ) . '"';

                        // Add hover price data for start days
                        if ( $is_start_day && $tier && ! $is_past && ! $is_booked ) {
                            $weekly_rate = hlb_get_tier_weekly_rate( $tier );
                            if ( $weekly_rate && $weekly_rate > 0 ) {
                                $hover_prices = array();
                                if ( $dow === 1 ) {
                                    $hover_prices[] = array(
                                        'label' => hlb_get_stay_type_label( 'mon_fri' ),
                                        'price' => round( $weekly_rate * hlb_get_stay_type_percentage( 'mon_fri' ), 2 ),
                                    );
                                } elseif ( $dow === 5 ) {
                                    $hover_prices[] = array(
                                        'label' => hlb_get_stay_type_label( 'fri_mon' ),
                                        'price' => round( $weekly_rate * hlb_get_stay_type_percentage( 'fri_mon' ), 2 ),
                                    );
                                    $hover_prices[] = array(
                                        'label' => hlb_get_stay_type_label( 'fri_sun' ),
                                        'price' => round( $weekly_rate * hlb_get_stay_type_percentage( 'fri_sun' ), 2 ),
                                    );
                                }
                                $data_attrs .= " data-hover-prices='" . esc_attr( wp_json_encode( $hover_prices ) ) . "'";
                            }
                        }

                        echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $data_attrs . '>';
                        echo '<time datetime="' . esc_attr( $date ) . '" class="hlb-day-number">' . esc_html( $day ) . '</time>';
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
                <p><strong><?php esc_html_e( 'Stay Options:', 'holiday-let-booking' ); ?></strong>
                <?php esc_html_e( 'Mon-Fri (4 nights), Fri-Mon (3 nights), Fri-Sun (2 nights), or Full Week (7 nights). Check in on Mondays or Fridays only.', 'holiday-let-booking' ); ?>
                </p>
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
                <span class="hlb-legend-color hlb-start-day"></span>
                <span class="hlb-legend-text"><?php esc_html_e( 'Check-in day (Mon/Fri)', 'holiday-let-booking' ); ?></span>
            </div>
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
     * Render "Next N months" navigation link below the legend
     */
    private static function render_next_period_link( $start_month, $start_year, $num_months ) {
        $next_ts    = mktime( 0, 0, 0, $start_month + $num_months, 1, $start_year );
        $next_month = (int) date( 'n', $next_ts );
        $next_year  = (int) date( 'Y', $next_ts );
        ?>
        <div class="hlb-next-period">
            <button type="button" class="hlb-next-period-btn"
                    data-month="<?php echo esc_attr( $next_month ); ?>"
                    data-year="<?php echo esc_attr( $next_year ); ?>">
                <?php printf( esc_html__( 'Next %d months', 'holiday-let-booking' ), $num_months ); ?> &rarr;
            </button>
        </div>
        <?php
    }

    /**
     * Get tier data for date range
     * Returns array( date => tier_name )
     */
    private static function get_pricing( $start, $end ) {
        if ( hlb_get_option( 'enable_google_sheets', false ) ) {
            $sheets = new HLB_Google_Sheets();
            $tiers = $sheets->get_tiers();
            if ( ! empty( $tiers ) ) {
                return $tiers;
            }
        }
        return array();
    }
}
