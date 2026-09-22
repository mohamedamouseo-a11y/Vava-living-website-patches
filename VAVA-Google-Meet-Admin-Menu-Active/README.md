# Fix Google Meet Admin Menu Active State

## Goal
When viewing the VAVA Google Meet settings page, the sidebar submenu item "Google Meet" must appear selected/highlighted under "VAVA حجوزات".

## Current issue
The page loads correctly at the Google Meet settings screen, but WordPress does not mark the "Google Meet" submenu item as current/active in the left admin sidebar.

## Source of truth
Use CURRENT LIVE files via FTP.

## Scope
Modify ONLY:
`wp-content/themes/vava-living-theme-ar-v1/inc/google-meet-vava.php`

Do not modify booking logic, SMTP, OAuth behavior, reminders, payments, or any other feature.

## Required behavior
On the Google Meet settings admin screen:
- Parent menu remains `edit.php?post_type=vava_booking`
- Submenu slug remains the existing Google Meet settings slug
- The "Google Meet" submenu item is visibly selected/highlighted
- Other VAVA Bookings submenu pages keep their own active states unchanged

Use WordPress-native admin menu state handling, preferably targeted `parent_file` and/or `submenu_file` filters, or equivalent minimal native approach.

The logic must run ONLY on the Google Meet settings page and must not affect unrelated admin pages.

## Validation
- Google Meet settings page loads without fatal
- "Google Meet" submenu is active on that page
- "إعدادات البريد" is not incorrectly selected
- VAVA حجوزات list page active state still works
- only google-meet-vava.php changed
- no push
