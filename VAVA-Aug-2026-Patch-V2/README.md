# VAVA Living — August 2026 Patch V2

Additive V2 patch for the August 2026 client review. Apply it **on top of the existing V1 August patch**.

## Git safety

- Production repo: `mohamedamouseo-a11y/Vava-living-website`
- Existing working branch: `patches/vava-aug-2026-requirements`
- **Never commit/push/merge/rebase/fast-forward production `main`.**
- Patch repo: `mohamedamouseo-a11y/Vava-living-website-patches`
- This V2 folder is intentionally stored on the patch repo's `main`; this is not production `main`.

## Files in V2

- `wp-content/mu-plugins/vava-aug-2026-v2-fixes.php`
- `wp-content/mu-plugins/vava-aug-2026-v2-admin.js`
- `assets/vava-logo-new.png.base64.txt`
- `OpenHands-Prompt.txt`
- `README.md`

Keep V1 installed, especially:

- `wp-content/mu-plugins/vava-aug-2026-fixes.php`
- root `.user.ini`

## Implemented by V2

1. Login page forced `no-store/no-cache` to stop stale login nonces and the refresh-first problem.
2. Tagline forced to `نحو حياة مزدهرة`; lingering `حيث تزدهر الحياة` output is replaced.
3. Remaining `❧` and equivalent entities are replaced with `✦`.
4. Homepage/Paths image URLs receive an attachment revision and common caches are purged after homepage/Paths saves.
5. Session summary cards use an equal auto-fit grid; truly empty cards are hidden so no blank hole remains.
6. Compatibility support for older live copies that do not yet contain `الوصف الكامل للمنتج`: V2 injects a single full-description textarea and preserves posted `full_description` in the selections meta after the legacy saver runs.
7. Protected digital PDF upload now has a V2 chunked-upload path (4 MB chunks, existing 50 MB application cap) so a large replacement PDF is not one long upload request. Upload progress is shown separately from the existing async PDF-processing stage.
8. Existing V1 questionnaire CSV/Excel export, booking policy, and daily-capacity enforcement remain active.

## New logo payload

The approved new VAVA logo supplied in the August requirements is stored in this patch as Base64 text:

`assets/vava-logo-new.png.base64.txt`

During deployment decode it to:

`wp-content/themes/vava-living-theme-ar-v1/assets/images/vava-logo.png`

Linux example:

```bash
base64 -d VAVA-Aug-2026-Patch-V2/assets/vava-logo-new.png.base64.txt > wp-content/themes/vava-living-theme-ar-v1/assets/images/vava-logo.png
```

Create a backup of the current logo before replacing it. The decoded file is PNG.

## Important source-version mismatch

The current production GitHub `main` already contains newer digital-product code, including the full product description field and reader support, while the reviewed live server showed an older editor. OpenHands must compare the live/project worktree with current source before overwriting any theme file. Do not blindly replace entire theme files.

## Journey Impact questionnaire — do not guess

Exact form supplied by the client:

`https://docs.google.com/forms/d/e/1FAIpQLSeeZNwb_t3j1W3t5PZbDoRCbKfWHtRAajJWOIkeG2JD5kmjxQ/viewform?usp=sharing&ouid=110601807575632792329`

The exact form questions were not available reliably during patch preparation. Only update Journey Impact if the exact form is accessible in the deployment environment. Match every question, order, type, option, and required/optional state exactly. Otherwise report this item BLOCKED instead of guessing.

## Google Meet / Zoom

Do not create fake/static links. Automatic unique meeting creation/email requires a selected provider plus valid OAuth/API credentials. If those are not already available, report BLOCKED and leave booking stable.

## Protected PDF processing prerequisite

Chunking fixes the upload request. The subsequent protected-page conversion still requires the host to support the converter used by the current VAVA implementation (Imagick/Ghostscript support or `pdftoppm`). If conversion fails, report the exact host error rather than hiding it.

## Validation

```bash
php -l wp-content/mu-plugins/vava-aug-2026-v2-fixes.php
git diff --check
```

Then verify:

- login works on first load without needing refresh;
- decoded new logo appears wherever `vava-logo.png` is used;
- tagline is `نحو حياة مزدهرة`;
- no visible `❧` remains;
- changing homepage/Paths images is visible after save without stale image output;
- digital editor has exactly one full-description field and saves it;
- replacement PDF upload progresses through the upload phase and then shows processing separately;
- session-detail blocks fill the container with no empty gap;
- V1 CSV export, exact booking policy, and 205-minute/category daily caps still work.
