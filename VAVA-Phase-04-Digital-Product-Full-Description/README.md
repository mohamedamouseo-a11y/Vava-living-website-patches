# VAVA Phase 04 — Digital Product Full Description

## Scope

Phase 04 adds one bilingual **Full product description** field to VAVA digital products while preserving the existing short description.

The approved behavior is:

- Existing `description` remains the **short description** used on product cards.
- New `full_description` is localised separately for Arabic and English.
- The field appears only for **digital products** in the VAVA Selections editor.
- The product detail / inline reader uses `full_description` when present.
- If a full description is empty, product details safely fall back to the existing canonical catalogue description or short description; no content is invented.
- Canonical existing digital products may prefill the field from their existing catalogue detail description.
- New products start with one empty full-description field per language.
- No duplicate full-description field is permitted.

## Reference files

Only these theme files are expected to require Phase 04 code changes:

- `wp-content/themes/vava-living-theme-ar-v1/inc/selections-vava.php`
- `wp-content/themes/vava-living-theme-ar-v1/inc/digital-products-vava.php`

Patch reference:

- `patches/phase04-full-description.patch`

## Acceptance test

1. Open the VAVA Selections page editor.
2. Open **Digital products**.
3. Confirm every existing product shows:
   - Short description
   - Full product description
4. Edit Arabic short + full descriptions and English short + full descriptions with visibly different test values.
5. Save / Update.
6. Reload the editor.
7. Confirm all four values remain independently saved.
8. Open the frontend product list: the card must still show the short description.
9. Open product details: it must show the full description.
10. Add a new digital product and repeat the save/reload/detail test.

## Out of scope

Do not modify Phase 01 login, Phase 02 branding, Phase 03 paths images/cache busting, protected PDF upload/processing, questionnaires, sessions, bookings, policies, Google Meet/Zoom, Developer Hub, WordPress Core, or unrelated product fields.

## Deployment rule

Apply to the actual live WordPress installation only after backing up each affected live file. The patch repository is the source package; OpenHands must not push or modify GitHub during live deployment.