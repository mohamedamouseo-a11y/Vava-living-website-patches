# VAVA Client Fixes — Stage 02: Digital PDF Upload

## Scope
This stage addresses one confirmed outstanding client issue only:

- Replacing/uploading a protected PDF for a digital product takes too long, the progress indicator does not complete correctly, and the admin may eventually show an upload failure.

## Current-code findings
The existing implementation has two important UX/runtime mismatches:

1. The browser accepts PDFs up to a hard-coded 50 MB, while the actual PHP/WordPress upload ceiling may be lower (`upload_max_filesize` / `post_max_size`). A file can therefore pass the browser check but be rejected by the server.
2. Upload progress is capped at 88%, then the processing status starts again around 1%. This causes the visible progress to appear stuck or move backwards even when the upload itself succeeded.

The current backend also collapses several PHP upload failures into the generic Arabic message `تعذر رفع الملف.`.

## Implementation
The patch:

- Computes the effective PDF upload limit as the smaller of the product cap (50 MB) and `wp_max_upload_size()`.
- Sends the effective limit to the admin JavaScript.
- Validates the selected PDF against the real server limit before starting the upload.
- Returns specific messages for common PHP upload errors.
- Handles HTTP 413, interrupted requests, and server errors more clearly in the admin UI.
- Uses 0–90% for the network upload phase and 90–100% for the protected-page processing phase, preventing the progress indicator from jumping backwards.
- Shows 100% briefly when processing reaches `ready` before hiding the progress UI.

## Explicitly out of scope
- Digital product descriptions/full descriptions.
- Product pricing, checkout, payments, customer library, or access control.
- PDF conversion architecture (Imagick/Ghostscript/pdftoppm).
- Login behavior.
- Tagline/footer wording.
- Questionnaire changes.
- Any other client request.

## Files
- `patches/0002-fix-digital-pdf-upload-progress-and-limits.patch`
- `OpenHands-Prompt.txt`

## Acceptance checks
- A PDF larger than the effective server limit is rejected immediately with the actual allowed size shown.
- A valid PDF upload can progress through upload and processing without the percentage moving backwards.
- Ready state visibly reaches 100%.
- PHP upload failures return a meaningful message where possible.
- Existing protected PDF processing and access-control behavior remains unchanged.
- Only these two source files change:
  - `wp-content/themes/vava-living-theme-ar-v1/inc/digital-products-commerce-vava.php`
  - `wp-content/themes/vava-living-theme-ar-v1/assets/js/admin-digital-products.js`
- Nothing is pushed or merged to the source repository `main` branch.
