# VAVA — Digital Product Template Fields — Step 1

Work only on the current live `/public_html`.

Goal of THIS STEP ONLY:
Add and persist the missing Digital Product admin fields in the existing VAVA Selections product editor.

Target:
`wp-content/themes/vava-living-theme-ar-v1/inc/selections-vava.php`

For DIGITAL products only, add these editable fields to each existing product card:
- category
- question
- inside
- ideal
- pages

Rules:
- category/question are localized text fields.
- inside/ideal are localized textarea fields, one item per line.
- pages is a shared field.
- Keep all existing fields and behavior unchanged.
- Do not touch frontend reader rendering yet.
- Do not add migration yet.
- Do not touch PDF, payments, orders, security, bookings, questionnaires, Paths, or any unrelated module.

Persistence:
- Extend the existing localized product save/sanitize flow to preserve category, question, inside, ideal.
- Convert inside/ideal textarea lines into trimmed arrays; ignore blank lines.
- Extend existing shared product save/load flow to preserve pages.
- Ensure add-product template contains these fields.
- Existing products with missing new values must continue to save/load safely.

Validation:
- Save/reload one digital product and confirm all five new fields persist.
- Confirm tangible products are unchanged.
- Run PHP lint if available.
- Run `git diff --check`.

Do not push, merge, commit to remote, or modify remote main.
Leave changes only in `/public_html`.
