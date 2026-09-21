# VAVA Developer Hub — Safe JS Cache Bust

## Goal
Force browsers to load the current live `assets/server-git.js` after recent fixes, without changing any Git behavior.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use the CURRENT live file via FTP as source of truth.

## Exact safe edit
Inside `enqueue_assets()`, change ONLY the version argument of the existing `wp_enqueue_script('vava-devhub-server-git', ...)` call.

Current version argument:
`VAVA_DEVHUB_VERSION`

Replace ONLY that script-version argument with:
`(file_exists(VAVA_DEVHUB_PATH . 'assets/server-git.js') ? (string) filemtime(VAVA_DEVHUB_PATH . 'assets/server-git.js') : VAVA_DEVHUB_VERSION)`

Do NOT change the stylesheet enqueue.
Do NOT change boot(), AJAX hooks, ajax_push_pending(), runtime, JS, CSS, config, remotes, auth, theme, or frontend.

## Critical safety
The PHP file is minified and previously broke from structural edits.
- Perform exact substring replacement only.
- Do not reformat.
- Do not add/remove braces.
- Confirm `boot()` is byte-identical before/after.
- If the exact enqueue substring cannot be matched safely, STOP.

## Validation
- PHP/admin page loads without fatal.
- Only the script version argument changed.
- JS file itself untouched.
- No git/network operations.
- No push.
