# VAVA — Three Questionnaire Excel Export Buttons

## Goal
Keep questionnaire management exactly where it already exists inside the Booking admin page, and add one Excel export button for each of the three existing questionnaires.

No new submenu, no separate responses dashboard, no new questionnaire system.

## Current live state
The live site may already contain the previous module:
`wp-content/themes/vava-living-theme-ar-v1/inc/questionnaire-admin-dashboard-v1.php`

That previous patch added a separate Questionnaires submenu/responses dashboard. This new patch REPLACES that admin UX with a simpler export-only approach.

Use the CURRENT `/public_html` files as source of truth and preserve newer unrelated live changes.

## Required UI
Inside the existing Booking page questionnaire panel rendered by:
`vava_booking_questionnaire_render_admin_panel()`

show a clear export button on each questionnaire card/accordion:

- Journey Start / beginning → `Download Excel` / `تنزيل Excel`
- Mid-Journey / midpoint → `Download Excel` / `تنزيل Excel`
- Journey Impact / impact → `Download Excel` / `تنزيل Excel`

Place the button in a clean, consistent position in each questionnaire header/card, visually matching the existing VAVA admin design.

Do not create any new admin submenu or standalone dashboard page.

## Export behavior
Each button exports ONLY that questionnaire type.

Suggested filenames:
- `vava-journey-start-responses.xlsx`
- `vava-mid-journey-responses.xlsx`
- `vava-journey-impact-responses.xlsx`

If ZipArchive is unavailable, use UTF-8 BOM CSV fallback with equivalent filenames ending `.csv`.

Each exported row represents one submitted questionnaire response.

Include booking/customer metadata first:
- Booking ID
- Customer name
- Customer email
- WhatsApp
- Service/session title
- Appointment date
- Appointment time
- Booking status
- Questionnaire language
- Questionnaire completed/submitted date

Then include ONLY the questions belonging to that questionnaire as columns.

Use human-readable question labels from the saved submission `snapshot` whenever available, so old submissions remain understandable after questionnaire wording changes. Fall back to current questionnaire settings only when snapshot data is missing.

For radio/checkbox values, export readable AR/EN option labels when possible, not internal keys.

Preserve Arabic correctly.

Protect spreadsheet cells from formula injection for values beginning with `=`, `+`, `-`, or `@`.

## Existing storage to reuse
Do not change the data model.

Existing questionnaire definitions/settings:
`_vava_booking_questionnaires`

Booking questionnaire response:
`_vava_booking_questionnaire`

Impact questionnaire response:
`_vava_booking_impact_questionnaire`

Types:
- `beginning`
- `midpoint`
- `impact`

Reuse existing helpers where practical, especially:
`vava_booking_questionnaire_all_booking_data()`

## Implementation preference
If the previous `questionnaire-admin-dashboard-v1.php` exists on live:
- reuse its secure XLSX/CSV generation helpers where safe;
- remove/disable its submenu registration and standalone dashboard/responses UI;
- keep only the export functionality needed by these three buttons.

Prefer the smallest safe code change.

A small compatibility hook/call inside `booking-questionnaires-vava.php` is acceptable to render the per-questionnaire export button.

Do not duplicate large export logic in three places; use one export handler with a validated questionnaire type parameter.

## Security
- Admin-only.
- Use existing VAVA booking admin capability or `manage_options` fallback.
- Export URL must use `admin-post.php` authenticated action only.
- Require nonce.
- Validate questionnaire type strictly against `beginning`, `midpoint`, `impact`.
- Sanitize input and escape admin output.
- Use private/no-cache response headers.
- No `nopriv` export action.
- Do not log questionnaire answers.

## Remove old UX from previous patch
If currently active, remove/disable:
- Questionnaires admin submenu.
- Standalone Manage/Responses page.
- Responses dashboard/stat cards/filters/page UI.

Do NOT delete stored questionnaire responses or settings.

## Must remain unchanged
- Existing questionnaire edit controls and save behavior.
- Front-end questionnaire forms and eligibility/display rules.
- Booking flow/submission logic.
- Payments / Paymob / bank transfer.
- Customer accounts.
- Paths/sessions.
- Digital products.
- PDF private storage/processing/reader/HMAC/watermarking.
- Journal, Contact, Legal.
- Developer Hub.
- WordPress core.

## Acceptance checks
1. No separate Questionnaires submenu exists from the previous patch.
2. Existing Booking > Questionnaires area still works normally.
3. All 3 questionnaires each show their own Excel button.
4. Each button exports only its own questionnaire responses.
5. Beginning export contains beginning questions only.
6. Midpoint export contains midpoint questions only.
7. Impact export contains impact questions only.
8. Customer + booking metadata is included in every export.
9. Historical response snapshots are used where available.
10. Arabic text opens correctly in Excel.
11. Unauthorized users cannot export.
12. PHP lint passes on every changed PHP file.
13. `git diff --check` passes.
14. No Git push is performed.