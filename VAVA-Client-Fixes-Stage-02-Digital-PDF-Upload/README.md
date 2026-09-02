# VAVA Client Fixes — Stage 02: Digital PDF Upload

## Scope
This stage addresses the protected digital-product PDF upload experience in the VAVA Selections admin editor.

Client issue:
- Replacing/uploading a protected PDF can take time, progress feedback was misleading, upload errors were generic, and the progress UI could be visually clipped inside the product editor card.

## Current-code findings
1. The browser originally accepted PDFs up to a hard-coded 50 MB while PHP/WordPress may allow less.
2. Network upload progress originally stopped around 88%, then protected-page processing restarted near 1%, making progress appear to move backwards.
3. Backend upload failures were reduced to a generic Arabic error.
4. The protected-PDF field CSS uses `height:100%` / `min-height:100%` inside a product card that clips overflow. When the dynamic progress box becomes visible, the field does not grow naturally and the progress/status area can be cut off at the bottom of the container.
5. `.vava-media-actions` also used `margin-top:auto`, which makes the dynamic vertical layout less reliable when the progress block appears.

## Implementation
Primary Stage 02 patch:
- Computes the effective PDF upload limit as the smaller of 50 MB and `wp_max_upload_size()`.
- Sends the effective limit to admin JavaScript.
- Validates the selected PDF against the real server limit.
- Returns clearer PHP upload errors.
- Handles HTTP 413, interrupted requests, and server errors.
- Uses 0–90% for network upload and 90–100% for protected-page processing.
- Shows 100% briefly when processing reaches `ready`.

Layout hotfix:
- Keeps the protected-PDF field height content-driven instead of forcing `height:100%`.
- Removes the inherited `min-height:100%` behavior from the inner media field.
- Keeps the visible progress component in normal document flow at full width.
- Gives progress, actions, and the private-file note their own vertical space so none of them are clipped.
- Replaces the `margin-top:auto` action placement with predictable spacing below the progress component.

## Explicitly out of scope
- Digital product descriptions/full descriptions.
- Product pricing, checkout, payments, customer library, or access control.
- PDF conversion architecture.
- Login behavior.
- Tagline/footer wording.
- Questionnaire changes.
- Any other client request.

## Files
- `patches/0002-fix-digital-pdf-upload-progress-and-limits.patch`
- `patches/0003-fix-digital-pdf-progress-layout.patch`
- `OpenHands-Prompt.txt`

## Source files affected across Stage 02
- `wp-content/themes/vava-living-theme-ar-v1/inc/digital-products-commerce-vava.php`
- `wp-content/themes/vava-living-theme-ar-v1/assets/js/admin-digital-products.js`
- `wp-content/themes/vava-living-theme-ar-v1/assets/css/admin-selections.css`

## Acceptance checks
- A PDF larger than the effective server limit is rejected with the actual allowed size.
- Upload/processing progress does not move backwards.
- Ready state visibly reaches 100%.
- Upload errors are meaningful where possible.
- While uploading, the entire progress component is visible below the PDF preview and above the action buttons.
- The progress label, percentage, track, buttons, and protected-file note are not clipped by the product card.
- The product editor card grows vertically when progress is visible and returns to its normal layout when progress is hidden.
- Existing protected PDF processing, storage, and access-control behavior remains unchanged.
