# VAVA Developer Hub — Safe Push Pending Reconcile

Work only on the CURRENT live Developer Hub plugin via FTP.

The Developer Hub was restored to the known-good GitHub main version. Use that CURRENT live file as source of truth.

## Goal
Fix failed "Push Pending Commits" when the live server has local commits but `origin/main` has newer commits.

Do this WITHOUT adding any new button or JavaScript.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Do NOT modify:
- `boot()`
- `enqueue_assets()`
- any JS/CSS
- runtime/config/remotes/auth
- theme/frontend
- Commit & Push behavior
- Pull behavior

## Exact scope
Replace ONLY the body/implementation of:
`public static function ajax_push_pending()`

Preserve the existing authorization and locking model.

## Required behavior for Push Pending Commits
1. Authorize admin request and acquire existing lock.
2. Read current status and ensure repository is operational.
3. If working tree has ANY changes, STOP safely with a clear message telling the admin to use Review Changes -> Commit & Push first. Do not stage or modify anything.
4. Require at least one local commit ahead of origin, otherwise return the existing "no local commits" error.
5. Run:
   `git fetch origin <current-branch>`
6. Refresh `status_snapshot()`.
7. If `behind > 0`, run:
   `git rebase origin/<current-branch>`
8. If rebase fails:
   - read conflicted files with:
     `git diff --name-only --diff-filter=U`
   - immediately run:
     `git rebase --abort`
   - do NOT auto-resolve
   - do NOT push
   - return a sanitized error listing conflicted filenames when available
9. Refresh status again.
10. Push only if:
   - working tree is clean
   - `behind === 0`
   - `ahead > 0`
11. Use existing:
   `git push origin <current-branch>`
12. Refresh status and return success.

## Safety
Never use:
- force push
- reset --hard
- clean
- checkout/restore to discard files
- merge
- pull
- git config changes
- remote changes

Do not change upstream tracking. It is not required for `git push origin main`.

## Important implementation safety
The PHP file is minified and a previous edit caused a fatal by changing class braces.

Therefore:
- start from the CURRENT restored live file
- replace ONLY the exact `ajax_push_pending()` method span
- do not reformat surrounding code
- do not alter class/boot braces
- verify class/method brace balance after edit
- if a safe exact replacement cannot be made, STOP without deploying

## Validation
Before deployment:
- verify only one method changed
- verify `boot()` is byte-identical to current live source
- verify JS untouched
- verify no new AJAX hooks/buttons were added

After deployment:
- confirm Developer Hub admin page loads without fatal
- run `git diff --check`
- do NOT trigger Push Pending during validation
- do NOT execute fetch/rebase/push during validation

No push/commit/rebase is to be executed by OpenHands. This task only changes the existing Hub action behavior.
