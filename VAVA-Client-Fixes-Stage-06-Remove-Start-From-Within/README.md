# Stage 06 — Remove “نبدأ من الداخل.”

Client request:

Remove only:

`نبدأ من الداخل.`

Keep unchanged:

`لأن التغيير الحقيقي يبدأ من الداخل… ويكبر معك، على مهلك.`

## Current source finding

The exact requested strings were not found in the inspected GitHub `main` source, so this stage must be diagnostic-first on live hosting.

## Deployment policy

- Live hosting / FTP only.
- Prove the exact live source before editing.
- Backup first.
- Make the smallest possible change.
- Verify the rendered live frontend after the change.
- If target cannot be proven, stop with no edits.
- If the unwanted sentence is already absent and the kept sentence is present, report the stage as already resolved with no edits.

Use `OpenHands-Prompt.txt` for execution.