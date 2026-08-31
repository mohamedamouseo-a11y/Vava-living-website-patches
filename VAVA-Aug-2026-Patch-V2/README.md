# VAVA Living — August 2026 Patch V2

Additive V2 patch for the August 2026 client review. This patch is intentionally small and must be applied **on top of the existing V1 August patch**.

## Safety / branch rule

- Production repository: `mohamedamouseo-a11y/Vava-living-website`
- Existing working branch: `patches/vava-aug-2026-requirements`
- **Never commit, push, merge, rebase, or fast-forward production `main`.**
- Patch repository: `mohamedamouseo-a11y/Vava-living-website-patches`
- It is acceptable for this patch folder to live on the patch repository's `main`; that is not the production repository.

## File added by V2

`wp-content/mu-plugins/vava-aug-2026-v2.php`

Keep the V1 files already installed, especially:

- `wp-content/mu-plugins/vava-aug-2026-fixes.php`
- root `.user.ini`

## What V2 fixes

1. **Login** — removes the fragile custom press-and-hold gate while preserving normal WordPress authentication and the existing VAVA login styling. Login responses are forced non-cacheable.
2. **New VAVA logo** — uses the new approved symbol + VAVA Living identity from the client requirements document. The V2 logo is embedded in the MU plugin so there is no extra binary asset to deploy. It replaces legacy `assets/images/vava-logo.png` usages rendered in the DOM, including dynamically inserted admin/login branding.
3. **Tagline** — forces `نحو حياة مزدهرة` and removes lingering `حيث تزدهر الحياة` output.
4. **Retired glyph** — replaces remaining `❧` / equivalent HTML entities with `✦`, including dynamically inserted DOM content.
5. **Paths image** — when the Paths hero image is changed, V2 persists `_vava_paths_hero_image_id`, synchronizes it to the homepage Paths visual `_vava_home_paths_image_id`, clears relevant caches, and adds a file revision query to reduce stale image caching.
6. **Digital product full description** — if the live server is still on the older editor, V2 injects `الوصف الكامل للمنتج` without duplicating the field on a newer theme, saves it after the legacy sanitizer, and uses it in the product reader.
7. **Protected PDF upload** — shows the real upload percentage through 100% for upload stage 1/2, uses a 600-second request timeout, keeps the existing async processing workflow, and adds direct Ghostscript as a third server-side converter fallback after Imagick and `pdftoppm`. Failure messages remain explicit if the host has no supported converter.
8. **Session details layout** — empty list cards are hidden and the remaining cards expand equally to use the full container.
9. **Booking policy** — re-applies the exact Arabic August policy supplied by the client via the V1 policy content function.
10. **Daily consultation capacity** — keeps the V1 caps and backfills missing booking duration metadata so older bookings correctly count toward the requested daily limits: 1 comprehensive × 90 min, 2 follow-up × 30 min, 2 inquiry × 20 min, 1 exploratory × 15 min; overall 205 consultation minutes/day.
11. **Questionnaire export** — V1 already provides UTF-8 CSV export compatible with Excel and V2 keeps it unchanged.

## Intentionally not guessed

### Journey Impact questionnaire
The exact Google Form contents could not be retrieved reliably from the available source. Do **not** invent or approximate its questions. OpenHands should only change this questionnaire if it can open the exact supplied form and reproduce the visible questions/options exactly.

Source form:
`https://docs.google.com/forms/d/e/1FAIpQLSeeZNwb_t3j1W3t5PZbDoRCbKfWHtRAajJWOIkeG2JD5kmjxQ/viewform?usp=sharing&ouid=110601807575632792329`

### Google Meet / Zoom
Do not fabricate meeting URLs. Automatic unique meeting creation requires a selected provider plus authorized OAuth/API credentials. Preserve the current booking flow until those credentials/provider are supplied.

## Host prerequisite for protected PDF conversion
At least one of the following must be available on hosting:

- Imagick with PDF/Ghostscript support
- `pdftoppm`
- Ghostscript binary `gs`

V2 improves the fallback chain but cannot install host packages from WordPress code.

## Validation

Run at minimum:

```bash
php -l wp-content/mu-plugins/vava-aug-2026-v2.php
```

Then verify on staging/live deployment target without touching production `main`:

- native login works without refresh or press-and-hold;
- new logo appears in frontend, login and admin branding;
- tagline is `نحو حياة مزدهرة`;
- no visible `❧` remains;
- changing Paths hero image updates the Paths page and homepage Paths visual;
- digital editor has exactly one full-description field per product and values persist;
- PDF upload phase reaches 100%, then processing status is shown separately;
- session detail cards use the full container with no empty audience gap;
- daily caps are enforced at 205 minutes total and requested category counts;
- questionnaire CSV opens correctly in Excel;
- supplied booking policy is visible.
