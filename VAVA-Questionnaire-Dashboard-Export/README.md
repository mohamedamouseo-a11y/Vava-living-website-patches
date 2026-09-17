# VAVA — Questionnaire Dashboard & Excel Export

Scope: **questionnaire administration and response export only**.

## Goal
Create a clear WordPress Dashboard area where VAVA administrators can:

1. Manage the three existing booking questionnaires from one dedicated screen.
2. View submitted questionnaire responses without opening bookings one-by-one.
3. Filter/search responses.
4. Download response data to the device as an Excel workbook (`.xlsx`).

## Existing system to preserve
The current VAVA questionnaire engine is already implemented in:

`wp-content/themes/vava-living-theme-ar-v1/inc/booking-questionnaires-vava.php`

It already contains:
- `beginning` — Journey Start Questionnaire.
- `midpoint` — Mid-Journey Questionnaire.
- `impact` — Journey Impact Questionnaire.
- bilingual AR/EN titles, descriptions, groups, question labels and option labels.
- enable/disable state.
- required flags.
- current questionnaire settings stored on the Booking page in `_vava_booking_questionnaires`.
- booking questionnaire data stored in `_vava_booking_questionnaire`.
- impact questionnaire data stored in `_vava_booking_impact_questionnaire`.
- a snapshot of the questionnaire definition saved with each submission.

Do **not** replace this data model.

## Required Dashboard UX
Add a dedicated submenu below the existing VAVA Bookings area:

**Questionnaires / الاستبيانات**

The page should contain two main tabs:

### 1. Manage Questionnaires
Show all three questionnaires and allow administrators to edit the existing canonical settings:
- Enable / disable questionnaire.
- Arabic title.
- English title.
- Arabic description.
- English description.
- Section/group labels in Arabic and English.
- Question labels in Arabic and English.
- Required / optional state.
- Existing answer option labels in Arabic and English.

For this patch, do **not** change field IDs, field types, routing/display rules, or internal option values. Do not add a new questionnaire builder architecture.

The existing helper/render/save logic should be reused where practical. The canonical source remains `_vava_booking_questionnaires` on the current Booking page.

### 2. Responses
Create an admin response browser showing one row per submitted questionnaire response.

Include at minimum:
- Booking ID.
- Questionnaire type/title.
- Submitted/completed date.
- Customer name.
- Customer email.
- WhatsApp.
- Service/session title.
- Appointment date.
- Appointment time.
- Booking status.
- Questionnaire language.
- View response action.

The response detail view should display question labels and answers from the **saved submission snapshot** when available so historical answers remain understandable even after future questionnaire wording changes.

Add filters for:
- Questionnaire type: All / Beginning / Midpoint / Impact.
- Date range if practical.
- Search by booking ID, customer name or email.

Add pagination so the screen remains usable with many responses.

## Excel export
Add a secure admin action and a visible **Download Excel** button.

Required behavior:
- Download an actual `.xlsx` workbook when the server supports the required ZIP capability.
- No Composer dependency and no third-party WordPress plugin.
- If `ZipArchive` is unavailable, provide a UTF-8 BOM CSV fallback that opens correctly in Excel and clearly report the fallback in the implementation report.
- Export should respect the current questionnaire type/search/date filters when possible.
- One response per row.
- Include booking/customer/session metadata plus questionnaire answers.
- Use human-readable question labels as column headings.
- Use the saved questionnaire snapshot when available.
- Preserve Arabic characters correctly.
- Values must be exported as text/safe cell values to prevent spreadsheet formula injection.

## Security and privacy
Questionnaire responses can contain health-related personal information. Therefore:
- Admin page capability must be `manage_options` or the existing VAVA booking admin capability if it resolves to the same or stricter permission.
- No public REST/AJAX endpoint.
- Save/export actions require nonces.
- Escape all admin output.
- Sanitize all filters and posted settings.
- Send no-cache/private headers for exports.
- Never expose questionnaire data to front-end visitors.
- Do not log raw questionnaire answers.

## Files expected to change
Preferred implementation:
- Add a new isolated theme module, for example:
  `wp-content/themes/vava-living-theme-ar-v1/inc/questionnaire-admin-dashboard-v1.php`
- Load it in:
  `wp-content/themes/vava-living-theme-ar-v1/functions.php`

Only modify `booking-questionnaires-vava.php` if a small compatibility hook/helper is genuinely necessary. Prefer reusing its existing public functions instead.

## Must remain unchanged
- Booking front-end flow and questionnaire display rules.
- Payment handling / Paymob / bank transfer.
- Customer account flow.
- Paths/session logic.
- Digital products.
- PDF private storage, conversion, reader security, HMAC URLs and watermarking.
- Journal, Contact and Legal systems.
- WordPress core.
- Developer Hub behavior.

## Acceptance checks
1. Dashboard contains a clear Questionnaires submenu under Bookings.
2. Admin can edit questionnaire copy/required/options and save successfully.
3. Existing front-end questionnaire behavior still works.
4. Existing historical submissions remain readable.
5. Responses screen shows Beginning, Midpoint and Impact submissions.
6. Search/filtering works without PHP warnings.
7. Download Excel creates a readable workbook with Arabic text intact.
8. Export contains customer/session metadata and questionnaire answers.
9. Unauthorized users cannot access the page or export endpoint.
10. `php -l` passes on all modified PHP files.
11. `git diff --check` passes.
12. No Git push is performed by OpenHands.
