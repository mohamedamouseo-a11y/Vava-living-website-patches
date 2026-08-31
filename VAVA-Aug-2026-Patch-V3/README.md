# VAVA Living — August 2026 Corrective Patch V3

This V3 exists because live verification after V2 still showed four failures plus a Journey Impact mismatch. Apply it **on top of V1 + V2**. Do not remove previous patches.

## Safety
- Production repo: `mohamedamouseo-a11y/Vava-living-website`
- Patch repo: `mohamedamouseo-a11y/Vava-living-website-patches`
- Production `main` must never be modified/pushed/merged.
- Server changes are applied by OpenHands.

## Files
- `wp-content/mu-plugins/vava-aug-2026-v3-fixes.php`
- `wp-content/mu-plugins/vava-aug-2026-v3-admin.js`
- `patches/journey-impact-questionnaire-v3.patch`
- `patches/session-audience-layout-v3.patch`
- `OpenHands-Prompt.txt`

## Why V2 did not solve these live items
1. **Login:** V2 added no-cache headers, but the theme still enqueued `login-vava.js`, rendered the press-and-hold guard and rejected direct login posts without a guard token. V3 removes only that custom gate; native WordPress username/password authentication stays intact.
2. **Full description compatibility JS:** V2 used `plugins_url()` from an MU plugin. V3 loads admin compatibility JS through `WPMU_PLUGIN_URL` / `/wp-content/mu-plugins/` explicitly.
3. **Empty session card:** V2 tested the whole card text. An empty audience card still has a heading/icon, so it was incorrectly considered non-empty. V3 checks actual `<li>` content and the source patch prevents empty audience rendering.
4. **Paths image:** V2 mainly handled cache. V3 persists both known image meta fields late on normal save and through a scoped AJAX fallback, then clears common caches.
5. **Journey Impact:** the client supplied the exact form screenshot after V2, so this is no longer blocked.

## Exact Journey Impact content
Use exactly these six visible questions, in this order:

1. `الاسم الكريم (ثنائي)` — text.
2. `وش أكثر شيء علقت فيه من هالرحلة؟` — free text.
3. `وش أكثر جزء من التجربة حسيت إنه أفادك؟` — radio:
   - `الجلسة الاستشارية`
   - `الخطة الشخصية`
   - `الملفات التعليمية`
   - `جلسة المتابعة (إن وجدت)`
   - `دعم الواتساب (إن وجدت)`
   - `أخرى`
4. `هل كان فيه شيء تمنيت يكون أوضح أو مختلف؟` — free text.
5. `هل ترشح الخدمات الاستشارية المتنوعة المقدمة من فافا لشخص آخر؟` — radio:
   - `أكيد`
   - `ممكن`
   - `لا`
   - `أخرى`
6. `هل تسمح لي أشارك جزء من كلامك عن تجربتك (بدون اسم أو أي معلومات شخصية)؟` — radio:
   - `نعم`
   - `لا`

The screenshot did not reliably expose required/optional markers, so V3 does not invent them; new fields default to non-required until an authoritative source says otherwise.

### Important migration note
`vava_booking_questionnaire_settings()` uses `array_replace_recursive(defaults, stored)`. Merely replacing stored `impact` meta does not remove old default groups. OpenHands must apply `patches/journey-impact-questionnaire-v3.patch` (or the equivalent minimal source edit) so theme defaults are replaced too. The MU plugin then replaces the stored Impact override once. Historical completed response snapshots are not deleted.

## Full digital product description
Current GitHub source already contains `full_description` support in `inc/selections-vava.php` and `inc/digital-products-vava.php`. If the live server is older, port only the current-main `full_description` hunks; do not replace whole files. V3 also injects exactly one visible compatibility field for digital products and preserves its posted value after older sanitizers.

## Validation
```bash
php -l wp-content/mu-plugins/vava-aug-2026-v3-fixes.php
node --check wp-content/mu-plugins/vava-aug-2026-v3-admin.js
php -l wp-content/themes/vava-living-theme-ar-v1/inc/booking-questionnaires-vava.php
php -l wp-content/themes/vava-living-theme-ar-v1/single-vava_session.php
git diff --check
```

Live-test these five failed items first:
1. Incognito login works first attempt, no refresh/hold gate.
2. Change Homepage `مسارات VAVA` image, Update, reload dashboard + homepage; also verify Paths hero image separately if used.
3. Digital-product editor shows exactly one `الوصف الكامل للمنتج`; save/reload preserves it and frontend uses it.
4. Session details with no audience items have no empty card/column; remaining cards fill the container evenly.
5. Journey Impact contains exactly the six supplied questions and no old Impact groups/questions.
