<?php
/**
 * Plugin Name: Holiday Let Booking Calendar
 * Plugin URI: https://miramedia.co.uk/plugins/holiday-let-booking
 * Description: Complete booking calendar system for holiday lets with seasonal pricing, Google Sheets integration, and email notifications.
 * Version: 2.0.2
 * Author: Miramedia
 * Author URI: https://miramedia.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: holiday-let-booking
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'HLB_VERSION', '2.0.2' );
define( 'HLB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HLB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HLB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Holiday_Let_Booking {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->maybe_upgrade();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once HLB_PLUGIN_DIR . 'includes/class-booking-post-type.php';
        require_once HLB_PLUGIN_DIR . 'includes/class-booking-handler.php';
        require_once HLB_PLUGIN_DIR . 'includes/class-calendar-renderer.php';
        require_once HLB_PLUGIN_DIR . 'includes/class-email-handler.php';
        require_once HLB_PLUGIN_DIR . 'includes/class-settings.php';
        require_once HLB_PLUGIN_DIR . 'includes/class-google-sheets.php';
        
        // Admin classes
        if ( is_admin() ) {
            require_once HLB_PLUGIN_DIR . 'admin/class-admin.php';
            require_once HLB_PLUGIN_DIR . 'admin/class-bookings-list.php';
            require_once HLB_PLUGIN_DIR . 'admin/class-meta-boxes.php';
            
            // Initialize admin classes
            HLB_Meta_Boxes::init();
            HLB_Bookings_List::init();
        }
        
        // Public classes
        require_once HLB_PLUGIN_DIR . 'public/class-public.php';
        require_once HLB_PLUGIN_DIR . 'public/class-shortcodes.php';
        
        // Block editor
        require_once HLB_PLUGIN_DIR . 'blocks/class-blocks.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        // Init
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( 'HLB_Public', 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( 'HLB_Admin', 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_hlb_submit_booking', array( 'HLB_Booking_Handler', 'ajax_submit_booking' ) );
        add_action( 'wp_ajax_nopriv_hlb_submit_booking', array( 'HLB_Booking_Handler', 'ajax_submit_booking' ) );
        add_action( 'wp_ajax_hlb_get_price', array( 'HLB_Booking_Handler', 'ajax_get_price' ) );
        add_action( 'wp_ajax_nopriv_hlb_get_price', array( 'HLB_Booking_Handler', 'ajax_get_price' ) );
        add_action( 'wp_ajax_hlb_check_availability', array( 'HLB_Booking_Handler', 'ajax_check_availability' ) );
        add_action( 'wp_ajax_nopriv_hlb_check_availability', array( 'HLB_Booking_Handler', 'ajax_check_availability' ) );
        add_action( 'wp_ajax_hlb_get_price_preview', array( 'HLB_Booking_Handler', 'ajax_get_price_preview' ) );
        add_action( 'wp_ajax_nopriv_hlb_get_price_preview', array( 'HLB_Booking_Handler', 'ajax_get_price_preview' ) );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom post type
        HLB_Booking_Post_Type::register();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();

        // Clear old pricing cache
        delete_transient( 'hlb_sheets_pricing' );
        delete_transient( 'hlb_sheets_tiers' );
        delete_transient( 'hlb_sheets_bookings' );

        // v2.0 migration: force-set new pricing options if upgrading from v1
        if ( false === get_option( 'hlb_tier_weekly_rates' ) ) {
            update_option( 'hlb_tier_weekly_rates', array(
                'low'    => 835,
                'medium' => 835,
                'high'   => 835,
                'peak'   => 835,
            ) );
        }
        if ( false === get_option( 'hlb_stay_type_percentages' ) ) {
            update_option( 'hlb_stay_type_percentages', array(
                'mon_fri' => 60,
                'fri_mon' => 65,
                'fri_sun' => 60,
            ) );
        }

        // Schedule cron jobs
        if ( ! wp_next_scheduled( 'hlb_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'hlb_daily_cleanup' );
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'hlb_daily_cleanup' );
    }
    
    /**
     * Check for version upgrade and run migrations
     */
    private function maybe_upgrade() {
        $installed_version = get_option( 'hlb_version', '0' );
        if ( version_compare( $installed_version, HLB_VERSION, '<' ) ) {
            // Clear all caches
            delete_transient( 'hlb_sheets_pricing' );
            delete_transient( 'hlb_sheets_tiers' );
            delete_transient( 'hlb_sheets_bookings' );

            // Ensure new options exist
            if ( false === get_option( 'hlb_tier_weekly_rates' ) ) {
                update_option( 'hlb_tier_weekly_rates', array(
                    'low'    => 835,
                    'medium' => 835,
                    'high'   => 835,
                    'peak'   => 835,
                ) );
            }
            if ( false === get_option( 'hlb_stay_type_percentages' ) ) {
                update_option( 'hlb_stay_type_percentages', array(
                    'mon_fri' => 60,
                    'fri_mon' => 65,
                    'fri_sun' => 60,
                ) );
            }

            update_option( 'hlb_version', HLB_VERSION );
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Register custom post type
        HLB_Booking_Post_Type::register();
        
        // Initialize admin
        if ( is_admin() ) {
            HLB_Admin::init();
        }
        
        // Initialize public
        HLB_Public::init();
        
        // Initialize shortcodes
        HLB_Shortcodes::init();
        
        // Initialize blocks
        HLB_Blocks::init();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'holiday-let-booking',
            false,
            dirname( HLB_PLUGIN_BASENAME ) . '/languages'
        );
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookings cache table (optional, for performance)
        $table_name = $wpdb->prefix . 'hlb_bookings_cache';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date_from date NOT NULL,
            date_to date NOT NULL,
            booking_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY date_from (date_from),
            KEY date_to (date_to),
            KEY booking_id (booking_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        // Pricing table (optional - can use Google Sheets instead)
        $table_name = $wpdb->prefix . 'hlb_pricing';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            price_date date NOT NULL,
            price decimal(10,2) NOT NULL,
            is_available tinyint(1) DEFAULT 1,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY price_date (price_date)
        ) $charset_collate;";
        
        dbDelta( $sql );
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'hlb_dog_fee' => 35,
            'hlb_currency_symbol' => '£',
            'hlb_date_format' => 'Y-m-d',
            'hlb_time_format' => 'H:i',
            'hlb_enable_google_sheets' => false,
            'hlb_google_api_key' => '',
            'hlb_google_sheet_id' => '',
            'hlb_admin_email' => get_option( 'admin_email' ),
            'hlb_from_email' => get_option( 'admin_email' ),
            'hlb_from_name' => get_bloginfo( 'name' ),
            'hlb_enable_notifications' => true,
            'hlb_check_in_time' => '15:00',
            'hlb_check_out_time' => '10:00',
            'hlb_calendar_months_display' => 3,
            'hlb_tier_weekly_rates' => array(
                'low'    => 835,
                'medium' => 835,
                'high'   => 835,
                'peak'   => 835,
            ),
            'hlb_stay_type_percentages' => array(
                'mon_fri' => 60,
                'fri_mon' => 65,
                'fri_sun' => 60,
            ),
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function hlb_init() {
    return Holiday_Let_Booking::get_instance();
}

// Start the plugin
hlb_init();

/**
 * Helper function to get plugin settings
 */
function hlb_get_option( $key, $default = false ) {
    return get_option( 'hlb_' . $key, $default );
}

/**
 * Helper function to update plugin settings
 */
function hlb_update_option( $key, $value ) {
    return update_option( 'hlb_' . $key, $value );
}

/**
 * Helper function to format price
 */
function hlb_format_price( $amount ) {
    $symbol = hlb_get_option( 'currency_symbol', '£' );
    return $symbol . number_format( (float) $amount, 2 );
}

/**
 * Get weekly rate for a tier name
 */
function hlb_get_tier_weekly_rate( $tier ) {
    $tier = hlb_normalize_tier( $tier );
    $rates = hlb_get_option( 'tier_weekly_rates', array(
        'low'    => 835,
        'medium' => 835,
        'high'   => 835,
        'peak'   => 835,
    ) );
    return isset( $rates[ $tier ] ) ? (float) $rates[ $tier ] : null;
}

/**
 * Normalize tier name — handles aliases like "mid" => "medium"
 */
function hlb_normalize_tier( $tier ) {
    $tier = strtolower( trim( $tier ) );
    $aliases = array(
        'mid' => 'medium',
        'med' => 'medium',
        'lo'  => 'low',
        'hi'  => 'high',
    );
    return isset( $aliases[ $tier ] ) ? $aliases[ $tier ] : $tier;
}

/**
 * Get stay type percentage as a decimal (e.g. 0.60)
 */
function hlb_get_stay_type_percentage( $stay_type ) {
    $percentages = hlb_get_option( 'stay_type_percentages', array(
        'mon_fri' => 60,
        'fri_mon' => 65,
        'fri_sun' => 60,
    ) );
    return isset( $percentages[ $stay_type ] ) ? (float) $percentages[ $stay_type ] / 100 : null;
}

/**
 * Determine stay type from check-in day of week and number of nights
 * Returns 'mon_fri', 'fri_mon', 'fri_sun', or null if invalid
 */
function hlb_determine_stay_type( $check_in_dow, $nights ) {
    if ( $check_in_dow === 1 && $nights === 4 ) return 'mon_fri';
    if ( $check_in_dow === 5 && $nights === 3 ) return 'fri_mon';
    if ( $check_in_dow === 5 && $nights === 2 ) return 'fri_sun';
    return null;
}

/**
 * Get human-readable label for a stay type
 */
function hlb_get_stay_type_label( $stay_type ) {
    $labels = array(
        'mon_fri' => 'Mon-Fri (4 nights)',
        'fri_mon' => 'Fri-Mon (3 nights)',
        'fri_sun' => 'Fri-Sun (2 nights)',
    );
    return isset( $labels[ $stay_type ] ) ? $labels[ $stay_type ] : $stay_type;
}

/**
 * Format tier for display - shows weekly rate or tier name
 */
function hlb_format_tier( $tier ) {
    $rate = hlb_get_tier_weekly_rate( $tier );
    if ( $rate !== null && $rate > 0 ) {
        return hlb_format_price( $rate ) . '/wk';
    }
    return ucfirst( $tier );
}

/**
 * Helper function to get booked dates
 */
function hlb_get_booked_dates( $start_date = null, $end_date = null ) {
    $booked_dates = array();

    // Get bookings from Google Sheets if enabled
    if ( hlb_get_option( 'enable_google_sheets', false ) ) {
        $sheets = new HLB_Google_Sheets();
        $sheets_bookings = $sheets->get_booked_dates();
        if ( ! empty( $sheets_bookings ) ) {
            $booked_dates = array_merge( $booked_dates, $sheets_bookings );
        }
    }

    // Also get bookings from WordPress posts
    $args = array(
        'post_type' => 'hlb_booking',
        'post_status' => array( 'publish', 'hlb-confirmed', 'hlb-paid' ),
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
        ),
    );

    if ( $start_date ) {
        $args['meta_query'][] = array(
            'key' => '_hlb_check_out',
            'value' => $start_date,
            'compare' => '>=',
            'type' => 'DATE',
        );
    }

    if ( $end_date ) {
        $args['meta_query'][] = array(
            'key' => '_hlb_check_in',
            'value' => $end_date,
            'compare' => '<=',
            'type' => 'DATE',
        );
    }

    $bookings = get_posts( $args );

    foreach ( $bookings as $booking ) {
        $check_in = get_post_meta( $booking->ID, '_hlb_check_in', true );
        $check_out = get_post_meta( $booking->ID, '_hlb_check_out', true );

        if ( $check_in && $check_out ) {
            $dates = hlb_get_date_range( $check_in, $check_out );
            // Exclude check-out day — guests leave that day, so it's available for new check-ins
            array_pop( $dates );
            $booked_dates = array_merge( $booked_dates, $dates );
        }
    }

    return array_unique( $booked_dates );
}

/**
 * Helper function to get date range
 */
function hlb_get_date_range( $start, $end, $format = 'Y-m-d' ) {
    $dates = array();
    $current = strtotime( $start );
    $end = strtotime( $end );
    
    while ( $current <= $end ) {
        $dates[] = date( $format, $current );
        $current = strtotime( '+1 day', $current );
    }
    
    return $dates;
}







/**
 * Debug Tool for Google Sheets Integration
 * Add this to your functions.php or as a separate plugin file
 * 
 * Usage: Add [hlb_debug] shortcode to any page
 */

add_shortcode( 'hlb_debug', 'hlb_debug_google_sheets' );

function hlb_debug_google_sheets() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return '<p>Debug tool only available for administrators.</p>';
    }
    
    ob_start();
    
    $enabled = hlb_get_option( 'enable_google_sheets', false );
    $api_key = hlb_get_option( 'google_api_key', '' );
    $sheet_id = hlb_get_option( 'google_sheet_id', '' );
    
    ?>
    <div class="hlb-debug-panel" style="background: #f5f5f5; border: 2px solid #8B6F47; border-radius: 8px; padding: 2rem; margin: 2rem 0; font-family: monospace;">
        <h2 style="margin-top: 0; color: #8B6F47;">🔍 Google Sheets Debug Panel</h2>
        
        <h3>Settings</h3>
        <ul>
            <li><strong>Google Sheets Enabled:</strong> <?php echo $enabled ? '✅ Yes' : '❌ No'; ?></li>
            <li><strong>API Key:</strong> <?php echo $api_key ? '✅ Set (' . substr( $api_key, 0, 20 ) . '...)' : '❌ Not set'; ?></li>
            <li><strong>Sheet ID:</strong> <?php echo $sheet_id ? '✅ Set (' . $sheet_id . ')' : '❌ Not set'; ?></li>
        </ul>
        
        <?php if ( $enabled && $api_key && $sheet_id ) : ?>
            <h3>Testing API Connection...</h3>
            <?php
            // Test raw API call
            $range = 'Prices!A2:C1000';
            $url = sprintf(
                'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
                $sheet_id,
                urlencode( $range ),
                $api_key
            );

            echo '<h4>API Request</h4>';
            echo '<p><strong>URL:</strong> <code style="word-break: break-all;">' . esc_html( $url ) . '</code></p>';

            $response = wp_remote_get( $url, array( 'timeout' => 10 ) );

            if ( is_wp_error( $response ) ) {
                echo '<p style="color: red;">❌ Connection Error: ' . esc_html( $response->get_error_message() ) . '</p>';
            } else {
                $code = wp_remote_retrieve_response_code( $response );
                $body = wp_remote_retrieve_body( $response );
                $data = json_decode( $body, true );

                echo '<p><strong>HTTP Status:</strong> ' . esc_html( $code ) . '</p>';

                if ( $code !== 200 ) {
                    echo '<p style="color: red;">❌ API Error:</p>';
                    echo '<pre style="background: #fff; padding: 1rem; overflow: auto; max-height: 200px;">' . esc_html( $body ) . '</pre>';
                } else {
                    echo '<p style="color: green;">✅ API connection successful</p>';
                    $row_count = isset( $data['values'] ) ? count( $data['values'] ) : 0;
                    echo '<p><strong>Rows returned:</strong> ' . $row_count . '</p>';
                    if ( $row_count > 0 && isset( $data['values'][0] ) ) {
                        echo '<p><strong>First row sample:</strong> ' . esc_html( implode( ' | ', $data['values'][0] ) ) . '</p>';
                    }
                }
            }

            $sheets = new HLB_Google_Sheets();

            // Test tier data
            echo '<h4>Tier Data</h4>';
            $tiers = $sheets->get_tiers();

            if ( empty( $tiers ) ) {
                echo '<p style="color: red;">❌ No tier data found. Check:</p>';
                echo '<ul>';
                echo '<li>Sheet is public (Anyone with link can view)</li>';
                echo '<li>Tab is named exactly "Prices"</li>';
                echo '<li>Data format: Date | Tier (low, medium, high, peak)</li>';
                echo '<li>API key has Google Sheets API enabled</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: green;">✅ Found ' . count( $tiers ) . ' tier entries</p>';
                echo '<p><strong>Sample entries:</strong></p>';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="background: #8B6F47; color: white;">';
                echo '<th style="padding: 0.5rem; text-align: left;">Date</th>';
                echo '<th style="padding: 0.5rem; text-align: left;">Tier</th>';
                echo '<th style="padding: 0.5rem; text-align: left;">Weekly Rate</th>';
                echo '</tr>';

                $count = 0;
                foreach ( $tiers as $date => $tier ) {
                    if ( $count++ >= 10 ) break;
                    $weekly_rate = hlb_get_tier_weekly_rate( $tier );
                    echo '<tr style="border-bottom: 1px solid #ddd;">';
                    echo '<td style="padding: 0.5rem;">' . esc_html( $date ) . '</td>';
                    echo '<td style="padding: 0.5rem;">' . esc_html( ucfirst( $tier ) ) . '</td>';
                    echo '<td style="padding: 0.5rem;">' . ( $weekly_rate ? hlb_format_price( $weekly_rate ) : 'N/A' ) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            // Test bookings data
            echo '<h4>Booked Dates</h4>';
            $booked = $sheets->get_booked_dates();

            if ( empty( $booked ) ) {
                echo '<p>ℹ️ No booked dates found in Google Sheets</p>';
            } else {
                echo '<p style="color: green;">✅ Found ' . count( $booked ) . ' booked dates</p>';
                echo '<p><strong>Sample dates:</strong> ' . implode( ', ', array_slice( $booked, 0, 10 ) ) . '</p>';
            }

            // Test calendar tier lookup
            echo '<h4>Calendar Tier Lookup</h4>';
            $test_date = '2026-05-01';
            $test_tier = isset( $tiers[ $test_date ] ) ? $tiers[ $test_date ] : null;

            if ( $test_tier ) {
                $test_rate = hlb_get_tier_weekly_rate( $test_tier );
                echo '<p style="color: green;">✅ Tier lookup working! Test date ' . $test_date . ' = <strong>' . esc_html( ucfirst( $test_tier ) ) . '</strong>';
                if ( $test_rate ) {
                    echo ' (' . hlb_format_price( $test_rate ) . '/wk)';
                }
                echo '</p>';
            } else {
                echo '<p style="color: orange;">⚠️ No tier found for test date ' . $test_date . '</p>';
            }
            
            ?>
            
            <h3>Cache Status</h3>
            <p>Cache expires every 5 minutes. Last data fetched:</p>
            <ul>
                <li><strong>Pricing cache:</strong> <?php 
                    $pricing_cache = get_transient( 'hlb_sheets_pricing' );
                    echo $pricing_cache ? '✅ Active (' . count( $pricing_cache ) . ' entries)' : '❌ Empty (will fetch on next page load)';
                ?></li>
                <li><strong>Bookings cache:</strong> <?php 
                    $bookings_cache = get_transient( 'hlb_sheets_bookings' );
                    echo $bookings_cache ? '✅ Active (' . count( $bookings_cache ) . ' dates)' : '❌ Empty';
                ?></li>
            </ul>
            
            <form method="post" style="margin-top: 1rem;">
                <input type="hidden" name="hlb_clear_cache" value="1">
                <?php wp_nonce_field( 'hlb_clear_cache', 'hlb_cache_nonce' ); ?>
                <button type="submit" style="background: #8B6F47; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;">
                    Clear Cache & Refresh
                </button>
            </form>
            
            <?php
            // Handle cache clear
            if ( isset( $_POST['hlb_clear_cache'] ) && check_admin_referer( 'hlb_clear_cache', 'hlb_cache_nonce' ) ) {
                delete_transient( 'hlb_sheets_pricing' );
                delete_transient( 'hlb_sheets_bookings' );
                echo '<p style="color: green; margin-top: 1rem;">✅ Cache cleared! Reload page to fetch fresh data.</p>';
            }
            ?>
            
        <?php else : ?>
            <p style="color: orange;"><strong>⚠️ Google Sheets not configured</strong></p>
            <p>Go to <strong>Bookings → Settings</strong> to configure:</p>
            <ol>
                <li>Enable Google Sheets integration</li>
                <li>Add your Google API key</li>
                <li>Add your Spreadsheet ID</li>
            </ol>
        <?php endif; ?>
        
        <h3>Quick Links</h3>
        <ul>
            <li><a href="<?php echo admin_url( 'edit.php?post_type=hlb_booking&page=hlb-settings' ); ?>">Plugin Settings</a></li>
            <li><a href="https://console.cloud.google.com/">Google Cloud Console</a></li>
            <li><a href="https://sheets.google.com/">Google Sheets</a></li>
        </ul>
    </div>
    <?php
    
    return ob_get_clean();
}


