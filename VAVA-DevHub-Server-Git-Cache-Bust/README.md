# VAVA Developer Hub — server-git.js Cache Bust

Work only on the CURRENT live Developer Hub plugin via FTP.

## Goal
The newly added "Prepare GitHub Sync" button exists in `assets/server-git.js` but is not appearing in the browser UI. Fix only the Developer Hub asset-loading cache so the current JS file is loaded.

## Scope
Inspect only the Developer Hub plugin file(s) that enqueue/register:
`wp-content/plugins/vava-developer-hub/assets/server-git.js`

Use deterministic cache busting:
- prefer `filemtime()` of `assets/server-git.js`
- otherwise bump only that script's version safely

Do not alter the new Prepare GitHub Sync logic.
Do not alter any git commands/runtime behavior.
Do not modify theme/frontend code.
Do not push.

## Validation
- confirm page HTML/script URL for `server-git.js` contains the new version
- confirm downloaded JS contains "Prepare GitHub Sync" / its action hook
- run PHP lint if available
- run `git diff --check`
