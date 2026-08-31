# VAVA Developer Hub — Phase 02 File Browser

Incremental upgrade for the already-installed **Vava Developer Hub** plugin.

## Goal

Replace manual GitHub path typing in **Controlled Push** with a safe file browser.

After this phase, the admin can:

- Click **Browse files** next to File path.
- Browse the selected GitHub repository and branch.
- Open folders with an Up/Refresh navigation flow.
- Select an editable file.
- Automatically load its exact GitHub path into **File path**.
- Automatically load the current remote file contents into **File content**.
- Continue through the existing Review & Security Check -> Authorize Controlled Push flow.
- Still type a path manually when intentionally creating a new file.

## Security scope

For `Vava-living-website`, browsing is intentionally limited to:

- `wp-content/themes/vava-living-theme-ar-v1`
- `wp-content/plugins/vava-developer-hub`

The existing protected-path, extension, size, nonce, administrator and controlled-push rules remain in force.

For `Vava-living-website-patches`, the repository can be browsed from its root, but files still pass the existing controlled-push file validation before they can be selected.

## Files in this phase

Only four production files are supplied:

- `wp-content/plugins/vava-developer-hub/vava-developer-hub.php`
- `wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-file-browser.php`
- `wp-content/plugins/vava-developer-hub/assets/file-browser.js`
- `wp-content/plugins/vava-developer-hub/assets/file-browser.css`

The plugin version is bumped from `1.0.0` to `1.1.0` so WordPress/browser asset caches use the new JS/CSS.

## FTP deployment

This phase is designed to work with FTP-only access.

Safe upload order:

1. Backup/download the current `vava-developer-hub.php` before replacing it.
2. Upload `includes/class-vava-devhub-file-browser.php`.
3. Upload `assets/file-browser.js`.
4. Upload `assets/file-browser.css`.
5. Upload/replace `vava-developer-hub.php` **LAST**.

Uploading the bootstrap last prevents a temporary fatal error where WordPress could try to require the new PHP class before it exists.

The plugin is already installed and can remain active during this incremental update. No GitHub token is added or changed by this phase.

## Acceptance checks

Open WordPress Admin -> Developer Hub -> Controlled Push.

PASS requires:

1. A **Browse files** button appears next to the File path field.
2. Clicking it opens **GitHub File Browser**.
3. With `Vava-living-website` selected, the initial browser shows the Vava Theme and Developer Hub Plugin safe roots.
4. Opening folders works.
5. Selecting an editable file closes the browser and fills both File path and File content automatically.
6. Review & Security Check works with the selected file.
7. No PHP fatal error occurs and the rest of Developer Hub still loads.

Actual GitHub write/Authorize Controlled Push still requires a server-side `VAVA_GITHUB_TOKEN` with repository write permission.
