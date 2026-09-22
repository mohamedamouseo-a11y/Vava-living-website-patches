# Fix VAVA Booking Details Not Opening

## Problem
On the live VAVA Bookings list, clicking the booking number link (for example #179) does nothing. The booking details page must open.

The same canonical details route is also used by the "عرض التفاصيل" action, so verify both entry points.

## Source of truth
Use CURRENT LIVE files via FTP. Do not overwrite newer live booking/Google Meet/SMTP changes with GitHub main.

## Goal
Restore reliable navigation:
- clicking the booking number opens booking details
- "عرض التفاصيل" from the row action opens the same details page
- details page renders normally
- Google Meet meeting card and all current booking details remain intact

## Diagnose first
Before editing, inspect CURRENT LIVE:
1. rendered/link-producing PHP for `.vava-booking-admin-number`
2. `vava_booking_admin_details_url()`
3. registration of the `vava-booking-details` admin page
4. current `assets/js/admin-bookings-vava.js` for click interception/preventDefault
5. relevant admin CSS only if an overlay/pointer-event issue exists

Determine whether the root cause is:
- bad/missing href
- invalid/unregistered admin route
- JS intercepting the link
- CSS overlay/pointer issue
- another live-only regression

Fix the actual cause.

## Preferred behavior
Keep the canonical details URL:
`admin.php?page=vava-booking-details&booking={ID}`

Do NOT switch to a different WordPress screen unless the canonical route is proven unusable and cannot be fixed safely.

The booking-number link must remain a normal `<a href="...">` link and work without JavaScript.

## Allowed files
Modify the minimum necessary CURRENT LIVE files only. Likely:
- `wp-content/themes/vava-living-theme-ar-v1/inc/booking-vava-safe-v4r10.php`
- optionally `wp-content/themes/vava-living-theme-ar-v1/assets/js/admin-bookings-vava.js` ONLY if JS is proven to be the cause
- optionally `wp-content/themes/vava-living-theme-ar-v1/assets/css/admin-bookings-vava.css` ONLY if CSS is proven to be the cause

Do NOT modify:
- google-meet-vava.php
- mail-settings-vava.php
- functions.php unless absolutely required by the root cause
- payment/Paymob/bank/refund/questionnaire/product/customer-account logic
- Developer Hub
- frontend

## Safety
Preserve:
- current Google Meet automation
- meeting card
- manual meeting fallback
- SMTP settings
- admin menu active-state fix
- current booking list design

Do not push.

## Validation
Report exact cause found.

Validate:
1. booking-number HTML contains a non-empty canonical details href
2. canonical details page is registered with current booking admin capability
3. no click handler prevents normal number-link navigation
4. "عرض التفاصيل" resolves to same canonical URL
5. details renderer still includes current meeting/automation UI
6. admin list and details PHP load without fatal
7. only necessary file(s) changed
8. run diff/whitespace validation and show exact output for any non-zero result

Do not claim a non-zero diff command is a success merely because files differ.
