# VAVA Living — Stage 07

## Goal
Remove only the final Arabic question beginning with:

`هل أبدأ بفهم جسدي أولًا؟`

from the client-reported `مجرد فكرة / استشارة سريعة` area, without changing the rest of the content or any booking/pricing behavior.

## Current source review
- The exact target question was not found in the current source repository default branch.
- `مجرد فكرة` was not found in the current source repository default branch.
- `استشارة سريعة` does exist in the Paths default data as the Arabic package/session with `uid: session-8`.

Because the exact target source is not proven from source control, this stage is diagnostic-first on the live WordPress installation.

## Deployment
Use `OpenHands-Prompt.txt` with the FTP-connected production environment.

No source patch is included until the exact live target is proven.

## Safety
- Backup the exact target before editing.
- No global replacements.
- Do not change pricing, booking, buttons, titles, English wording, layout, or unrelated questions.
- Do not report success without live frontend verification.
