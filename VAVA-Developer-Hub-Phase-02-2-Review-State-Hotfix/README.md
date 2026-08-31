# VAVA Developer Hub Phase 02.2 — Review State Hotfix

Incremental hotfix over Developer Hub 1.1.1.

## Bug

The red `Invalid file path` review error can remain visible after the user opens the GitHub File Browser. It is stale feedback from a previous Review attempt and makes the browser look broken even when repository browsing works.

## Fix

Version `1.1.2` changes only the File Browser JavaScript plus the plugin bootstrap version for cache busting.

- Opening **Browse files** clears stale Review/Push feedback.
- Selecting/loading a file clears stale feedback before loading the selected file.
- Changing repository or branch clears stale feedback.
- Clicking **Review & Security Check** with an empty path is intercepted before the backend request and shows `Select a file first using Browse files, or enter a valid file path.` instead of `Invalid file path`.
- File rows now say `Select file` to make the click action clearer.
- Controlled Push security/backend rules are unchanged.

## Only two live files change

1. `wp-content/plugins/vava-developer-hub/assets/file-browser.js`
2. `wp-content/plugins/vava-developer-hub/vava-developer-hub.php`

Deploy `file-browser.js` first and bootstrap last. The bootstrap version must become `1.1.2`, which also cache-busts the updated JavaScript asset.

Do not modify WordPress Core, the Vava theme, GitHub token configuration, or any unrelated plugin.