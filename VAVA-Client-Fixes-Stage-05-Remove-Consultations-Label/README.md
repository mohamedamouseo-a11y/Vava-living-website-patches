# Stage 05 — Remove “للاستشارات” and keep “باختصار”

## Scope
Client-requested copy cleanup only: remove the unwanted Arabic label/word `للاستشارات` from the specific UI phrase while preserving `باختصار` and all surrounding approved content.

## Why there is no static patch yet
The exact visible phrase was not found in the inspected source defaults, including Homepage and Paths defaults. The live value may be stored in WordPress post meta/options. A static patch against a guessed theme file would be unsafe.

`OpenHands-Prompt.txt` therefore requires the live deployment agent to prove the exact source first, back it up, then apply the smallest possible change and verify the live frontend.

## Safety
- No global search/replace.
- No database-wide replacement.
- No raw edits to serialized data.
- No unrelated Arabic/English copy changes.
- No booking/payment/digital-product changes.
- Stop if the target source cannot be proven.
