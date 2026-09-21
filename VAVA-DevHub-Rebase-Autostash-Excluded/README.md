# VAVA Developer Hub — Rebase With Autostash for Excluded Tracked Changes

## Verified current failure
Latest Push Pending reaches fetch/rebase with:
- branch: main
- ahead: 6
- behind: 4
- safe_count: 0

Git then refuses to start rebase with:
`cannot rebase: You have unstaged changes. Please commit or stash them.`

These local changes are excluded from normal Developer Hub commits and must NOT be committed or deleted.

## Goal
Allow the existing safe Push Pending reconciliation to rebase while preserving excluded tracked working-tree changes.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use the CURRENT live file via FTP as source of truth.

## Exact edit
Inside ONLY `ajax_push_pending()`, change the existing rebase command from:

`array('rebase', 'origin/' . $snapshot['branch'])`

to:

`array('rebase', '--autostash', 'origin/' . $snapshot['branch'])`

Do not change anything else.

## Why
`--autostash` lets Git temporarily stash tracked unstaged changes before the rebase and reapply them afterward. Untracked backup files remain untouched. Existing rebase failure handling and `rebase --abort` remain in place.

## Preserve exactly
- safe_count guards
- fetch logic
- conflict-file detection
- rebase --abort
- detailed diagnostic/error logging
- final ahead/behind/safe_count guard
- push logic
- enqueue_assets cache-bust suffix
- boot()
- JS/CSS/runtime/config/auth/remotes/theme/frontend

## Safety
- Exact substring replacement only in the minified PHP.
- No reformatting.
- No brace/method edits.
- Do NOT run fetch/rebase/push/pull/commit during validation.
- Do NOT push to GitHub.
- If the exact current rebase command is not present once, STOP.

## Validation
- Confirm only the rebase command gained `--autostash`.
- Confirm `rebase --abort` still exists.
- Confirm diagnostics remain intact.
- Confirm admin page loads without fatal.
- Confirm boot() and enqueue_assets() byte-identical.
