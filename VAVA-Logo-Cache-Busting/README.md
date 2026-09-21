# VAVA — Logo Cache Busting

Work only on the CURRENT live `/public_html`.

## Goal
Fix stale VAVA logo caching across browsers/devices when `assets/images/vava-logo.png` is replaced with a newer file using the same filename.

## Required change
Add a small reusable helper that returns the VAVA logo URL with a deterministic cache-busting query string based on the logo file modification time.

Preferred behavior:
`.../assets/images/vava-logo.png?v=<filemtime>`

Use the existing theme asset/version architecture where possible. Do not use random timestamps.

Then replace direct VAVA logo URL usage so the same versioned logo URL is used anywhere the theme currently outputs `assets/images/vava-logo.png`, including the relevant frontend/header/footer/admin/reader uses that are actually present in the CURRENT live code.

Important:
- Keep the exact same logo file and design.
- Do not change dimensions, CSS, layout, or image content.
- Do not rename the logo file.
- Do not change generic asset behavior unless required.
- Do not touch unrelated images/assets.
- Do not touch bookings, questionnaires, digital product logic, PDF security, payments, customer accounts, Paths, or Developer Hub.

## Safety
The helper must gracefully fall back to the normal logo URL if the file is missing or its mtime cannot be read.

## Validation
- Confirm rendered logo URLs include a version query string.
- Confirm the version is derived from the current logo file mtime.
- Confirm all edited PHP files remain valid if PHP CLI is available.
- Run `git diff --check`.

Do not push, merge, create PRs, or modify remote main.
Leave changes only in `/public_html` for review.
