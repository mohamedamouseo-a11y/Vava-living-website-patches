# VAVA Living — Phase 02 Branding & UI

Independent Phase 02 patch. Scope is intentionally limited to branding/general UI cleanup.

## Scope

1. Replace the approved shared VAVA logo at `assets/images/vava-logo.png`.
2. Change Arabic tagline `حيث تزدهر الحياة` to `نحو حياة مزدهرة`.
3. Remove visible retired glyph `❧` and equivalent entities, replacing them with `✦`.
4. Stop page-level horizontal movement on mobile without changing desktop layout.

## Out of scope

Do not touch Login, Paths images, digital products, PDF upload, sessions, questionnaires, booking limits, policies, Meet/Zoom, or any other phase.

## Patch files

- `wp-content/mu-plugins/vava-phase-02-branding-ui.php`
- `patches/source-replacements.txt`
- `OpenHands-Prompt.txt`

## Approved logo payload

Reuse the approved August logo already stored in this patch repository:

`VAVA-Aug-2026-Patch-V2/assets/vava-logo-new.png.base64.txt`

Decode it to:

`wp-content/themes/vava-living-theme-ar-v1/assets/images/vava-logo.png`

Do not use a different logo and do not create tracked backup files.

## Current-source observations

The current homepage directly renders the Arabic intro as `حيث تزدهر الحياة`, and both homepage/internal headers plus the shared footer reference `assets/images/vava-logo.png`. The admin brand also localizes the same logo URL. Phase 02 updates the direct source where appropriate and keeps an MU-plugin compatibility backstop for legacy stored output.

## Validation

Run when available:

```bash
php -l wp-content/mu-plugins/vava-phase-02-branding-ui.php
php -l wp-content/themes/vava-living-theme-ar-v1/header-home.php
php -l wp-content/themes/vava-living-theme-ar-v1/inc/admin-brand-vava.php
git diff --check
```

Then inspect the diff and confirm only Phase 02 files/changes are present.

Do not push from OpenHands. Leave the website worktree ready for the user's Developer Hub workflow.
