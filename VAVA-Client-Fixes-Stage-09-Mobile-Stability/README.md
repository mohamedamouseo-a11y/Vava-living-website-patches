# VAVA Living — Stage 09

## Mobile Page/Section Stability

Client request: on mobile, some sections/pages in the main interface move while others remain fixed, which makes descriptions difficult to read. The requested outcome is stable, readable mobile content.

Because the term "move" is ambiguous, this stage is diagnostic-first. OpenHands must reproduce the issue on the LIVE production site, distinguish horizontal overflow from animations/transforms/layout shifts/sliders, prove the exact root cause, back up the exact live file(s), apply only the smallest mobile-scoped fix, and verify Arabic, English, and desktop behavior.

No speculative site-wide `overflow-x:hidden`, global animation disabling, redesign, content changes, or unrelated functionality changes are allowed.

Execution instructions are in `OpenHands-Prompt.txt`.
