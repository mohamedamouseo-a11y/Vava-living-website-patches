# VAVA — Individual Questionnaire Excel Exports

## Goal
Keep questionnaire management in its existing Booking > Questionnaires tab and add **one Excel download button for each of the three existing questionnaires**:

- Journey Start (`beginning`)
- Mid-Journey (`midpoint`)
- Journey Impact (`impact`)

Each button must download only that questionnaire's responses, with customer/booking metadata plus that questionnaire's questions and answers.

## Current live-state assumption
A previous patch may already have added:

`wp-content/themes/vava-living-theme-ar-v1/inc/questionnaire-admin-dashboard-v1.php`

and a require in `functions.php`.

The current live `/public_html` is authoritative. Inspect it first. Do not overwrite newer unrelated code.

## Required final UX
Do **not** keep a separate Questionnaires submenu/dashboard page.

Inside the existing questionnaire editor rendered by:

`inc/booking-questionnaires-vava.php`

show an export action for each questionnaire card/accordion, visually close to that questionnaire's controls:

- `تنزيل Excel — استبيان بداية الرحلة`
- `تنزيل Excel — استبيان منتصف الرحلة`
- `تنزيل Excel — استبيان أثر الرحلة`

Use the existing AR/EN admin language mechanism if practical. Buttons must not break the accordion toggle markup or create nested buttons.

## Data source
Do not create a new storage model and do not store a permanent spreadsheet on the server.

Generate the file on demand from existing WordPress data:

- `_vava_booking_questionnaire` for `beginning` / `midpoint`
- `_vava_booking_impact_questionnaire` for `impact`
- reuse `vava_booking_questionnaire_all_booking_data()` where practical

Keep all existing responses untouched.

## One workbook per questionnaire
Each export contains one row per submitted response of the selected type only.

Fixed metadata columns:

- Booking ID
- Customer name
- Email
- WhatsApp
- Service/session
- Appointment date
- Appointment time
- Booking status
- Questionnaire language
- Submitted/completed at

Then add only that questionnaire's question columns.

Use human-readable question labels and human-readable option labels, not raw values where a label exists.

For historical responses, prefer the saved submission `snapshot` so old answers remain understandable after future questionnaire wording changes.

Build the question-column set from the current questionnaire schema plus any historical snapshot-only fields, so old data is not lost. Preserve a stable logical field order.

Suggested filenames:

- `vava-journey-start-responses-YYYY-MM-DD.xlsx`
- `vava-mid-journey-responses-YYYY-MM-DD.xlsx`
- `vava-journey-impact-responses-YYYY-MM-DD.xlsx`

## Export implementation
Prefer a real `.xlsx` generated server-side with native PHP/OpenXML + `ZipArchive` and no Composer/plugin dependency.

If `ZipArchive` is unavailable, use a UTF-8 BOM CSV fallback for that questionnaire only and use the matching `.csv` filename.

Requirements:

- preserve Arabic text
- sanitize every spreadsheet value against formula injection (`=`, `+`, `-`, `@`)
- XML escape XLSX values
- use text cells rather than formula cells
- send private/no-cache headers
- do not expose a public endpoint
- use `admin-post.php` authenticated action only
- capability must be the existing VAVA booking admin capability, with `manage_options` fallback
- nonce-protect every export URL/action

If there are no responses for that questionnaire, return the admin safely with a clear notice instead of generating a misleading file.

For large datasets, query booking IDs in batches rather than loading every full post object at once.

Exclude product orders and other non-session records.

## Clean up the previous dashboard patch
Final UI must not include the separate Questionnaires submenu/page from the previous patch.

Preferred clean implementation:

1. Replace the old dashboard module with a small export-only module, e.g.:
   `inc/questionnaire-excel-export-v2.php`
2. Load it after `booking-questionnaires-vava.php` in `functions.php`.
3. Add only the smallest compatibility/render call needed in `booking-questionnaires-vava.php` to display the per-questionnaire export button.
4. Remove/unload `questionnaire-admin-dashboard-v1.php`. Delete it only if it was introduced solely by the previous patch and contains no unrelated live changes; otherwise leave the file but ensure no hooks/menu/export duplicates remain active.

Avoid duplicate export actions/functions from the old module.

## Must remain unchanged
Do not change:

- questionnaire eligibility/display rules
- front-end questionnaire HTML/JS behavior except what is strictly required for admin export integration
- booking creation/submission
- questionnaire data model
- payments / Paymob / bank transfer
- customer accounts
- Paths / sessions
- digital products
- PDF storage/processing/reader/HMAC/watermarking
- Journal / Contact / Legal
- Developer Hub
- WordPress core

## Acceptance checks
1. Existing Booking > Questionnaires tab still works exactly as before.
2. No separate Questionnaires submenu/dashboard remains.
3. Three separate Excel buttons are visible, one per questionnaire.
4. Each button downloads only its own questionnaire type.
5. Workbook includes booking/customer metadata and that questionnaire's answers.
6. Historical snapshots are used when available.
7. Arabic text is readable in Excel.
8. No raw option IDs are shown when human labels exist.
9. No public export endpoint exists.
10. Unauthorized users cannot export.
11. Existing questionnaire responses remain unchanged.
12. PHP lint passes on all changed PHP files when PHP CLI is available.
13. `git diff --check` passes.
14. No Git push is performed.
