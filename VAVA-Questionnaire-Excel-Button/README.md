# VAVA — Questionnaire Excel Button Simplification

## Goal
Simplify the questionnaire admin UX on the CURRENT live `/public_html` project.

The previous questionnaire dashboard patch has already been applied live and added:
- `wp-content/themes/vava-living-theme-ar-v1/inc/questionnaire-admin-dashboard-v1.php`
- a `require_once` for that file in `functions.php`

The user does NOT want a separate Questionnaires submenu/page anymore.

## Required final UX
Use the EXISTING questionnaire tab inside the Booking Page editor (`إعدادات صفحة الحجز` → `الاستبيانات`).

Add one clear admin-only button near the top of that existing questionnaire panel:

**تنزيل بيانات الاستبيانات Excel**

Optional English label where appropriate:
**Download Questionnaire Data**

Clicking the button must immediately download ALL stored questionnaire responses.

## Export contents
Export all three questionnaire types:
- `beginning`
- `midpoint`
- `impact`

Include at minimum:
- Booking ID
- Questionnaire type/title
- Submitted/completed date
- Customer name
- Customer email
- WhatsApp
- Service/session title
- Appointment date
- Appointment time
- Booking status
- Questionnaire language
- Every questionnaire question and answer

Use the saved submission `snapshot` for historical question labels whenever available.

Stored response sources already used by VAVA:
- `_vava_booking_questionnaire`
- `_vava_booking_impact_questionnaire`

## Reuse the existing export implementation
The live `questionnaire-admin-dashboard-v1.php` already contains the XLSX/CSV export implementation from the previous patch.

Reuse that working export code instead of rebuilding it.

Keep:
- native `.xlsx` generation using `ZipArchive` when available
- UTF-8 BOM CSV fallback when `ZipArchive` is unavailable
- Arabic support
- spreadsheet formula-injection protection
- admin capability check
- nonce verification
- private/no-cache download headers

## Remove the unwanted UI
Remove/disable ONLY the separate questionnaire submenu/page and its Responses/Manage UI registration.

There must be NO new `Questionnaires / الاستبيانات` submenu after this patch.

It is acceptable to keep the existing module file loaded if it is reused only as the secure export backend, but its separate admin menu/page must no longer be registered or visible.

Do not delete or rewrite working export helpers unnecessarily.

## Existing questionnaire editor
Do not rebuild questionnaire management. The existing questionnaire editor inside the Booking Page must remain exactly where it is and continue to allow editing the current questionnaire settings.

Add the download button inside `vava_booking_questionnaire_render_admin_panel()` in:

`wp-content/themes/vava-living-theme-ar-v1/inc/booking-questionnaires-vava.php`

Prefer using a helper from the existing export module to generate a nonce-protected admin export URL. If none exists, add the smallest helper needed.

## Scope protection
Do NOT change:
- questionnaire field IDs/types/options/eligibility rules
- booking frontend flow
- payments / Paymob / bank transfer
- customer accounts
- Paths/sessions
- digital products
- PDF system / reader / HMAC / watermarking
- Journal / Contact / Legal
- Developer Hub
- WordPress core

## Validation
After editing:
- `php -l` every modified PHP file
- `git diff --check`
- verify the old Questionnaires submenu is gone
- verify the existing Booking Page questionnaire tab still loads
- verify the Excel button is visible there
- verify the button downloads all questionnaire data
- verify Arabic opens correctly in Excel/CSV

Do NOT push, merge, or create a PR. Leave changes only in `/public_html` for the user to push later from Developer Hub.