# VAVA — Digital Products Template Fields

## Goal
Make new Digital Products created from the existing VAVA Selections/Digital Products admin use the SAME inline-reader template and marketing structure as the six current products.

Work against the CURRENT live `/public_html`. Do not overwrite newer unrelated live changes.

## Current architecture to preserve
Existing admin/editor:
`wp-content/themes/vava-living-theme-ar-v1/inc/selections-vava.php`

Existing digital reader/catalogue:
`wp-content/themes/vava-living-theme-ar-v1/inc/digital-products-vava.php`

Existing commerce/PDF/security must remain unchanged.

The current editor already supports:
- add/remove product
- enabled state
- title
- card description
- full description
- price/currency
- digital PDF/cover workflow

The current six product detail sections are still largely code-managed in `vava_digital_products_catalog()`.

## Required final admin UX
For DIGITAL products only, extend each existing product card in the current dashboard editor with bilingual editable fields:

1. Category / التصنيف
2. Question / السؤال الذي يجيب عنه الدليل
   Example: `ماذا يناسب كل دوشا بسرعة؟`
3. Full Description / الوصف الكامل (keep existing field)
4. What you will find inside / ماذا ستجد داخل الدليل؟
5. Ideal for you if / مناسب لك إذا كنت...
6. Pages / عدد الصفحات

Keep the existing title, short/card description, price, currency, button text, enabled state and PDF controls.

For the two list sections (`inside`, `ideal`), a simple textarea with ONE ITEM PER LINE is preferred. The frontend must turn those lines into the same bullet-list structure already used by current products.

Arabic/English values must remain independent/localized, while shared operational fields (price/pages/enabled/PDF) remain synchronized appropriately.

## Data model
Do NOT create product pages or a second product system.

Extend the EXISTING VAVA Selections product data model.

Localized product data should support:
- uid
- title
- description (short/card description)
- full_description
- category
- question
- inside (array/list)
- ideal (array/list)
- currency
- button_text

Shared product data should support:
- uid
- image_id/fallback_asset as currently used
- price
- pages
- enabled

Sanitize all new values.

For `inside` and `ideal`:
- accept textarea input one item per line;
- normalize line endings;
- trim blank lines;
- save as arrays;
- cap to a reasonable number such as 20 items.

## Reader behavior
Update `vava_digital_product_reader_data()` so SAVED admin product fields override catalogue detail fields when present.

The same current reader template must render:
- category
- title
- full description
- question block
- inside list
- ideal list
- page count

A newly-added digital product that does NOT exist in the hard-coded catalogue must still render the complete template using saved admin data.

Do not duplicate descriptions.

## Existing six products — migration
Add a SAFE one-time migration for the six current catalogue products.

Goal: populate the newly-editable fields from the current canonical catalogue WITHOUT erasing existing admin edits.

For each existing UID:
- preserve current saved title/short description/full description/price/PDF/visibility;
- fill only missing new fields from current catalogue:
  - category
  - question
  - inside
  - ideal
  - pages
- if current saved full_description is empty, seed it from the catalogue long description.

Do not reset order, price, cover/PDF, enabled state or any existing user edit.

Use a versioned option so the migration runs once.

## Add Product behavior
The existing Add Product button must continue to work.

A newly added digital product should immediately expose all required template fields in the admin and, after save, open in the same frontend inline-reader layout as existing products.

No code edit should be required for future new products.

## Preview
If practical within the existing editor architecture, extend the live admin preview enough to show the new product's title/short description as today. Do not build a second full reader preview if that adds risk.

## Scope protection
DO NOT modify:
- digital product payment/order backend
- protected PDF storage
- PDF processing/conversion
- protected reader security
- HMAC signed URLs
- watermarking
- customer account access
- booking
- questionnaires
- Paths
- Journal
- Contact
- Legal
- Developer Hub
- WordPress core

## Validation
Test on the CURRENT live worktree without making a real paid transaction.

Required:
1. Existing six products still render correctly.
2. Existing admin edits remain intact.
3. Add a temporary/new draft product in admin or via safe local data test:
   - title
   - category
   - short description
   - full description
   - question
   - inside list
   - ideal list
   - pages
4. Save/reload and confirm all fields persist.
5. Confirm the new product renders the SAME reader structure as current products.
6. Verify Arabic and English independently.
7. Verify PDF/payment/security code was not changed.
8. `php -l` all modified PHP files when PHP CLI is available.
9. `git diff --check`.

Do not push, merge or create PRs.
Leave changes only in `/public_html` for the user to review and push from Developer Hub.
