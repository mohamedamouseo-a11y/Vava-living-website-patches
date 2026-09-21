# VAVA Developer Hub — Fatal Recovery

Work only on the CURRENT live Developer Hub plugin via FTP.

## Goal
Recover the Developer Hub admin page from the current WordPress fatal error introduced after the recent Prepare GitHub Sync / server-git.js cache-busting edits.

## Scope
Inspect only:
- `wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`
- `wp-content/plugins/vava-developer-hub/assets/server-git.js`
- the plugin file that instantiates/registers this class only if needed to verify loading

Do not inspect or modify theme/frontend code.

## Required behavior
1. Identify the exact fatal cause first (parse error, duplicate method/hook, invalid method placement, bad enqueue/version code, etc.).
2. Make the smallest fix that restores:
   - Developer Hub page loads normally
   - existing Server Git Sync UI
   - existing Prepare GitHub Sync backend action if it can be kept safely
   - server-git.js filemtime cache-busting
3. If the Prepare GitHub Sync addition itself is the fatal source and cannot be safely repaired in one small edit, disable/remove ONLY that new action/button while keeping the original Developer Hub fully working. Do not touch existing Commit Push / Push Pending / Pull behavior.
4. Do not execute any git mutation commands. This task is recovery only.

## Validation
- PHP lint the edited PHP file if PHP CLI is available.
- Confirm the Developer Hub admin page request no longer returns a fatal error.
- Confirm the existing original Server Git Sync UI renders.
- Confirm no push/pull/commit/rebase command is executed during validation.
- Run `git diff --check` on edited plugin files.

Do not push, commit, merge, change git config/remotes, or modify remote main.
