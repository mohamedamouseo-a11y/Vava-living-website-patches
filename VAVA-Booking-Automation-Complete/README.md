# VAVA Booking Automation — Complete Remaining Phases

## Goal
Implement the remaining booking automation in ONE deployment, while keeping the already-live SMTP settings and manual meeting fallback intact.

Final workflow:
Booking confirmed/paid -> Google Meet created automatically -> Meet link saved -> customer details email resent with Meet link -> reminder sent before the session.

This must be safe when Google or SMTP credentials are not configured yet:
- no fatal errors
- no broken bookings
- no duplicate meetings
- no duplicate emails
- no duplicate reminders
- manual meeting link remains available
- automation becomes active automatically once required credentials/settings are connected.

## Current live source of truth
Use CURRENT LIVE FILES via FTP. Do not trust GitHub main over live for:
- manual meeting-link Step 1
- SMTP Phase 1

Active theme:
`wp-content/themes/vava-living-theme-ar-v1/`

## Allowed files
1. CREATE:
   `wp-content/themes/vava-living-theme-ar-v1/inc/google-meet-vava.php`
2. MODIFY:
   `wp-content/themes/vava-living-theme-ar-v1/functions.php`
   only to require the new module.
3. MODIFY:
   `wp-content/themes/vava-living-theme-ar-v1/inc/booking-vava-safe-v4r10.php`
   targeted additions only around the CURRENT live meeting card, email/action labels/status display where needed.

Do NOT modify `inc/mail-settings-vava.php` except if absolutely required for compatibility; the existing SMTP settings module is already live and must not regress.

Do NOT edit legacy `inc/booking-vava.php`.

---

# A. Google Meet Settings + OAuth

Add submenu under:
`edit.php?post_type=vava_booking`

Menu:
`إعدادات Google Meet`

Capability:
`vava_booking_admin_capability()` when available, otherwise `manage_options`.

Store settings:
`_vava_google_meet_settings`

Fields:
- enabled
- client_id
- client_secret
- calendar_id (default: primary)
- automation_enabled (default 1)
- reminder_enabled (default 1)
- reminder_hours (default 24; allow 1..168)

Security:
- client_secret input is always blank when rendered
- blank submitted secret preserves current secret
- never print/log secret
- show generated read-only Redirect URI:
  `admin_url('admin-post.php?action=vava_google_meet_oauth_callback')`
- connection badge: Connected / Not connected

Store OAuth tokens separately:
`_vava_google_meet_tokens`
Fields only:
- access_token
- refresh_token
- expires_at

Never expose tokens in HTML/logs/notices/URLs.

OAuth:
- authorization: https://accounts.google.com/o/oauth2/v2/auth
- token: https://oauth2.googleapis.com/token
- scope: https://www.googleapis.com/auth/calendar.events
- access_type=offline
- prompt=consent
- include_granted_scopes=true
- state transient tied to current admin
- exact redirect URI matching the settings page value
- server-side code exchange using WordPress HTTP API
- require persistent refresh token
- Connect and Disconnect actions with nonce + capability

Handlers:
- `admin_post_vava_google_meet_connect`
- `admin_post_vava_google_meet_oauth_callback`
- `admin_post_vava_google_meet_disconnect`

Implement access-token refresh helper using `wp_remote_post()`.

---

# B. Google Meet Generation

Provide reusable function:
`vava_booking_generate_google_meet( int $booking_id )`

Eligibility:
- post type = vava_booking
- session booking only, never digital/tangible/physical product order
- valid future booking date + time
- integration enabled + connected

Timezone:
Use VAVA booking shared timezone from the current booking configuration when resolvable, otherwise WordPress timezone.
Never assume UTC.

Calendar API:
POST
`https://www.googleapis.com/calendar/v3/calendars/{calendar_id}/events?conferenceDataVersion=1&sendUpdates=none`

Event:
- summary: `VAVA — {service title}`
- description: booking number + short admin reference only
- start.dateTime/timeZone
- end.dateTime/timeZone using booking duration
- conferenceData.createRequest.requestId unique/unpredictable
- conferenceData.createRequest.conferenceSolutionKey.type = `hangoutsMeet`
- no customer attendee in this implementation

Extract Meet URL from:
1. hangoutLink
2. conferenceData.entryPoints where entryPointType=video

Validate final URL:
- HTTPS
- host exactly meet.google.com

Persist:
- existing Step 1 meeting meta:
  - `_vava_booking_meeting_provider = google_meet`
  - `_vava_booking_meeting_url`
  - `_vava_booking_meeting_status = assigned`
  - existing created/updated timestamp/user conventions
- Google meta:
  - `_vava_booking_google_event_id`
  - `_vava_booking_google_event_html_url`

Duplicate protection:
- if a valid meeting URL exists, never create another event
- if event_id exists but Meet URL is missing, GET the existing event and resolve it
- repeated clicks/jobs must never create duplicate events
- use a short booking-level transient/meta lock during generation

If Google returns event but conference link is still pending:
- preserve event ID
- meeting status = generating
- retry SAME event later
- do not create another event

---

# C. Manual Generate UI

In CURRENT live `vava_booking_render_admin_meeting_card()` preserve all manual Step 1 fields/buttons.

Add:
- Google connection badge
- `Generate Google Meet` button when no meeting exists
- `تحديث Google Meet` when event exists but link is pending
- clear link to Google Meet settings when disconnected
- success state when Meet exists

Handler:
`admin_post_vava_booking_generate_google_meet`

Requirements:
- nonce tied to booking
- capability
- session booking only
- calls the same reusable generator
- redirects back with concise notice
- must NOT send customer email by itself

Existing:
- Save only
- Save & Send
must stay unchanged.

---

# D. Automatic Generation + Automatic Email

Automation applies only to FUTURE SESSION bookings when:
- booking status is `confirmed` or `paid`
OR
- effective payment status is `paid`

Never automate cancelled/refunded/product orders.

When eligible:
1. generate/resolve Google Meet idempotently
2. once a valid Meet URL exists, resend existing booking details email using:
   `vava_booking_send_details_email( $booking_id, true, false )`
3. this existing email already contains the meeting block from Step 1; do not create a competing details-email template.

Meta for idempotency:
- `_vava_booking_meeting_auto_email_sent_at`
- `_vava_booking_meeting_auto_email_last_attempt_at`

Rules:
- send the auto meeting-details email only once after Meet generation
- mark sent only if the existing function returns true
- if email fails, keep pending and allow a later retry
- throttle retries to prevent spam/rapid loops
- absence of SMTP credentials must NOT break Meet creation or booking flow

Trigger automation:
- hook relevant booking/payment status meta transitions so confirmed/paid bookings are queued quickly
- schedule a small one-off event rather than doing slow Google network work inside payment/webhook/admin status requests
- use:
  `vava_booking_automation_process_booking`

Also add an hourly catch-up worker:
`vava_booking_automation_hourly`

It should process only a bounded batch of relevant upcoming bookings (e.g. max 20 per run), not an unlimited scan.

The hourly worker exists to recover from:
- delayed Google conference creation
- temporary Google API failure
- temporary email/SMTP failure
- credentials being connected after the booking was already confirmed

No duplicate events or duplicate successful emails.

---

# E. Reminder Automation

Default reminder:
24 hours before the booked session.

Use settings:
- reminder_enabled
- reminder_hours

Schedule per-booking one-off reminder when an eligible booking has a valid meeting URL:
hook:
`vava_booking_meeting_reminder_event`

Meta:
- `_vava_booking_meeting_reminder_scheduled_at`
- `_vava_booking_meeting_reminder_sent_at`
- `_vava_booking_meeting_reminder_last_attempt_at`

Reminder must contain:
- session/service name
- date
- time
- Google Meet join link/button
- booking number
- questionnaire received line ONLY when `_vava_booking_questionnaire` exists

Language:
use existing `_vava_booking_language`.

Send through normal `wp_mail()`, therefore existing SMTP settings apply automatically.

Idempotency:
- reminder sent once only
- mark sent only on successful `wp_mail()`
- if reminder time has passed but session is still in the future, hourly catch-up may send it
- never send reminder after the session start
- retry failures with throttling
- if meeting URL is unavailable at reminder time, let automation resolve/generate it first; do not send a reminder without a valid Meet link

Cancellation/refund:
- reminder handler must re-check eligibility at execution time and exit silently for cancelled/refunded/non-session bookings.

---

# F. Dashboard Status

Keep existing appointment column and meeting card.

Enhance compact status without adding a new table column:
- Google Meet ✓
- Google Meet generating
- Meeting pending
- Email sent / Email pending
- Reminder sent / Reminder scheduled where practical without clutter

Booking details meeting card should show concise automation state:
- Google connection
- Meet status
- automatic email status
- reminder status/time

Do not expose secrets/tokens.

---

# G. Action Log

Extend action labels:
- `meeting_generated` => `إنشاء Google Meet`
- `meeting_generate_pending` => `Google Meet قيد الإنشاء`
- `meeting_auto_sent` => `إرسال رابط الاجتماع تلقائيًا`
- `meeting_reminder_sent` => `إرسال تذكير الجلسة`

Avoid repeated failure/pending log spam.

---

# H. Safety / no-regression rules

Do NOT change:
- payment calculations
- Paymob HMAC/payment flow
- bank transfer flow
- booking slot/capacity logic
- customer cancellation/refunds
- questionnaire behavior
- digital products
- customer accounts
- Developer Hub
- frontend templates unrelated to booking email
- existing SMTP logic
- existing manual meeting Save / Save & Send behavior

No new DB tables.
No external Composer/npm package.
No raw Google errors containing secrets/tokens in UI/log.
No real OAuth connection, Calendar event, or email during validation.
Do NOT push.

---

# Validation

Validate without real external actions:
1. only allowed files changed
2. site/admin no fatal
3. Google settings page loads
4. secrets/tokens are never rendered
5. OAuth state/capability/nonces present
6. offline OAuth + calendar.events scope
7. access token refresh implemented
8. Meet generator uses conferenceDataVersion=1 + hangoutsMeet
9. sendUpdates=none
10. timezone-aware start/end
11. product orders rejected
12. duplicate event protection works structurally
13. existing event is refreshed instead of recreated
14. manual meeting fallback preserved
15. Generate button itself does not email
16. confirmed/paid transition queues automation, not blocking payment request
17. existing details email function reused for automatic meeting-link send
18. auto email marked only after successful send
19. reminder is one-time/idempotent and rechecks booking status
20. cancelled/refunded/past sessions do not get automation/reminders
21. hourly worker bounded
22. SMTP missing does not break booking/Meet generation
23. no real external request that creates OAuth/event/email during validation
24. PHP syntax where available
25. git diff --check with exact output reported if non-zero

Return exact validation output for any failed command; do not describe a non-zero diff-check as success without showing why.
