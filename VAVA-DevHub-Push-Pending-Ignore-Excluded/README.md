# VAVA Developer Hub — Push Pending Ignore Excluded Changes

Work only on the CURRENT live Developer Hub plugin via FTP.

## Goal
Fix the current Push Pending Commits failure caused by excluded server files being counted as blocking working-tree changes.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use the CURRENT live file as source of truth.

## Exact change
Inside ONLY `ajax_push_pending()`:

1. Replace the initial dirty-tree guard so it blocks only when there are safe/project changes:
- use `$snapshot['safe_count'] > 0`
- do NOT block because of excluded changes/backups/logs/cache/uploads/etc.

2. Replace the final post-reconcile dirty-tree guard the same way:
- require `safe_count === 0`
- excluded changes must not block push.

3. Preserve all existing:
- auth/lock
- fetch
- rebase
- conflict detection + rebase --abort
- ahead/behind guards
- push
- audit/error handling

Do not change any other method.
Do not touch boot(), JS, CSS, runtime, config, remotes, auth, theme, or frontend.
Do not execute any git network/mutation command during validation.
Do not push.

## Validation
- Confirm only `ajax_push_pending()` changed.
- Confirm both dirty-tree checks now use `safe_count`, not total `changes`.
- Confirm excluded files remain excluded and untouched.
- Confirm Developer Hub admin page loads without fatal.
- Run `git diff --check`.
