# VAVA Automation Phase 1 — SMTP Settings + Test Email

## Goal
Add a small VAVA-managed SMTP settings screen so all existing `wp_mail()` flows (booking confirmation, meeting-link resend, future reminders) can use one reliable SMTP configuration.

This phase is ONLY email transport/settings.
Do NOT implement Google Meet API or reminders yet.

## Current architecture
The active theme is:
`wp-content/themes/vava-living-theme-ar-v1/`

Existing booking emails already use `wp_mail()`, so the SMTP layer should configure WordPress PHPMailer globally rather than rewriting booking email logic.

## Modify / create ONLY
1. Create:
   `wp-content/themes/vava-living-theme-ar-v1/inc/mail-settings-vava.php`
2. Modify:
   `wp-content/themes/vava-living-theme-ar-v1/functions.php`
   only to require the new file.

Use CURRENT live files via FTP as source of truth.

---

## Settings location
Add a submenu under the existing VAVA Bookings CPT menu:
Parent:
`edit.php?post_type=vava_booking`

Menu title:
`إعدادات البريد`

Page title:
`إعدادات البريد SMTP`

Capability:
Prefer existing `vava_booking_admin_capability()` when available; otherwise `manage_options`.

---

## Stored option
Use one WordPress option:
`_vava_mail_smtp_settings`

Fields:
- enabled (0/1)
- host
- port
- encryption: none / tls / ssl
- username
- password
- from_email
- from_name

Default port:
- 587 for tls
- 465 for ssl
- 25 otherwise

Do not store test-recipient email as a permanent setting.

Password UX:
- password input must render blank
- blank submitted password preserves current saved password
- never echo the saved password back into HTML
- never log it

---

## SMTP hook
Use `phpmailer_init`.

When SMTP is enabled and host is non-empty:
- call `isSMTP()`
- set Host
- set Port
- set SMTPAuth = true only when username is non-empty
- set Username
- set Password
- set SMTPSecure only for tls/ssl
- set From / FromName when valid
- keep charset UTF-8
- do not disable certificate verification
- do not change existing booking email bodies or recipients

When SMTP is disabled:
- leave WordPress mail behavior unchanged.

---

## Save handler
Use a normal admin POST handler:
`admin_post_vava_save_mail_settings`

Requirements:
- nonce
- capability check
- sanitize all fields
- validate port 1..65535
- validate from_email when provided
- preserve old password if submitted password is blank
- save settings option
- redirect back with success/error query flag

---

## Test email
On the same settings page add:
- test recipient input, default visually to current admin email
- button: `إرسال رسالة تجريبية`

Handler:
`admin_post_vava_send_test_email`

Requirements:
- nonce
- capability check
- validate recipient email
- call `wp_mail()` so the same SMTP hook is tested
- subject: `VAVA SMTP Test`
- short bilingual body
- redirect back with PASS/FAIL notice
- do not expose SMTP password or raw PHPMailer exception details in UI/logs

---

## Admin notices
Show concise notices:
- Settings saved
- Test email sent
- Test email failed
- Validation error

---

## Compatibility / safety
Do NOT modify:
- booking-vava-safe-v4r10.php
- booking flow
- meeting link feature
- Paymob
- bank transfer
- questionnaires
- customer accounts
- Developer Hub
- frontend templates

Do NOT add external libraries.
Do NOT send a real test email during validation.
Do NOT push.

---

## Validation
1. only new mail module + functions.php require changed
2. PHP syntax / admin no fatal
3. settings submenu registers under VAVA Bookings
4. password is never rendered
5. blank password preserves saved value
6. `phpmailer_init` only activates when enabled
7. SMTPAuth depends on username
8. encryption whitelist enforced
9. test action calls `wp_mail()`
10. no real email sent during validation
11. booking files untouched
12. git diff --check
