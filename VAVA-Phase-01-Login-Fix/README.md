# VAVA Phase 01 — Login Fix

Scope: **login only**. Do not modify any other VAVA requirement in this phase.

## Confirmed root cause
The VAVA theme registers a custom press-and-hold login gate. Its JS hides and disables the native WordPress submit button until two AJAX requests return a temporary token. The theme also rejects a normal login POST when that token is absent. This creates a fragile first-load flow and is the component to retire.

## Fix strategy
1. Add `wp-content/mu-plugins/vava-phase-01-login-fix.php` as a safety layer.
2. Patch `inc/admin-brand-vava.php` so it keeps the VAVA login CSS/branding but no longer loads `login-vava.js`, no longer injects the press-and-hold UI, and no longer rejects authentication when a hold token is missing.
3. Add server-level no-cache rules for `wp-login.php` only when `.htaccess`/Apache/LiteSpeed is applicable.
4. Purge WordPress/page/server cache after applying.

## Files in this phase
- `wp-content/mu-plugins/vava-phase-01-login-fix.php`
- `patches/admin-brand-vava-login.patch`
- `patches/htaccess-login-no-cache.txt`
- `OpenHands-Prompt.txt`

## Must remain unchanged
- WordPress Core.
- Production GitHub `main`.
- Booking, products, questionnaires, Paths, session layouts, logo, and all other VAVA features.
- Previous V1/V2/V3 patch files.

## Technical acceptance checks
From the server after deployment:
- PHP lint passes on the MU plugin.
- Login HTML does **not** contain `login-vava.js`.
- Login HTML does **not** contain `data-vava-login-hold`.
- `wp-login.php` returns no-store/no-cache headers.
- The normal username/password form still exists.

## User live acceptance test
1. Open a brand-new Incognito/Private window.
2. Open `/wp-login.php` directly.
3. Enter valid credentials.
4. Click **Log In / تسجيل الدخول once** without refreshing.
5. PASS = Dashboard opens on the first attempt with no press-and-hold step and no refresh.
