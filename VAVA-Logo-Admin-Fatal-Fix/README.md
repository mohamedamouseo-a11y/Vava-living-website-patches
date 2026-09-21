# VAVA — Admin Fatal After Logo Cache Fix

Work only on current live `/public_html`.

Symptom:
Opening `/wp-admin/update-core.php` now shows the WordPress "critical error" screen after the recent logo cache-busting changes.

Scope:
Investigate ONLY this regression and the recently edited logo-cache files.

First:
1. Read the newest relevant PHP/WordPress error log entry for the failing `update-core.php` request.
2. Identify the exact fatal error, file and line.
3. Do not scan the whole project.

Recently changed logo-cache files:
- `functions.php`
- `inc/admin-brand-vava.php`
- `inc/booking-vava-safe-v4r10.php`
- `inc/booking-vava.php`
- `inc/digital-products-commerce-vava.php`
- `inc/homepage-metaboxes.php`
- `header-home.php`
- `page-templates/homepage.php`
- `template-parts/shared-footer.php`

Fix:
Apply the smallest safe correction needed so wp-admin, especially `update-core.php`, loads normally while preserving deterministic logo cache-busting.

Do not:
- change CSS/layout/logo image
- touch unrelated modules
- update WordPress/plugins/themes
- push/commit/create PR
- redesign the cache solution unless the fatal requires it

Validation:
- Verify `/wp-admin/update-core.php` no longer fatals.
- Verify `vava_logo_url()` still returns the versioned logo URL.
- Run PHP lint on changed PHP files if available.
- Run `git diff --check`.

Leave changes only in `/public_html`.
