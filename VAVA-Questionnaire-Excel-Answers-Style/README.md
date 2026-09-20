# VAVA — Questionnaire Excel Answers + Theme Style

## Scope
Fix only the existing 3 questionnaire Excel buttons and the existing questionnaire export backend on the CURRENT live `/public_html`.

The previous live work already provides:
- 3 per-type Excel buttons in `inc/booking-questionnaires-vava.php`
- export backend in `inc/questionnaire-admin-dashboard-v1.php`
- per-type filter: `beginning`, `midpoint`, `impact`

Do not create a new submenu/page.

## 1) Button style
Replace the temporary WordPress-blue appearance with the VAVA admin visual identity.

Keep every button always visible and preserve its current secure URL/nonce/type.

Use a VAVA olive treatment matching the existing dashboard:
- background/border: `#747a4e`
- text: white
- hover/focus: darker olive around `#5f653f`
- border-radius: about 10px
- padding: about 8px 14px
- font-size: 13px
- font-weight: 700
- no blue WordPress primary-button styling

Label stays:
`تنزيل Excel`

Keep the button compact and aligned cleanly inside each questionnaire card.

## 2) Export ACTUAL submitted answers
Current symptom: exported files contain questionnaire questions/columns but user answers are blank/missing.

Fix the existing export code. Do NOT export the questionnaire schema alone.

VAVA submissions already store:
- normal booking questionnaire: `_vava_booking_questionnaire`
- impact questionnaire: `_vava_booking_impact_questionnaire`

Each saved submission contains:
- `type`
- `language`
- `completed_at`
- `answers`
- `snapshot`

For the requested export `type`:
1. Include only real submitted responses of that type.
2. For every response, read actual values from:
   `$data['answers'][$field_id]`
3. Build question columns from the saved `snapshot` when available.
4. Match each question to its answer by the field `id`.
5. For radio/checkbox values, convert stored internal values to human-readable option labels from the saved snapshot, using the submission language and Arabic/English fallback.
6. For checkbox arrays, join selected labels into one readable cell.
7. Text/textarea/number values must export exactly the sanitized value the user submitted.
8. Preserve Arabic correctly.
9. Continue spreadsheet formula-injection protection.
10. Do not output a fake response row merely from the questionnaire definition when no user submitted it.

Keep booking metadata already exported, including booking ID, customer/session/date/status/language where available.

## Required verification
Before finishing, verify at least one actual stored submission:
- inspect its saved `answers` array,
- identify one known field/value,
- generate/export the same questionnaire type,
- confirm that value appears in the corresponding spreadsheet row/cell.

Also verify all three buttons still use their correct type:
- beginning
- midpoint
- impact

Run:
- PHP lint if PHP CLI is available
- `git diff --check`

Do not modify unrelated systems.
Do not push, merge, create PRs, or modify remote `main`.
Leave changes only in `/public_html`.
