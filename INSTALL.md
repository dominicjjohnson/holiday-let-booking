# Quick Installation Guide

Get your Holiday Let Booking plugin running in 5 minutes!

## Step 1: Install Plugin

1. **Upload** the `holiday-let-booking` folder to `/wp-content/plugins/`
2. **Activate** via WordPress Admin → Plugins → Holiday Let Booking

## Step 2: Configure Basic Settings

Go to **Bookings → Settings**:

- ✅ Cleaning Fee: £150 (for winter)
- ✅ Dog Fee: £35
- ✅ Check-in Time: 15:00
- ✅ Check-out Time: 10:00
- ✅ Admin Email: [your email]
- ✅ Enable Notifications: ✓

Click **Save Settings**

## Step 3: Add to Your Site

**Option A: Shortcode** (easiest)
```
[holiday_booking_calendar]
```
Paste this into any page or post

**Option B: Gutenberg Block**
1. Edit page in Gutenberg
2. Click + to add block
3. Search "Holiday Let Booking"
4. Add block

**Option C: PHP Template**
```php
<?php echo do_shortcode('[holiday_booking_calendar]'); ?>
```

## Step 4: Test!

1. Visit your page
2. You should see the calendar
3. Try clicking dates
4. Fill in the booking form
5. Check you receive the email

## Optional: Google Sheets Sync

If you want to manage pricing via Google Sheets:

### A. Create Google Sheet

1. Create new Google Sheet
2. Create tab "Prices" with columns: Date | Price
3. Create tab "Bookings" with columns: Check-in | Check-out | Guest Name | Status
4. Share → Anyone with link → Viewer

### B. Get API Key

1. Go to [console.cloud.google.com](https://console.cloud.google.com/)
2. Create project
3. Enable "Google Sheets API"
4. Create Credentials → API Key
5. Copy key

### C. Configure Plugin

1. Bookings → Settings
2. Enable Google Sheets: ✓
3. Paste API Key
4. Paste Sheet ID (from URL)
5. Save

Done! Pricing will sync every 5 minutes.

## Adding Manual Prices (Without Google Sheets)

If not using Google Sheets, you can add prices directly in WordPress database or via code:

```php
global $wpdb;
$table = $wpdb->prefix . 'hlb_pricing';
$wpdb->insert( $table, array(
    'price_date' => '2026-02-14',
    'price' => 150.00,
    'is_available' => 1
) );
```

## Managing Bookings

- **View All**: Bookings → All Bookings
- **Calendar View**: Bookings → Calendar
- **Edit Booking**: Click any booking
- **Change Status**: Update status dropdown

## Booking Status Flow

1. **Pending** - New request arrives
2. **Confirmed** - You confirm availability
3. **Paid** - Payment received
4. **Completed** - After checkout

## Customization Quick Wins

### Change Colors
Edit `/public/css/booking-calendar.css` line 9-16

### Change Fonts
Edit main plugin file, search for "Google Fonts"

### Modify Winter Months
Edit `holiday-let-booking.php` line 102

## Troubleshooting

**Problem**: Calendar not showing  
**Solution**: Clear WordPress cache, check shortcode is correct

**Problem**: Emails not sending  
**Solution**: Install WP Mail SMTP plugin

**Problem**: Dates showing as booked when they're not  
**Solution**: Check booking status is Confirmed or Paid

**Problem**: Google Sheets not syncing  
**Solution**: Wait 5 minutes for cache to expire, or clear object cache

## Next Steps

1. ✅ Add real pricing to database or Google Sheet
2. ✅ Test booking flow end-to-end
3. ✅ Customize colors to match your brand
4. ✅ Set up payment gateway (if needed)
5. ✅ Add to navigation menu
6. ✅ Test on mobile devices

---

**You're ready to start taking bookings!** 🎉

For full documentation, see README.md
