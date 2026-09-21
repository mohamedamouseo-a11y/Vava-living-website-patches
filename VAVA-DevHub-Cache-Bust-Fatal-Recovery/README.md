# VAVA Developer Hub — Cache Bust Fatal Recovery

## Situation
The Developer Hub admin page became a WordPress critical-error page immediately after changing the `server-git.js` enqueue version to a `filemtime(VAVA_DEVHUB_PATH ...)` expression.

## Goal
Restore the Developer Hub page and still force a fresh JS URL without relying on `VAVA_DEVHUB_PATH`.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php`

Use the CURRENT live file via FTP as source of truth.

## Exact edit only
Inside `enqueue_assets()`, find ONLY the existing `wp_enqueue_script('vava-devhub-server-git', ...)` version argument.

If it currently contains:
`(file_exists(VAVA_DEVHUB_PATH . 'assets/server-git.js') ? (string) filemtime(VAVA_DEVHUB_PATH . 'assets/server-git.js') : VAVA_DEVHUB_VERSION)`

replace ONLY that version argument with:
`VAVA_DEVHUB_VERSION . '-pushpending-20260921-1'`

Do not alter any other argument or code.

## Critical safety
- The PHP file is minified.
- Exact substring replacement only.
- No reformatting.
- No brace/method edits.
- boot() must remain byte-identical.
- Do NOT touch ajax_push_pending(), JS, CSS, runtime, auth, remotes, config, theme, or frontend.
- Do NOT run git fetch/rebase/push/pull/commit.
- Do NOT push to GitHub.
- If the exact current substring is not present, STOP and report it.

## Validation
1. Confirm exact substring was replaced once.
2. Confirm Developer Hub admin URL no longer returns the WordPress critical-error page.
3. Confirm generated/enqueued script version is based on `VAVA_DEVHUB_VERSION . '-pushpending-20260921-1'`.
4. Confirm boot() byte-identical and brace counts unchanged.
5. JS/CSS files untouched.
