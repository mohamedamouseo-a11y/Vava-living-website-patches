# VAVA Client Fixes — Stage 01: Mid-Journey Survey

## Scope
This stage addresses one confirmed outstanding client request only:

- Remove the Mid-Journey Questionnaire question: **"كيف كان نشاطك اليومي؟" / "How has your daily activity been?"**

## Why this is still required
The question is still present in the current `main` source at:

`wp-content/themes/vava-living-theme-ar-v1/inc/booking-questionnaires-vava.php`

under `midpoint > next_session > fields` with the field id `daily_activity`.

## Implementation
The patch does two narrowly-scoped things:

1. Removes `daily_activity` from the default Mid-Journey Questionnaire definition.
2. Filters `daily_activity` out of merged Mid-Journey settings so older saved `_vava_booking_questionnaires` metadata cannot reintroduce the retired question.

Historical questionnaire snapshots/answers are intentionally left untouched, so previously submitted information remains readable.

## Explicitly out of scope
- Journey Start Questionnaire.
- Journey Impact Questionnaire.
- Any other Mid-Journey question.
- Login behavior (already fixed).
- Site tagline/footer wording (already fixed).
- Digital product full description (already present in current code).
- Any other item from the August client notes.

## Files
- `patches/0001-remove-midpoint-daily-activity.patch` — patch against `mohamedamouseo-a11y/Vava-living-website` `main`.
- `OpenHands-Prompt.txt` — constrained implementation/verification prompt.

## Acceptance checks
- Mid-Journey form no longer renders the Arabic question `كيف كان نشاطك اليومي؟`.
- English Mid-Journey form no longer renders `How has your daily activity been?`.
- Saving/reloading questionnaire settings does not restore the field.
- All other Mid-Journey questions remain unchanged.
- Existing historical submissions are not deleted or rewritten.
- No changes are pushed or merged to the source repository `main` branch by this stage.
