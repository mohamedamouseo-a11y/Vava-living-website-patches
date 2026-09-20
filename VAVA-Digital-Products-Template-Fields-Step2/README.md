# VAVA — Digital Product Template Fields — Step 2

Work only on current live `/public_html`.

Goal of THIS STEP ONLY:
Make the saved Digital Product fields from Step 1 render in the existing frontend inline-reader template.

Targets:
- `wp-content/themes/vava-living-theme-ar-v1/inc/digital-products-vava.php`
- only touch `inc/selections-vava.php` if needed for read compatibility; do not alter Step 1 UI/save behavior.

Use saved product data from the existing Selections model:
Localized:
- category
- question
- inside
- ideal
- full_description
Shared:
- pages

Required behavior:
1. In `vava_digital_product_reader_data()`, saved admin values must override catalogue values when present.
2. New digital products not present in the hard-coded catalogue must still render the full current reader structure.
3. `inside` and `ideal` saved arrays must render in the existing bullet-list sections.
4. Saved `pages` must show in Product Details.
5. Preserve existing title, price, currency, PDF cover/file flow and current reader layout.
6. Existing six products must remain unchanged visually if no saved override exists.
7. Do not add migrations in this step.
8. Do not duplicate descriptions.

Do NOT touch:
payments, orders, PDF processing/security, HMAC, watermarking, customer accounts, booking, questionnaires, Paths, Journal, Contact, Legal, Developer Hub.

Validation:
- Confirm one existing product with saved Step 1 values renders them in the current reader.
- Confirm one new/non-catalogue digital product can render the same structure from saved data.
- Run PHP lint if available.
- Run `git diff --check`.

Do not push, merge, or modify remote main.
Leave changes only in `/public_html`.
