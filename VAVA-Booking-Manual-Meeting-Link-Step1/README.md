# VAVA Booking — Manual Meeting Link Step 1

## Scope
Implement ONLY Step 1 of the meeting workflow:

- Manual Google Meet / Zoom / custom meeting URL on each session booking.
- Manage it from the existing VAVA booking details dashboard.
- Show the meeting information in the existing booking email.
- Provide **Save** and **Save & Send to Customer**.
- Reuse the existing booking email system.
- Preserve the current booking/payment/questionnaire flow.

DO NOT implement Google/Zoom API integration.
DO NOT implement reminders/cron in this step.

## Active booking file
Modify ONLY:

`wp-content/themes/vava-living-theme-ar-v1/inc/booking-vava-safe-v4r10.php`

This is the active file loaded by functions.php. Do not edit legacy `booking-vava.php`.

Use the CURRENT live file via FTP as source of truth.

---

## Data model

Store meeting data on the existing `vava_booking` post using these meta keys:

- `_vava_booking_meeting_provider`
  - allowed: `google_meet`, `zoom`, `manual`
- `_vava_booking_meeting_url`
- `_vava_booking_meeting_status`
  - `pending` or `assigned`
- `_vava_booking_meeting_created_at`
- `_vava_booking_meeting_updated_at`
- `_vava_booking_meeting_updated_by`
- `_vava_booking_meeting_email_sent_at`
- `_vava_booking_meeting_email_sent_by`

Do not add DB tables.

---

## 1. Helpers

Add small reusable helpers near the existing booking admin/email helpers:

### Provider label
Create a helper that maps:
- google_meet => Google Meet
- zoom => Zoom
- manual => رابط اجتماع يدوي / Meeting link

### Meeting data
Create a helper returning normalized:
- provider
- provider_label
- url
- status

Sanitize provider and URL on output.

### Meeting URL validation
Accept HTTPS URLs only.

Provider-specific validation:
- google_meet: host must be `meet.google.com`
- zoom: host may be `zoom.us` or any subdomain ending in `.zoom.us`
- manual: any valid HTTPS URL

Reject javascript/data/file schemes and malformed URLs.

---

## 2. Admin meeting card

Create reusable renderer:
`vava_booking_render_admin_meeting_card( int $booking_id )`

Show it ONLY for real session bookings, never digital/tangible product orders.

Place it in:
- `vava_booking_render_admin_fullpage_content()`
- and the existing admin details/drawer renderer if that view is still used

Place it near the existing "الخدمة والموعد" section.

Card title:
`بيانات الاجتماع`

Fields:
1. Provider select:
   - Google Meet
   - Zoom
   - رابط يدوي
2. Meeting URL input, LTR
3. Status:
   - في انتظار الرابط
   - تم إضافة رابط الاجتماع
4. If URL exists:
   - clickable "فتح رابط الاجتماع" target=_blank rel=noopener
5. Buttons:
   - `حفظ فقط`
   - `حفظ وإرسال للعميل`

Use a normal POST form to `admin-post.php` to avoid adding new JS.

Hidden:
- action = `vava_booking_save_meeting`
- booking ID
- nonce tied to booking ID

If booking status/payment is not ready for customer notification:
- saving is allowed
- Save & Send must be rejected with a clear admin message
- confirmed bookings are allowed
- paid bookings are allowed
- preserve existing cash/pay-later confirmed flow

Do not expose this card for product orders.

---

## 3. Save handler

Add:
`vava_booking_save_meeting_admin_post()`

Register:
`admin_post_vava_booking_save_meeting`

Requirements:
- verify nonce
- verify `current_user_can( vava_booking_admin_capability() )`
- verify booking exists and is `vava_booking`
- reject digital/tangible product orders
- sanitize provider
- sanitize + validate URL
- allow Save with empty URL => status `pending`
- Save & Send requires a valid non-empty URL
- update the meeting meta keys
- set created_at only the first time a valid URL is assigned
- always update updated_at / updated_by

On save, append to existing action log:
- `meeting_saved`

On successful Save & Send:
- call existing `vava_booking_send_details_email( $booking_id, true, false )`
- update meeting email sent timestamp/user
- append action:
  `meeting_sent`

If email fails:
- keep saved meeting data
- show clear error that meeting was saved but email failed

Redirect back to:
`vava_booking_admin_details_url( $booking_id )`

Use query flags for:
- meeting_saved
- meeting_sent
- meeting_error

Render a standard WP admin success/error notice on the details page.

---

## 4. Action history labels

Extend `vava_booking_action_label()`:

- meeting_saved => `حفظ رابط الاجتماع`
- meeting_sent => `إرسال رابط الاجتماع للعميل`

Do not change existing labels.

---

## 5. Booking list visibility

Inside the existing appointment cell for normal session bookings, do NOT create a new column.

After date/time, show a small status line:
- if URL exists: `Google Meet ✓`, `Zoom ✓`, or `Meeting ✓`
- if no URL: `Meeting pending`

Do not change product-order rows.

---

## 6. Booking email

Extend ONLY the existing:
`vava_booking_email_html()`

Read the saved meeting data.

If there is NO meeting URL:
- do not render an empty meeting block
- preserve current email exactly otherwise

If a meeting URL exists, add a clearly separated responsive email-safe section after the booking summary and before/near booking details:

Arabic:
- title: `موعد جلستك أونلاين`
- provider label
- session date
- session time
- CTA: `الانضمام إلى الجلسة`

English:
- title: `Your online session`
- provider label
- session date
- session time
- CTA: `Join meeting`

CTA must use the saved HTTPS URL and proper escaping.

The existing email already includes service/date/time/customer/payment info. Do not duplicate or remove current sections.

---

## 7. Questionnaire handling in email

The current VAVA booking system already renders the beginning/midpoint questionnaire inside the booking flow and saves it in:
`_vava_booking_questionnaire`.

Do NOT invent a new public questionnaire route in this step.

If `_vava_booking_questionnaire` exists:
- add a small line in the meeting section:
  Arabic: `تم استلام استبيان ما قبل الجلسة.`
  English: `Your pre-session questionnaire has been received.`
- optionally link the existing customer account/booking details URL using the already available `$account_url`

If no questionnaire data exists:
- do not show a fake questionnaire link.

This preserves the current questionnaire workflow.

---

## 8. Initial confirmation behavior

Do NOT automatically create meeting links.

Do NOT change booking submission, Paymob webhook, bank approval, slot logic, or questionnaire submission.

Initial booking confirmation email may continue without a meeting block if no meeting URL has been assigned yet.

Later, the admin uses:
`Save & Send to Customer`
which force-resends the existing details email with the meeting section included.

---

## 9. Security / compatibility

Must preserve:
- booking creation
- slot availability
- Paymob
- bank transfer
- status transitions
- refunds
- questionnaires
- customer account
- existing resend-details action
- admin permissions/nonces
- product orders

Use:
- `esc_url_raw` for storage
- `esc_url` for output
- `sanitize_key`
- nonce verification
- existing booking admin capability

No secrets or API credentials.

---

## Validation

Do not perform real customer email sends during validation.

Validate:
1. only active booking file changed
2. PHP syntax valid
3. existing booking page/admin page load without fatal
4. existing booking creation code untouched
5. existing Paymob/bank/questionnaire logic untouched
6. manual meeting card renders only on session bookings
7. invalid/non-HTTPS URL rejected
8. provider-host validation works
9. Save persists meta
10. Save & Send path calls existing force email function
11. email HTML includes meeting block only when URL exists
12. action log labels present
13. no reminder/API integration added
14. git diff --check

Do NOT push.
