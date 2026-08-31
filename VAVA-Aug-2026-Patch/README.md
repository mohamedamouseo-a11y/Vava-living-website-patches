# VAVA Living — August 2026 Requirements Patch

Branch: `patches/vava-aug-2026-requirements`
Base: `main`

## Implemented
- No-cache handling for `wp-login.php`.
- Tagline changed to `نحو حياة مزدهرة`.
- Replace retired `❧` with `✦` on server-rendered public output.
- Mobile overflow containment and cleanup of genuinely empty sections.
- Paths-page cache invalidation/revision handling after image changes.
- Remove saved midpoint question `كيف كان نشاطك اليومي` when present as a DB override.
- Excel-compatible CSV export for native booking questionnaire answers.
- Apply the supplied Arabic booking/payment/refund/cancellation policy.
- Daily booking limits: 1 comprehensive, 2 follow-up, 2 inquiry, 1 exploratory, maximum 205 consultation minutes/day.
- PHP upload/runtime settings appropriate for the existing protected-PDF 50MB limit.
- Recover queued/processing protected-PDF jobs when their cron event is missing.

## Already present in current main
- Digital product editor already has a separate Full Product Description field and stores/renders `full_description`.
- Protected PDF code already accepts files up to 50MB.

## Requires external input — not guessed
1. New logo file was not attached.
2. Exact Journey Impact questions are referenced via Google Form but were not retrievable; existing questions remain unchanged.
3. Google Meet/Zoom provider credentials were not supplied.
4. Maintenance owner cannot be determined from source code.
5. Production DB host cannot be verified because production `wp-config.php` is not in the repository.

## Current digital product payment behavior
Digital-product checkout is bank-transfer/manual-review only. Access becomes active after manual transfer approval. There is no current Paymob/card checkout path for digital products.

## Validation
Test login in Edge desktop and Chrome Android; Paths image replacement; questionnaire CSV; booking policy; four daily capacity limits and 205-minute ceiling; protected PDF replacement/processing; mobile overflow; and absence of `❧`.

No change in this patch merges or pushes to `main`.
