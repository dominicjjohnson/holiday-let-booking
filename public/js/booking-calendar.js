/**
 * Booking Calendar JavaScript - WordPress Version
 * Stay type selection: click Mon/Fri start day, choose stay type
 */

jQuery(document).ready(function($) {
    'use strict';

    init();

    /**
     * Initialize all functionality
     */
    function init() {
        setupCalendarInteractions();
        setupPriceCalculation();
        setupFormValidation();
        setupMonthNavigation();
    }

    /**
     * Calendar day interactions — only start days (Mon/Fri) are clickable
     */
    function setupCalendarInteractions() {
        var checkInInput = $('#hlb_check_in');
        var checkOutInput = $('#hlb_check_out');
        var stayTypeInput = $('#hlb_stay_type');

        // Click handler for start days only
        $(document).on('click', '.hlb-calendar-day.hlb-start-day', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $day = $(this);
            var date = $day.data('date');

            // Set check-in
            if (checkInInput.length) {
                checkInInput.val(date).trigger('change');
            }

            // Visual feedback
            $('.hlb-calendar-day').removeClass('hlb-selected-start hlb-in-range');
            $day.addClass('hlb-selected-start');

            // Clear previous stay selection
            if (checkOutInput.length) {
                checkOutInput.val('');
            }
            if (stayTypeInput.length) {
                stayTypeInput.val('');
            }

            // Hide any existing price summary
            $('#hlb-price-summary').hide();

            // Show loading indicator near the clicked date
            showStayPopover($day, '<div class="hlb-popover-loading">Loading options...</div>');

            // Get stay options for this date
            getPricePreview(date, $day);

            return false;
        });

        // Click anywhere else to close popover
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.hlb-stay-popover, .hlb-start-day').length) {
                hideStayPopover();
            }
        });

        // Hover tooltip for start days
        $(document).on('mouseenter', '.hlb-calendar-day.hlb-start-day', function() {
            var hoverData = $(this).data('hover-prices');
            if (hoverData) {
                showHoverTooltip($(this), hoverData);
            }
        });

        $(document).on('mouseleave', '.hlb-calendar-day.hlb-start-day', function() {
            hideHoverTooltip();
        });
    }

    /**
     * Show stay type popover near the clicked calendar day
     */
    function showStayPopover($target, html) {
        hideHoverTooltip();
        var popover = $('#hlb-stay-popover');
        if (!popover.length) {
            popover = $('<div id="hlb-stay-popover" class="hlb-stay-popover"></div>');
            $('body').append(popover);
        }
        popover.html(html);

        // Position below the clicked date
        var offset = $target.offset();
        var targetHeight = $target.outerHeight();
        var targetWidth = $target.outerWidth();

        popover.css({
            position: 'absolute',
            top: offset.top + targetHeight + 8,
            left: offset.left + (targetWidth / 2),
            transform: 'translateX(-50%)',
            zIndex: 10001
        }).show();

        // Ensure popover is visible in viewport
        setTimeout(function() {
            var popoverBottom = popover.offset().top + popover.outerHeight();
            var windowBottom = $(window).scrollTop() + $(window).height();
            if (popoverBottom > windowBottom) {
                $('html, body').animate({
                    scrollTop: popover.offset().top - 100
                }, 300);
            }
        }, 50);
    }

    /**
     * Hide the stay popover
     */
    function hideStayPopover() {
        $('#hlb-stay-popover').hide();
    }

    /**
     * Show hover tooltip with stay prices
     */
    function showHoverTooltip($element, pricesData) {
        // Don't show tooltip if popover is visible
        if ($('#hlb-stay-popover').is(':visible')) return;

        var tooltip = $('#hlb-hover-tooltip');
        if (!tooltip.length) {
            tooltip = $('<div id="hlb-hover-tooltip" class="hlb-hover-tooltip"></div>');
            $('body').append(tooltip);
        }
        var html = '';
        if (typeof pricesData === 'string') {
            try { pricesData = JSON.parse(pricesData); } catch(e) { return; }
        }
        if (!pricesData || !pricesData.length) return;
        for (var i = 0; i < pricesData.length; i++) {
            html += '<div>' + pricesData[i].label + ': <strong>&pound;' + Math.round(pricesData[i].price) + '</strong></div>';
        }
        tooltip.html(html);

        var offset = $element.offset();
        var tooltipHeight = tooltip.outerHeight();
        tooltip.css({
            top: offset.top - tooltipHeight - 8,
            left: offset.left + ($element.outerWidth() / 2) - (tooltip.outerWidth() / 2)
        });
        tooltip.show();
    }

    /**
     * Hide hover tooltip
     */
    function hideHoverTooltip() {
        $('#hlb-hover-tooltip').hide();
    }

    /**
     * Get price preview for selected check-in date
     */
    function getPricePreview(checkInDate, $targetDay) {
        $.ajax({
            url: hlbAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'hlb_get_price_preview',
                nonce: hlbAjax.nonce,
                check_in: checkInDate
            },
            success: function(response) {
                if (response.success && response.data && response.data.options && response.data.options.length > 0) {
                    displayPricePreview(response.data, $targetDay);
                } else {
                    showStayPopover($targetDay, '<div class="hlb-popover-message">No stay options available for this date.</div>');
                }
            },
            error: function() {
                showStayPopover($targetDay, '<div class="hlb-popover-message">Unable to load prices. Please try again.</div>');
            }
        });
    }

    /**
     * Display price preview as a popover with clickable stay type buttons
     */
    function displayPricePreview(data, $targetDay) {
        var currency = data.currency || '&pound;';
        var html = '<div class="hlb-popover-header">Choose your stay:</div>';
        html += '<div class="hlb-stay-options">';

        for (var i = 0; i < data.options.length; i++) {
            var opt = data.options[i];
            html += '<button type="button" class="hlb-stay-option" '
                + 'data-stay-type="' + opt.stay_type + '" '
                + 'data-check-out="' + opt.check_out + '" '
                + 'data-nights="' + opt.nights + '" '
                + 'data-price="' + opt.price + '">'
                + '<span class="hlb-stay-option-label">' + opt.label + '</span>'
                + '<span class="hlb-stay-option-price">' + currency + Math.round(opt.price) + '</span>'
                + '</button>';
        }

        html += '</div>';
        html += '<div class="hlb-popover-tier">' + ucfirst(data.tier) + ' season &middot; ' + currency + Math.round(data.weekly_rate) + '/wk</div>';

        showStayPopover($targetDay, html);

        // Bind click handlers on stay option buttons
        $('#hlb-stay-popover .hlb-stay-option').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var checkOut = $(this).data('check-out');
            var stayType = $(this).data('stay-type');
            var checkIn = $('#hlb_check_in').val();

            // Set form values
            $('#hlb_check_out').val(checkOut).trigger('change');
            $('#hlb_stay_type').val(stayType);

            // Visual: highlight selected button
            $('.hlb-stay-option').removeClass('hlb-stay-option-selected');
            $(this).addClass('hlb-stay-option-selected');

            // Highlight date range on calendar
            highlightRange(checkIn, checkOut);

            // Hide the popover
            hideStayPopover();

            // Scroll to form
            scrollToForm();
        });
    }

    /**
     * Highlight date range on calendar
     */
    function highlightRange(startDate, endDate) {
        $('.hlb-calendar-day').removeClass('hlb-in-range');
        $('.hlb-calendar-day').each(function() {
            var date = $(this).data('date');
            if (date && date >= startDate && date <= endDate) {
                $(this).addClass('hlb-in-range');
            }
        });
    }

    /**
     * Scroll to booking form smoothly
     */
    function scrollToForm() {
        var form = $('.hlb-booking-form-wrapper, .hlb-booking-form-section');
        if (form.length) {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: form.offset().top - 100
                }, 500);
            }, 200);
        }
    }

    /**
     * Real-time price calculation
     */
    function setupPriceCalculation() {
        var checkInInput = $('#hlb_check_in');
        var checkOutInput = $('#hlb_check_out');
        var dogCheckbox = $('#hlb_has_dog');

        if (!checkInInput.length || !checkOutInput.length) return;

        function updatePrice() {
            var checkIn = checkInInput.val();
            var checkOut = checkOutInput.val();
            var hasDog = dogCheckbox.length ? dogCheckbox.is(':checked') : false;

            if (checkIn && checkOut && checkOut > checkIn) {
                calculatePrice(checkIn, checkOut, hasDog);
            } else {
                $('#hlb-price-summary').hide();
            }
        }

        checkInInput.on('change', updatePrice);
        checkOutInput.on('change', updatePrice);
        if (dogCheckbox.length) {
            dogCheckbox.on('change', updatePrice);
        }

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
                }
            });
        }

        function displayPrice(priceData) {
            var priceDiv = $('#hlb-price-summary');
            var priceContent = $('#hlb-price-content');

            var subtotal = parseFloat(priceData.subtotal) || 0;
            var dogFee = parseFloat(priceData.dog_fee) || 0;
            var total = parseFloat(priceData.total) || 0;
            var stayLabel = priceData.stay_label || (priceData.nights + ' night(s)');
            var tier = priceData.tier ? ucfirst(priceData.tier) : '';
            var percentage = priceData.percentage ? priceData.percentage + '%' : '';

            var html = '<div class="hlb-price-breakdown">';

            // Stay info
            html += '<div class="hlb-price-row hlb-price-main">';
            html += '<span class="hlb-price-label">' + stayLabel;
            if (tier) {
                html += ' <small>(' + tier + ' ' + percentage + ')</small>';
            }
            html += '</span>';
            html += '<span class="hlb-price-value">&pound;' + subtotal.toFixed(2) + '</span>';
            html += '</div>';

            // Dog fee
            if (dogFee > 0) {
                html += '<div class="hlb-price-row">';
                html += '<span class="hlb-price-label">Dog Fee</span>';
                html += '<span class="hlb-price-value">&pound;' + dogFee.toFixed(2) + '</span>';
                html += '</div>';
            }

            // Total
            html += '<div class="hlb-price-row hlb-price-total">';
            html += '<span class="hlb-price-label">Total Price</span>';
            html += '<span class="hlb-price-value">&pound;' + total.toFixed(2) + '</span>';
            html += '</div>';

            html += '</div>';

            priceContent.html(html);
            priceDiv.slideDown();

            // Show in booking info section
            var bookingTotalDiv = $('#hlb-booking-total-price');
            if (bookingTotalDiv.length) {
                bookingTotalDiv.html('<strong>Total Price: &pound;' + total.toFixed(2) + '</strong>').slideDown();
            }
        }
    }

    /**
     * Form validation and submission
     */
    function setupFormValidation() {
        var form = $('#hlb-booking-form');
        if (!form.length) return;

        form.on('submit', function(e) {
            e.preventDefault();

            var checkIn = $('#hlb_check_in').val();
            var checkOut = $('#hlb_check_out').val();
            var stayType = $('#hlb_stay_type').val();

            if (!checkIn || !checkOut) {
                showMessage('Please select a check-in date and stay type from the calendar.', 'error');
                return false;
            }

            if (!stayType) {
                showMessage('Please select a stay type (Mon-Fri, Fri-Mon, or Fri-Sun).', 'error');
                return false;
            }

            submitBooking();
            return false;
        });
    }

    /**
     * Submit booking via AJAX
     */
    function submitBooking() {
        var form = $('#hlb-booking-form');
        var submitButton = form.find('.hlb-submit-button');

        submitButton.prop('disabled', true).text('Processing...');

        var formData = {
            action: 'hlb_submit_booking',
            nonce: hlbAjax.nonce,
            check_in: $('#hlb_check_in').val(),
            check_out: $('#hlb_check_out').val(),
            stay_type: $('#hlb_stay_type').val(),
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
                    $('.hlb-calendar-day').removeClass('hlb-selected-start hlb-in-range');
                    hideStayPopover();

                    // Fire Google Ads conversion if configured
                    if (hlbAjax.conversion_id && typeof gtag === 'function') {
                        gtag('event', 'conversion', {
                            'send_to': hlbAjax.conversion_id,
                            'value': response.data.price ? parseFloat(response.data.price.total) : 0,
                            'currency': hlbAjax.currency || 'GBP',
                            'transaction_id': response.data.booking_id ? String(response.data.booking_id) : ''
                        });
                    }
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage(hlbAjax.error_message || 'An error occurred. Please try again.', 'error');
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
        var messageDiv = $('#hlb-booking-message');
        messageDiv.removeClass('hlb-success hlb-error').addClass('hlb-' + type);
        messageDiv.text(message).slideDown();

        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 100
        }, 500);
    }

    /**
     * Month navigation
     */
    function setupMonthNavigation() {
        $(document).on('click', '.hlb-arrow', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var month = $(this).data('month');
            var year = $(this).data('year');
            navigateToMonth(month, year);
            return false;
        });

        $('#hlb-jump-month, #hlb-jump-year').on('change', function() {
            var month = $('#hlb-jump-month').val();
            var year = $('#hlb-jump-year').val();
            navigateToMonth(month, year);
        });
    }

    /**
     * Navigate to specific month
     */
    function navigateToMonth(month, year) {
        var baseUrl = window.location.origin + window.location.pathname;
        var newUrl = baseUrl + '?hlb_month=' + month + '&hlb_year=' + year + '#booknow';
        window.location.href = newUrl;
    }

    /**
     * Capitalize first letter
     */
    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Add CSS for interactive states
    $('<style>')
        .prop('type', 'text/css')
        .html(
            '.hlb-calendar-day.hlb-selected-start {' +
            '    background: #8B6F47 !important;' +
            '    color: white !important;' +
            '    border-color: #6B5436 !important;' +
            '}' +

            '.hlb-calendar-day.hlb-in-range {' +
            '    background: rgba(139, 111, 71, 0.2) !important;' +
            '    border-color: #8B6F47 !important;' +
            '}' +

            '.hlb-calendar-day.hlb-start-day {' +
            '    cursor: pointer;' +
            '    border-left: 3px solid #8B6F47;' +
            '}' +

            '.hlb-calendar-day.hlb-start-day:hover {' +
            '    background: rgba(139, 111, 71, 0.1);' +
            '    transform: scale(1.05);' +
            '    transition: all 0.15s ease;' +
            '}' +

            '.hlb-calendar-day.hlb-non-start {' +
            '    opacity: 0.6;' +
            '    cursor: default;' +
            '}' +

            '.hlb-booking-message {' +
            '    padding: 1rem;' +
            '    border-radius: 8px;' +
            '    margin-bottom: 1.5rem;' +
            '    font-weight: 500;' +
            '    display: none;' +
            '}' +

            '.hlb-booking-message.hlb-success {' +
            '    background: #5cb85c;' +
            '    color: white;' +
            '}' +

            '.hlb-booking-message.hlb-error {' +
            '    background: #d9534f;' +
            '    color: white;' +
            '}' +

            '.hlb-stay-popover {' +
            '    position: absolute;' +
            '    z-index: 10001;' +
            '    background: #8B6F47;' +
            '    color: white;' +
            '    padding: 1rem 1.25rem;' +
            '    border-radius: 12px;' +
            '    min-width: 220px;' +
            '    max-width: 320px;' +
            '    box-shadow: 0 8px 24px rgba(0,0,0,0.25);' +
            '    display: none;' +
            '    font-size: 0.95rem;' +
            '}' +

            '.hlb-stay-popover::before {' +
            '    content: "";' +
            '    position: absolute;' +
            '    top: -8px;' +
            '    left: 50%;' +
            '    margin-left: -8px;' +
            '    border-width: 0 8px 8px;' +
            '    border-style: solid;' +
            '    border-color: transparent transparent #8B6F47;' +
            '}' +

            '.hlb-popover-header {' +
            '    font-weight: 700;' +
            '    margin-bottom: 0.75rem;' +
            '    font-size: 1rem;' +
            '}' +

            '.hlb-popover-loading {' +
            '    text-align: center;' +
            '    padding: 0.5rem 0;' +
            '    opacity: 0.8;' +
            '}' +

            '.hlb-popover-message {' +
            '    text-align: center;' +
            '    padding: 0.25rem 0;' +
            '}' +

            '.hlb-stay-options {' +
            '    display: flex;' +
            '    flex-direction: column;' +
            '    gap: 0.5rem;' +
            '    margin-bottom: 0.75rem;' +
            '}' +

            '.hlb-stay-option {' +
            '    display: flex;' +
            '    justify-content: space-between;' +
            '    align-items: center;' +
            '    background: rgba(255, 255, 255, 0.15);' +
            '    color: white;' +
            '    border: 2px solid rgba(255, 255, 255, 0.3);' +
            '    padding: 0.65rem 1rem;' +
            '    border-radius: 8px;' +
            '    cursor: pointer;' +
            '    font-size: 0.95rem;' +
            '    transition: all 0.15s ease;' +
            '    text-align: left;' +
            '    line-height: 1.3;' +
            '}' +

            '.hlb-stay-option:hover {' +
            '    background: rgba(255, 255, 255, 0.3);' +
            '    border-color: white;' +
            '}' +

            '.hlb-stay-option-label {' +
            '    font-weight: 500;' +
            '}' +

            '.hlb-stay-option-price {' +
            '    font-weight: 700;' +
            '    font-size: 1.1em;' +
            '    margin-left: 1rem;' +
            '    white-space: nowrap;' +
            '}' +

            '.hlb-stay-option-selected {' +
            '    background: white !important;' +
            '    color: #8B6F47 !important;' +
            '    border-color: white !important;' +
            '}' +

            '.hlb-popover-tier {' +
            '    font-size: 0.8em;' +
            '    opacity: 0.7;' +
            '    text-align: center;' +
            '}' +

            '.hlb-hover-tooltip {' +
            '    position: absolute;' +
            '    z-index: 10000;' +
            '    background: #333;' +
            '    color: white;' +
            '    padding: 0.5rem 0.75rem;' +
            '    border-radius: 6px;' +
            '    font-size: 0.85rem;' +
            '    line-height: 1.4;' +
            '    pointer-events: none;' +
            '    display: none;' +
            '    white-space: nowrap;' +
            '    box-shadow: 0 2px 8px rgba(0,0,0,0.3);' +
            '}' +

            '.hlb-hover-tooltip::after {' +
            '    content: "";' +
            '    position: absolute;' +
            '    bottom: -6px;' +
            '    left: 50%;' +
            '    margin-left: -6px;' +
            '    border-width: 6px 6px 0;' +
            '    border-style: solid;' +
            '    border-color: #333 transparent transparent;' +
            '}' +

            '.hlb-price-summary {' +
            '    background: linear-gradient(135deg, #8B6F47 0%, #6B5436 100%);' +
            '    color: white;' +
            '    border-radius: 12px;' +
            '    padding: 2rem;' +
            '    margin-bottom: 2rem;' +
            '    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);' +
            '}' +

            '.hlb-price-summary-header h3 {' +
            '    margin: 0 0 1.5rem 0 !important;' +
            '    font-size: 1.5rem !important;' +
            '    font-weight: 700 !important;' +
            '    text-align: center !important;' +
            '    color: #ffffff !important;' +
            '}' +

            '.hlb-price-breakdown {' +
            '    background: rgba(255, 255, 255, 0.1);' +
            '    border-radius: 8px;' +
            '    padding: 1.5rem;' +
            '}' +

            '.hlb-price-row {' +
            '    display: flex;' +
            '    justify-content: space-between;' +
            '    align-items: center;' +
            '    padding: 0.75rem 0;' +
            '    border-bottom: 1px solid rgba(255, 255, 255, 0.2);' +
            '}' +

            '.hlb-price-row:last-child {' +
            '    border-bottom: none;' +
            '}' +

            '.hlb-price-row.hlb-price-main {' +
            '    font-size: 1.1rem;' +
            '    font-weight: 600;' +
            '}' +

            '.hlb-price-row.hlb-price-main small {' +
            '    font-weight: 400;' +
            '    opacity: 0.8;' +
            '}' +

            '.hlb-price-row.hlb-price-total {' +
            '    margin-top: 1rem;' +
            '    padding-top: 1.5rem;' +
            '    border-top: 2px solid rgba(255, 255, 255, 0.3);' +
            '    font-size: 1.5rem;' +
            '    font-weight: 800;' +
            '}' +

            '.hlb-price-label {' +
            '    flex: 1;' +
            '}' +

            '.hlb-price-value {' +
            '    font-weight: 700;' +
            '    font-size: 1.2em;' +
            '}' +

            '.hlb-booking-total-price {' +
            '    background: linear-gradient(135deg, #8B6F47 0%, #6B5436 100%) !important;' +
            '    color: white !important;' +
            '    padding: 1rem !important;' +
            '    border-radius: 8px !important;' +
            '    text-align: center !important;' +
            '    font-size: 1.25rem !important;' +
            '    margin-bottom: 1rem !important;' +
            '}'
        )
        .appendTo('head');
});
