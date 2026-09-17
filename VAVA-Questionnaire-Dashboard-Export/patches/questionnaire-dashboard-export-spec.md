# Questionnaire Dashboard & Export — Technical Patch Specification

This file describes the exact semantic patch to apply to the current live VAVA theme. It is intentionally implementation-oriented rather than a blind whole-file replacement because `/public_html` may be newer than GitHub `main`.

## A. Bootstrap

In the active theme `functions.php`, immediately after the active questionnaire engine is loaded, add one isolated require:

```php
require_once get_template_directory() . '/inc/questionnaire-admin-dashboard-v1.php';
```

Do not reorder the existing booking/customer/product modules.

## B. New isolated module

Create:

`wp-content/themes/vava-living-theme-ar-v1/inc/questionnaire-admin-dashboard-v1.php`

The module should:

- exit when WordPress is not loaded.
- define a small version constant such as `VAVA_QUESTIONNAIRE_ADMIN_DASHBOARD_VERSION`.
- register its admin submenu on `admin_menu`.
- use the booking capability helper if it exists, otherwise `manage_options`.
- never register public/nopriv endpoints.

### Recommended functions
Names may vary, but keep them prefixed and isolated, for example:

```text
vava_questionnaire_admin_capability
vava_questionnaire_admin_register_menu
vava_questionnaire_admin_page
vava_questionnaire_admin_manage_tab
vava_questionnaire_admin_responses_tab
vava_questionnaire_admin_save_settings
vava_questionnaire_admin_collect_responses
vava_questionnaire_admin_filter_responses
vava_questionnaire_admin_human_answer
vava_questionnaire_admin_export
vava_questionnaire_admin_export_xlsx
vava_questionnaire_admin_export_csv
```

## C. Canonical settings management

Use:

```php
vava_booking_page_id()
vava_booking_questionnaire_settings( $page_id )
vava_booking_questionnaire_defaults()
```

Canonical storage stays:

```text
_vava_booking_questionnaires
```

When saving, build the clean structure from the CURRENT defaults/current settings and copy only approved editable values from POST.

Editable values:
- enabled
- title.ar/title.en
- description.ar/description.en
- group label ar/en
- field label ar/en
- field required
- option label ar/en

Never trust POST for:
- questionnaire type key
- group identity outside known schema
- field id
- field type
- option internal value

Preserve field IDs/types/internal option values from the canonical schema.

Do not change the current logic that decides when Beginning, Midpoint or Impact appears.

## D. Response discovery

Source post type:

```text
vava_booking
```

Relevant metadata:

```text
_vava_booking_questionnaire
_vava_booking_impact_questionnaire
_vava_booking_customer
_vava_booking_service_title
_vava_booking_date
_vava_booking_time
_vava_booking_status
_vava_booking_language
```

Prefer existing helper:

```php
vava_booking_questionnaire_all_booking_data( $booking_id )
```

Expected normal questionnaire payload:

```php
array(
  'type'         => 'beginning' | 'midpoint',
  'title'        => '...',
  'language'     => 'ar' | 'en',
  'completed_at' => 'Y-m-d H:i:s',
  'answers'      => array(...),
  'snapshot'     => array(...),
)
```

Impact uses the same payload shape under its separate meta key.

Flatten each questionnaire payload into one admin response record. A single booking can therefore yield two response rows when it has both an initial/follow-up questionnaire and an Impact questionnaire.

Do not include digital/tangible product orders in this screen. Respect the current booking/order distinction if helper functions are available; otherwise exclude records with a recognized non-booking `_vava_booking_order_type`.

## E. Human-readable answer rendering

When rendering/exporting an answer:

1. Find the field in `snapshot.groups[*].fields[*]` by field ID.
2. Use snapshot field label in the submission language where possible.
3. For `radio`, translate the stored internal value using the matching snapshot option.
4. For `checkboxes`, translate each stored internal value and join labels.
5. For text/textarea/number, use sanitized stored text.
6. If snapshot is missing, fallback to current questionnaire settings.
7. Never show raw serialized PHP arrays.

## F. Admin responses UI

Recommended cards at top:
- Total responses
- Journey Start count
- Mid-Journey count
- Journey Impact count

Filters:
- type
- search
- date_from
- date_to

Suggested sanitized query args:

```text
questionnaire_type
questionnaire_search
questionnaire_date_from
questionnaire_date_to
paged
```

Response table:
- Booking #
- Questionnaire
- Completed
- Customer
- Email
- WhatsApp
- Service
- Appointment
- Status
- Language
- View

Use `<details>` for the response detail if a no-JS implementation is easiest and more robust.

Pagination target: 20-25 response rows per page.

## G. XLSX export

Register only an authenticated admin-post action:

```php
add_action( 'admin_post_vava_questionnaires_export', '...' );
```

Do NOT add `admin_post_nopriv_*`.

Before output:
- capability check
- nonce check
- sanitize filters
- `nocache_headers()`

### XLSX generation without dependencies

When `ZipArchive` exists, generate a minimal valid XLSX ZIP containing at least:

```text
[Content_Types].xml
_rels/.rels
xl/workbook.xml
xl/_rels/workbook.xml.rels
xl/worksheets/sheet1.xml
xl/styles.xml
```

Use inline-string cells (`t="inlineStr"`) rather than formulas/shared strings. XML-escape all content.

Do not write user-controlled content into formula cells.

Workbook filename example:

```text
vava-questionnaire-responses-2026-09-17.xlsx
```

Columns:
- Booking ID
- Questionnaire Type
- Questionnaire Title
- Completed At
- Customer Name
- Customer Email
- WhatsApp
- Service
- Appointment Date
- Appointment Time
- Booking Status
- Language
- dynamic question columns

Dynamic question columns can be the union of question IDs/labels across the currently exported responses. Keep deterministic order by questionnaire snapshot/group/field order where possible.

Arabic text must remain UTF-8.

### Fallback

If ZipArchive is unavailable:
- export CSV with UTF-8 BOM
- `text/csv; charset=UTF-8`
- filename `.csv`
- quote fields correctly
- sanitize values beginning with `=`, `+`, `-`, or `@` to prevent formula injection in Excel

## H. Styling

Admin-only styling can be inline on the page or loaded from a small admin stylesheet. Keep it scoped under a wrapper such as:

```text
.vava-questionnaire-dashboard
```

Match the existing VAVA admin visual language where practical.

Do not enqueue this CSS on the front end.

## I. Regression constraints

The patch must not change:
- questionnaire front-end HTML/JS unless absolutely required for compatibility
- existing question routing logic
- booking submission payload behavior
- booking payment state
- customer account creation
- session availability
- Paymob callbacks
- bank receipt handling
- digital products
- protected PDF reader
- HMAC/watermarking

## J. Acceptance tests

Static:
- PHP lint on every modified PHP file.
- `git diff --check`.
- no tracked backup files.

Admin:
- submenu appears under Bookings.
- only authorized admin can access it.
- 3 questionnaire definitions load.
- settings save to canonical Booking page meta.
- page still loads after save.
- response list detects all available response types.
- historical snapshot labels are used.
- pagination/filter/search operate without warnings.

Export:
- authenticated nonce-protected export only.
- valid `.xlsx` when ZipArchive exists.
- Arabic text remains readable.
- downloaded file contains metadata + answers.
- CSV fallback only when native XLSX generation is not possible.

Regression:
- make a dry/static review showing no changes to payment/PDF/reader/customer-account modules.
- no Git push.
