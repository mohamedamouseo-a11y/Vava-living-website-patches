# VAVA Developer Hub — Preserve Rebase Failure Details

## Verified current state
Latest Push Pending reached reconciliation and reported:
- branch: main
- ahead: 5
- behind: 4
- safe_count: 0
- rebase failed and was aborted safely

The current `ajax_push_pending()` discards the underlying `$rebase->get_error_message()` and throws only:
`Rebase failed; aborted safely.`
This hides the exact reason/conflict details.

## Goal
Preserve the real sanitized rebase failure text in the existing audit/error output, without changing Git behavior.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use CURRENT live file via FTP as source of truth.

## Exact change
Inside ONLY the existing rebase-failure branch in `ajax_push_pending()`:

Current behavior ends with a generic exception similar to:
`throw new Exception('Rebase failed; aborted safely.' . $detail);`

Change ONLY that exception construction so it also includes the sanitized original rebase WP_Error message:
`Vava_DevHub_Server_Git_Runtime::redact($rebase->get_error_message())`

Expected form:
`throw new Exception('Rebase failed; aborted safely. ' . Vava_DevHub_Server_Git_Runtime::redact($rebase->get_error_message()) . $detail);`

Preserve:
- conflict-file collection
- `git rebase --abort`
- safe_count guards
- fetch/rebase/push behavior
- diagnostic catch block
- cache-bust suffix
- all other methods

## Critical safety
- minified PHP: exact substring replacement only
- no reformatting
- no brace/method edits
- boot() and enqueue_assets() byte-identical
- JS/CSS/runtime untouched
- do NOT execute fetch/rebase/push/pull/commit during validation
- do NOT push
- stop if exact target cannot be matched safely

## Validation
Confirm:
- only the rebase failure exception text changed
- underlying rebase error is now included through redact()
- rebase --abort still occurs before throwing
- admin page loads without fatal
- brace balance unchanged
