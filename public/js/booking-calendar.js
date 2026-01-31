/**
 * Booking Calendar JavaScript - WordPress Version
 * Progressive enhancement for better UX
 */

jQuery(document).ready(function($) {
    'use strict';
    
    init();
    
    /**
     * Initialize all functionality
     */
    function init() {
        setupDateValidation();
        setupCalendarInteractions();
        setupPriceCalculation();
        setupFormValidation();
        setupMonthNavigation();
    }
    
    /**
     * Date field validation and coordination
     */
    function setupDateValidation() {
        const checkInInput = $('#hlb_check_in');
        const checkOutInput = $('#hlb_check_out');

        if (!checkInInput.length || !checkOutInput.length) return;

        // Update check-out min date when check-in changes
        checkInInput.on('change', function() {
            if (this.value) {
                const checkInDate = new Date(this.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOut = checkInDate.toISOString().split('T')[0];
                checkOutInput.attr('min', minCheckOut);

                // If check-out is before new minimum, update it
                if (checkOutInput.val() && checkOutInput.val() < minCheckOut) {
                    checkOutInput.val(minCheckOut);
                }
            }
        });
    }
    
    /**
     * Calendar day interactions
     */
    function setupCalendarInteractions() {
        const calendarDays = $('.hlb-calendar-day.hlb-available');
        const checkInInput = $('#hlb_check_in');
        const checkOutInput = $('#hlb_check_out');
        let selectedCheckIn = null;

        calendarDays.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const date = $(this).data('date');

            if (!selectedCheckIn) {
                // First click: set check-in
                selectedCheckIn = date;
                if (checkInInput.length) {
                    checkInInput.val(date).trigger('change');
                }

                // Visual feedback
                calendarDays.removeClass('hlb-selected-start hlb-in-range');
                $(this).addClass('hlb-selected-start');

                // Get price preview for this date
                getPricePreview(date);
            } else {
                // Second click: set check-out
                if (date > selectedCheckIn) {
                    if (checkOutInput.length) {
                        checkOutInput.val(date).trigger('change');
                    }

                    // Clear visual feedback
                    calendarDays.removeClass('hlb-selected-start hlb-in-range');

                    // Scroll to form NOW (after both dates selected)
                    scrollToForm();

                    // Reset for next selection
                    selectedCheckIn = null;

                    hideSelectionMessage();
                } else {
                    // Clicked earlier date, restart selection
                    selectedCheckIn = date;
                    checkInInput.val(date).trigger('change');
                    calendarDays.removeClass('hlb-selected-start hlb-in-range');
                    $(this).addClass('hlb-selected-start');

                    // Get price preview for new date
                    getPricePreview(date);
                }
            }
            
            return false;
        });

        // Hover effect for range preview
        calendarDays.on('mouseenter', function() {
            if (selectedCheckIn && $(this).data('date') > selectedCheckIn) {
                highlightRange(selectedCheckIn, $(this).data('date'));
            }
        });

        calendarDays.on('mouseleave', function() {
            if (selectedCheckIn) {
                clearRangeHighlight();
            }
        });
    }
    
    /**
     * Show selection message (supports HTML)
     */
    function showSelectionMessage(message) {
        let msgDiv = $('#hlb-selection-message');
        if (!msgDiv.length) {
            msgDiv = $('<div id="hlb-selection-message" class="hlb-selection-message"></div>');
            $('.hlb-calendar-grid').before(msgDiv);
        }
        msgDiv.html(message).fadeIn();
    }
    
    /**
     * Hide selection message
     */
    function hideSelectionMessage() {
        $('#hlb-selection-message').fadeOut();
    }

    /**
     * Get price preview for selected check-in date
     */
    function getPricePreview(checkInDate) {
        $.ajax({
            url: hlbAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'hlb_get_price_preview',
                nonce: hlbAjax.nonce,
                check_in: checkInDate
            },
            success: function(response) {
                if (response.success) {
                    displayPricePreview(response.data);
                } else {
                    showSelectionMessage('Now select your check-out date.');
                }
            },
            error: function() {
                showSelectionMessage('Now select your check-out date.');
            }
        });
    }

    /**
     * Display price preview message
     */
    function displayPricePreview(data) {
        var currency = data.currency || '£';
        var message = '<strong>Prices from this date:</strong><br>';
        message += '2 nights: ' + currency + data.prices[2].toFixed(0) + ' · ';
        message += '3 nights: ' + currency + data.prices[3].toFixed(0) + ' · ';
        message += 'Week: ' + currency + data.prices[7].toFixed(0);
        message += '<br><span style="font-size: 0.85em; opacity: 0.8;">* Does not include £150 booking fee</span>';

        // High season Friday-only week message
        if (data.is_high_season) {
            message += '<br><em style="font-size: 0.9em;">⚠️ High season: Weekly bookings only (Friday to Friday)</em>';
        }

        message += '<br><span style="font-size: 0.9em; opacity: 0.9;">Select your check-out date above</span>';

        showSelectionMessage(message);
    }

    /**
     * Highlight date range on calendar
     */
    function highlightRange(startDate, endDate) {
        $('.hlb-calendar-day.hlb-available').each(function() {
            const date = $(this).data('date');
            if (date >= startDate && date <= endDate) {
                $(this).addClass('hlb-in-range');
            }
        });
    }

    /**
     * Clear range highlighting
     */
    function clearRangeHighlight() {
        $('.hlb-calendar-day').removeClass('hlb-in-range');
    }

    /**
     * Scroll to booking form smoothly
     */
    function scrollToForm() {
        const form = $('.hlb-booking-form-wrapper, .hlb-booking-form-section');
        if (form.length) {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: form.offset().top - 100
                }, 500);
            }, 100);
        }
    }
    
    /**
     * Real-time price calculation
     */
    function setupPriceCalculation() {
        const checkInInput = $('#hlb_check_in');
        const checkOutInput = $('#hlb_check_out');
        const dogCheckbox = $('#hlb_has_dog');

        if (!checkInInput.length || !checkOutInput.length) return;

        // Calculate on input change
        function updatePrice() {
            const checkIn = checkInInput.val();
            const checkOut = checkOutInput.val();
            const hasDog = dogCheckbox.length ? dogCheckbox.is(':checked') : false;

            if (checkIn && checkOut && checkOut > checkIn) {
                calculatePrice(checkIn, checkOut, hasDog);
            } else {
                $('#hlb-price-estimate').hide();
            }
        }

        checkInInput.on('change', updatePrice);
        checkOutInput.on('change', updatePrice);
        if (dogCheckbox.length) {
            dogCheckbox.on('change', updatePrice);
        }
        
        /**
         * Calculate estimated price via AJAX
         */
        function calculatePrice(checkIn, checkOut, hasDog) {
            $.ajax({
                url: hlbAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hlb_get_price',
                    nonce: hlbAjax.nonce,
                    check_in: checkIn,
                    check_out: checkOut,
                    has_dog: hasDog
                },
                success: function(response) {
                    if (response.success) {
                        displayPrice(response.data);
                    }
                },
                error: function() {
                    console.log('Price calculation failed');
                }
            });
        }
        
        /**
         * Display price estimate
         */
        function displayPrice(priceData) {
            const priceDiv = $('#hlb-price-summary');
            const priceContent = $('#hlb-price-content');

            // Convert to numbers to ensure toFixed() works
            const subtotal = parseFloat(priceData.subtotal) || 0;
            const cleaningFee = parseFloat(priceData.cleaning_fee) || 0;
            const dogFee = parseFloat(priceData.dog_fee) || 0;
            const total = parseFloat(priceData.total) || 0;
            const nights = parseInt(priceData.nights) || 0;

            let html = '<div class="hlb-price-breakdown">';

            // Nights info
            html += '<div class="hlb-price-row hlb-price-main">';
            html += '<span class="hlb-price-label">' + nights + ' night' + (nights > 1 ? 's' : '') + '</span>';
            html += '<span class="hlb-price-value">£' + subtotal.toFixed(2) + '</span>';
            html += '</div>';

            // Cleaning fee
            if (cleaningFee > 0) {
                html += '<div class="hlb-price-row">';
                html += '<span class="hlb-price-label">Cleaning & Admin Fee</span>';
                html += '<span class="hlb-price-value">£' + cleaningFee.toFixed(2) + '</span>';
                html += '</div>';
            }

            // Dog fee
            if (dogFee > 0) {
                html += '<div class="hlb-price-row">';
                html += '<span class="hlb-price-label">Dog Fee</span>';
                html += '<span class="hlb-price-value">£' + dogFee.toFixed(2) + '</span>';
                html += '</div>';
            }

            // Total
            html += '<div class="hlb-price-row hlb-price-total">';
            html += '<span class="hlb-price-label">Total Price</span>';
            html += '<span class="hlb-price-value">£' + total.toFixed(2) + '</span>';
            html += '</div>';

            html += '</div>';

            priceContent.html(html);
            priceDiv.slideDown();

            // Also show booking price in form header and booking info
            showBookingPrice(total);
        }

        /**
         * Show booking price in form area and booking info section
         */
        function showBookingPrice(total) {
            // Show in Booking Information section
            var bookingTotalDiv = $('#hlb-booking-total-price');
            if (bookingTotalDiv.length) {
                bookingTotalDiv.html('<strong>Total Price: £' + total.toFixed(2) + '</strong>').slideDown();
            }
        }
    }
    
    /**
     * Form validation and submission
     */
    function setupFormValidation() {
        const form = $('#hlb-booking-form');
        if (!form.length) return;

        form.on('submit', function(e) {
            e.preventDefault();
            
            const checkIn = $('#hlb_check_in').val();
            const checkOut = $('#hlb_check_out').val();

            if (!checkIn || !checkOut) {
                showMessage('Please select both check-in and check-out dates', 'error');
                return false;
            }

            if (checkOut <= checkIn) {
                showMessage('Check-out date must be after check-in date', 'error');
                return false;
            }

            // Submit via AJAX
            submitBooking();
            return false;
        });
    }
    
    /**
     * Submit booking via AJAX
     */
    function submitBooking() {
        const form = $('#hlb-booking-form');
        const submitButton = form.find('.hlb-submit-button');
        
        // Disable button
        submitButton.prop('disabled', true).text('Processing...');
        
        const formData = {
            action: 'hlb_submit_booking',
            nonce: hlbAjax.nonce,
            check_in: $('#hlb_check_in').val(),
            check_out: $('#hlb_check_out').val(),
            guest_name: $('#hlb_guest_name').val(),
            guest_email: $('#hlb_guest_email').val(),
            guest_phone: $('#hlb_guest_phone').val(),
            has_dog: $('#hlb_has_dog').is(':checked'),
            special_requests: $('#hlb_special_requests').val()
        };
        
        $.ajax({
            url: hlbAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    form[0].reset();
                    $('#hlb-price-summary').slideUp();
                    hideSelectionMessage();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false).html('Request Booking <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>');
            }
        });
    }
    
    /**
     * Show message to user
     */
    function showMessage(message, type) {
        const messageDiv = $('#hlb-booking-message');
        messageDiv.removeClass('hlb-success hlb-error').addClass('hlb-' + type);
        messageDiv.text(message).slideDown();
        
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 100
        }, 500);
        
        setTimeout(function() {
            messageDiv.slideUp();
        }, 5000);
    }
    
    /**
     * Month navigation
     */
    function setupMonthNavigation() {
        // Arrow navigation
        $('.hlb-arrow').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const month = $(this).data('month');
            const year = $(this).data('year');
            navigateToMonth(month, year);
            return false;
        });
        
        // Dropdown navigation
        $('#hlb-jump-month, #hlb-jump-year').on('change', function() {
            const month = $('#hlb-jump-month').val();
            const year = $('#hlb-jump-year').val();
            navigateToMonth(month, year);
        });
    }
    
    /**
     * Navigate to specific month
     */
    function navigateToMonth(month, year) {
        // Get the current page URL (before any query parameters)
        var baseUrl = window.location.origin + window.location.pathname;
        
        // Build new URL with parameters
        var newUrl = baseUrl + '?month=' + month + '&year=' + year;
        
        // Navigate to new URL
        window.location.href = newUrl;
    }

    // Add CSS for interactive states
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .hlb-calendar-day.hlb-selected-start {
                background: #8B6F47 !important;
                color: white !important;
                border-color: #6B5436 !important;
            }

            .hlb-calendar-day.hlb-in-range {
                background: rgba(139, 111, 71, 0.2) !important;
                border-color: #8B6F47 !important;
            }

            .hlb-booking-message {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                font-weight: 500;
                display: none;
            }

            .hlb-booking-message.hlb-success {
                background: #5cb85c;
                color: white;
            }

            .hlb-booking-message.hlb-error {
                background: #d9534f;
                color: white;
            }

            #hlb-price-estimate {
                margin-top: 1.5rem;
                padding: 1.5rem;
                background: linear-gradient(135deg, #8B6F47 0%, #6B5436 100%);
                color: white;
                border-radius: 12px;
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                display: none;
            }
            
            .hlb-selection-message {
                background: #8B6F47;
                color: white;
                padding: 1rem;
                border-radius: 8px;
                text-align: center;
                margin-bottom: 1rem;
                font-weight: 500;
                display: none;
            }
            
            .hlb-price-summary {
                background: linear-gradient(135deg, #8B6F47 0%, #6B5436 100%);
                color: white;
                border-radius: 12px;
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .hlb-price-summary-header h3 {
                margin: 0 0 1.5rem 0;
                font-size: 1.5rem;
                font-weight: 700;
                text-align: center;
            }
            
            .hlb-price-breakdown {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                padding: 1.5rem;
            }
            
            .hlb-price-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .hlb-price-row:last-child {
                border-bottom: none;
            }
            
            .hlb-price-row.hlb-price-main {
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .hlb-price-row.hlb-price-total {
                margin-top: 1rem;
                padding-top: 1.5rem;
                border-top: 2px solid rgba(255, 255, 255, 0.3);
                font-size: 1.5rem;
                font-weight: 800;
            }
            
            .hlb-price-label {
                flex: 1;
            }
            
            .hlb-price-value {
                font-weight: 700;
                font-size: 1.2em;
            }
            
            .hlb-price-info {
                font-size: 0.875rem;
                opacity: 0.9;
                padding: 0.5rem 0;
                text-align: center;
            }

            .hlb-booking-total-price {
                background: linear-gradient(135deg, #8B6F47 0%, #6B5436 100%);
                color: white;
                padding: 1rem;
                border-radius: 8px;
                text-align: center;
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
        `)
        .appendTo('head');
});
