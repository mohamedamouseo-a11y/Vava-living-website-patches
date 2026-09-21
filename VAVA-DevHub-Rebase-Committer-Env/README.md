# VAVA Developer Hub — Rebase Committer Environment

## Verified current failure
Latest Push Pending reaches:

`git rebase --autostash origin/main`

but fails with Git committer identity error:

- `Committer identity unknown`
- `Please tell me who you are`
- Git requests user.name / user.email

Do NOT configure git globally or locally.

## Existing safe runtime capability
`Vava_DevHub_Server_Git_Runtime::run_git()` already supports:

`array('author' => true)`

and its environment() method sets temporary process-only values:

- GIT_AUTHOR_NAME
- GIT_AUTHOR_EMAIL
- GIT_COMMITTER_NAME
- GIT_COMMITTER_EMAIL

This is already used safely by Commit & Push.

## Goal
Use that existing process-only author/committer environment for the rebase operation.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use CURRENT live file via FTP as source of truth.

## Exact edit
Inside ONLY `ajax_push_pending()`, find the CURRENT rebase call:

`Vava_DevHub_Server_Git_Runtime::run_git(array('rebase', '--autostash', 'origin/' . $snapshot['branch']), array('timeout' => 180))`

Change ONLY its options array to:

`array('author' => true, 'timeout' => 180)`

Result:

`Vava_DevHub_Server_Git_Runtime::run_git(array('rebase', '--autostash', 'origin/' . $snapshot['branch']), array('author' => true, 'timeout' => 180))`

## Preserve exactly
- --autostash
- safe_count guards
- fetch
- conflict detection
- rebase --abort
- detailed diagnostics
- final push guards
- cache-bust suffix
- boot()
- enqueue_assets()
- JS/CSS/runtime/config/auth/remotes/theme/frontend

## Safety
- Exact substring replacement only.
- Minified PHP: no reformatting.
- Do NOT change git config.
- Do NOT add user.name/user.email config.
- Do NOT run any git operation during validation.
- Do NOT push.
- Stop if exact current rebase call is not found exactly once.

## Validation
- Confirm only rebase run_git options changed.
- Confirm `author => true` is present on the rebase call.
- Confirm --autostash remains.
- Confirm rebase --abort and diagnostics remain.
- Confirm admin page loads without fatal.
- Confirm boot() and enqueue_assets() byte-identical.
