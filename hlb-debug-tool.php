<?php
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

            // Test pricing data
            echo '<h4>Pricing Data</h4>';
            $pricing = $sheets->get_pricing();
            
            if ( empty( $pricing ) ) {
                echo '<p style="color: red;">❌ No pricing data found. Check:</p>';
                echo '<ul>';
                echo '<li>Sheet is public (Anyone with link can view)</li>';
                echo '<li>Tab is named exactly "Prices"</li>';
                echo '<li>Data format: Date | Tier | DisplayRate</li>';
                echo '<li>API key has Google Sheets API enabled</li>';
                echo '</ul>';
            } else {
                echo '<p style="color: green;">✅ Found ' . count( $pricing ) . ' pricing entries</p>';
                echo '<p><strong>Sample entries:</strong></p>';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="background: #8B6F47; color: white;">';
                echo '<th style="padding: 0.5rem; text-align: left;">Date</th>';
                echo '<th style="padding: 0.5rem; text-align: left;">Price</th>';
                echo '</tr>';

                $count = 0;
                foreach ( $pricing as $date => $price ) {
                    if ( $count++ >= 10 ) break;
                    echo '<tr style="border-bottom: 1px solid #ddd;">';
                    echo '<td style="padding: 0.5rem;">' . esc_html( $date ) . '</td>';
                    echo '<td style="padding: 0.5rem;">£' . number_format( $price, 2 ) . '</td>';
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

            // Test calendar rendering
            echo '<h4>Calendar Price Display</h4>';
            $test_date = '2026-05-01';
            $test_price = isset( $pricing[ $test_date ] ) ? $pricing[ $test_date ] : null;

            if ( $test_price ) {
                echo '<p style="color: green;">✅ Pricing working! Test date ' . $test_date . ' = <strong>£' . number_format( $test_price, 2 ) . '</strong></p>';
            } else {
                echo '<p style="color: orange;">⚠️ No price found for test date ' . $test_date . '</p>';
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
