# Holiday Let Booking Calendar - WordPress Plugin

A complete WordPress plugin for managing holiday let bookings with seasonal pricing, Google Sheets integration, email notifications, and a beautiful mobile-first design.

## Features

✅ **Complete Booking System** - Custom post type for bookings with full admin management  
✅ **Seasonal Pricing** - Different rates and rules for winter/summer periods  
✅ **Google Sheets Integration** - Optional sync for pricing and bookings  
✅ **Email Notifications** - Automatic confirmations to guests and admin  
✅ **Mobile-First Design** - Optimized for smartphone users  
✅ **SEO-Friendly** - Server-rendered HTML calendar for search engines  
✅ **Admin Dashboard** - View calendar, manage bookings, configure settings  
✅ **Shortcode & Gutenberg Block** - Easy integration anywhere on your site  
✅ **AJAX Booking** - Smooth, modern user experience  
✅ **Custom Post Statuses** - Pending, Confirmed, Paid, Cancelled, Completed  

## Installation

### Quick Install

1. **Download** the `holiday-let-booking` folder
2. **Upload** to `/wp-content/plugins/`
3. **Activate** via WordPress admin → Plugins
4. **Configure** settings at Bookings → Settings

### Manual Install

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone [repository-url] holiday-let-booking
```

Then activate via WordPress admin.

## Configuration

### 1. Basic Setup

Navigate to **Bookings → Settings** and configure:

- **Cleaning Fee**: £150 (default for winter bookings)
- **Dog Fee**: £35
- **Currency Symbol**: £ (or your currency)
- **Check-in Time**: 15:00
- **Check-out Time**: 10:00

### 2. Email Settings

- **Admin Email**: Where booking notifications are sent
- **From Email**: Email address for outgoing emails
- **From Name**: Your business name
- **Enable Notifications**: Toggle email alerts on/off

### 3. Google Sheets Integration (Optional)

If you want to manage pricing and bookings via Google Sheets:

1. **Create Google Sheet** with two tabs:
   - "Prices" (columns: Date, Price)
   - "Bookings" (columns: Check-in, Check-out, Guest Name, Status)

2. **Get API Key**:
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create project → Enable Google Sheets API
   - Create Credentials → API Key

3. **Make Sheet Public**:
   - Share → Anyone with link → Viewer
   - Copy spreadsheet ID from URL

4. **Configure Plugin**:
   - Enable Google Sheets in settings
   - Add API key and Sheet ID
   - Save settings

## Usage

### Display Booking Calendar

**Shortcode** (use anywhere):
```
[holiday_booking_calendar]
```

**Gutenberg Block**:
Search for "Holiday Let Booking Calendar" in block inserter

**PHP Template**:
```php
<?php echo do_shortcode('[holiday_booking_calendar]'); ?>
```

### Managing Bookings

1. **View All Bookings**: Bookings → All Bookings
2. **Calendar View**: Bookings → Calendar  
3. **Edit Booking**: Click any booking to edit details
4. **Change Status**: Update booking status (Pending → Confirmed → Paid)

### Booking Statuses

- **Pending**: New booking request  
- **Confirmed**: Booking confirmed by admin  
- **Paid**: Payment received  
- **Cancelled**: Booking cancelled  
- **Completed**: Guest has checked out  

## Seasonal Rules

### Winter Period (Before April 1st & From November 1st)

- Nightly bookings available
- Minimum 1 night
- £150 cleaning & admin fee added automatically
- Flexible dates

### Summer Period (April 1st - October 31st)

- **3-night stays**: Friday to Monday only
- **4-night stays**: Monday to Friday only  
- **Weekly stays**: Monday to Monday OR Friday to Friday
- No cleaning fee

## Customization

### Change Colors

Edit `/public/css/booking-calendar.css`:

```css
:root {
    --color-primary: #8B6F47;      /* Your brand color */
    --color-secondary: #C19A6B;
    /* ... more variables ... */
}
```

### Change Fonts

Update Google Fonts link in template files:

```html
<link href="https://fonts.googleapis.com/css2?family=YourFont&display=swap" rel="stylesheet">
```

### Modify Seasonal Rules

Edit winter months in `holiday-let-booking.php`:

```php
'hlb_winter_months' => array( 1, 2, 3, 11, 12 ), // Jan, Feb, Mar, Nov, Dec
```

### Custom Email Templates

Edit `/includes/class-email-handler.php` methods:
- `get_confirmation_template()` - Guest confirmation email
- `get_admin_notification_template()` - Admin notification email

## Hooks & Filters

### Actions

```php
// After booking is created
do_action( 'hlb_booking_created', $booking_id );

// After booking status changes
do_action( 'hlb_booking_status_changed', $booking_id, $old_status, $new_status );
```

### Filters

```php
// Modify price calculation
apply_filters( 'hlb_calculate_price', $price_data, $check_in, $check_out );

// Modify email subject
apply_filters( 'hlb_confirmation_email_subject', $subject, $booking_id );

// Modify email content
apply_filters( 'hlb_confirmation_email_content', $message, $booking_id );
```

## Database Tables

The plugin creates these custom tables:

- `wp_hlb_bookings_cache` - Performance cache for bookings
- `wp_hlb_pricing` - Optional local pricing (if not using Google Sheets)

## File Structure

```
holiday-let-booking/
├── holiday-let-booking.php      # Main plugin file
├── includes/                     # Core functionality
│   ├── class-booking-post-type.php
│   ├── class-booking-handler.php
│   ├── class-calendar-renderer.php
│   ├── class-email-handler.php
│   ├── class-settings.php
│   └── class-google-sheets.php
├── admin/                        # Admin interface
│   ├── class-admin.php
│   ├── class-bookings-list.php
│   ├── class-meta-boxes.php
│   └── views/
│       ├── admin-calendar.php
│       └── settings.php
├── public/                       # Public-facing
│   ├── class-public.php
│   ├── class-shortcodes.php
│   ├── css/
│   │   └── booking-calendar.css
│   ├── js/
│   │   └── booking-calendar.js
│   └── views/
│       ├── calendar-view.php
│       └── booking-form.php
└── blocks/                       # Gutenberg blocks
    └── class-blocks.php
```

## Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (iOS 12+)
- Mobile browsers

## Troubleshooting

### Bookings Not Showing in Calendar

1. Check booking status is "Confirmed" or "Paid"
2. Verify dates are in correct format (Y-m-d)
3. Clear WordPress cache
4. Check Custom Post Type is registered

### Email Notifications Not Sending

1. Verify WP Mail is configured correctly
2. Check spam folder
3. Test with SMTP plugin (WP Mail SMTP recommended)
4. Verify "Enable Notifications" is checked in settings

### Google Sheets Not Syncing

1. Check API key is correct
2. Verify sheet is public (Viewer access)
3. Check sheet tab names are "Prices" and "Bookings" exactly
4. Clear transient cache (wait 5 minutes or use object cache flush)

### Calendar Not Displaying

1. Check shortcode placement
2. Verify CSS is enqueued
3. Check for JavaScript errors in console
4. Ensure no theme conflicts

## Support

For issues or questions:

1. Check this README
2. Review code comments
3. Check WordPress debug.log
4. Test with default WordPress theme

## Changelog

### Version 1.0.0
- Initial release
- Custom post type for bookings
- Seasonal pricing system
- Google Sheets integration
- Email notifications
- Admin calendar view
- Mobile-first responsive design
- Shortcode and Gutenberg block

## License

GPL v2 or later

## Credits

Built by Miramedia  
Website: https://miramedia.co.uk

Fonts: Crimson Pro & Manrope from Google Fonts  
Icons: Heroicons

## Roadmap

Planned features:

- [ ] Payment gateway integration (Stripe, PayPal)
- [ ] iCal export for calendar sync
- [ ] SMS notifications
- [ ] Multi-property support
- [ ] Availability widget
- [ ] Booking analytics dashboard
- [ ] Dynamic pricing based on demand
- [ ] Guest review system
- [ ] Automated reminder emails
- [ ] Damage deposit handling

---

**Ready to start taking bookings!** 🏡

Install the plugin, configure your settings, and add the shortcode to any page.
