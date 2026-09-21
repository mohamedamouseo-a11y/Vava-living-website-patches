# VAVA Developer Hub — Restore Safe Push Pending on Current Live

## Current verified state
The CURRENT live Developer Hub has:
- diagnostic audit string PRESENT
- renderAudit details PRESENT
- JS cache-bust suffix `pushpending-20260921-1` PRESENT

But CURRENT live `ajax_push_pending()` is missing:
- safe_count guards
- fetch/rebase reconciliation

The live server and GitHub are not synchronized.

## Goal
Restore ONLY the safe rebase-aware `ajax_push_pending()` behavior on the CURRENT live PHP file while preserving all other current live changes exactly.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use CURRENT live file via FTP as source of truth.

## Replace ONLY
The exact `public static function ajax_push_pending() { ... }` method span.

Do NOT modify:
- boot()
- enqueue_assets() / cache-bust suffix
- ajax_commit_push()
- ajax_pull()
- diagnostic catch content, except integrate it into the restored method
- JS/CSS
- runtime/config/remotes/auth
- theme/frontend

## Required ajax_push_pending behavior

1. authorize admin request
2. acquire lock
3. status snapshot + ensure_operational
4. if `(int) $snapshot['safe_count'] > 0`:
   stop with:
   `Working tree has uncommitted safe changes. Use Review Changes → Commit & Push first.`
5. require `ahead >= 1`
6. run:
   `git fetch origin <branch>`
   with network true and timeout 180
7. refresh status snapshot
8. if `behind > 0`:
   run:
   `git rebase origin/<branch>`
9. if rebase fails:
   - collect conflicted files with:
     `git diff --name-only --diff-filter=U`
   - run:
     `git rebase --abort`
   - return a sanitized failure containing conflict filenames when available
   - do not auto-resolve
   - do not push
10. refresh snapshot after successful rebase
11. before push require:
    - behind === 0
    - ahead > 0
    - safe_count === 0
12. run:
    `git push origin <branch>`
13. audit success + return refreshed status

## Diagnostics
Preserve the CURRENT live diagnostic catch behavior:
- step: push_pending
- branch
- ahead
- behind
- safe_count
- exit_code if derivable
- stderr/error message
- all content passed through existing redaction

## Safety prohibitions
Never use:
- force push
- reset --hard
- clean
- checkout/restore
- merge
- pull
- git config changes
- remote changes

Because the PHP file is minified:
- exact method-span replacement only
- no reformatting
- no edits outside ajax_push_pending()
- boot() must remain byte-identical
- enqueue_assets() must remain byte-identical
- if safe method boundary cannot be identified exactly, STOP

## Validation only
Do NOT execute fetch/rebase/push/pull/commit during validation.
Validate:
- only ajax_push_pending() changed
- safe_count guards present twice
- fetch + conditional rebase present
- rebase --abort conflict path present
- diagnostic catch still present
- boot() unchanged
- enqueue_assets() unchanged, including cache-bust suffix
- JS untouched
- admin page loads without fatal
- brace balance valid
