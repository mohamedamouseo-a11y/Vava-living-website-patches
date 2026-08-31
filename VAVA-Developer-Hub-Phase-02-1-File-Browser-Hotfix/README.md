# VAVA Developer Hub Phase 02.1 — File Browser Hotfix

Incremental hotfix for the already-installed **Vava Developer Hub 1.1.0**.

## Problem fixed

Phase 02 exposed two virtual roots while the selected repository was `Vava-living-website`:

- Vava Theme
- Developer Hub Plugin

The Vava Theme exists on the website repository `main` branch, but the Developer Hub source is maintained in the dedicated patches repository. Selecting the virtual Developer Hub folder therefore requested a non-existent path from the website repository and GitHub returned `Not Found`.

## Fix

Version **1.1.1** changes the website repository File Browser root so it exposes **Vava Theme only**.

To browse Developer Hub patch/source files, select repository:

`Vava-living-website-patches`

The patches repository continues to browse its real GitHub tree normally.

## Files changed

Only two plugin files are replaced:

- `wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-file-browser.php`
- `wp-content/plugins/vava-developer-hub/vava-developer-hub.php`

No JavaScript, CSS, theme, WordPress Core, token, or GitHub production repository changes are required.

## FTP-safe deployment order

Because the plugin is already active:

1. Back up both current live files.
2. Replace `includes/class-vava-devhub-file-browser.php` first.
3. Replace `vava-developer-hub.php` last.
4. Hard-refresh the Developer Hub page.

## Acceptance checks

- Plugin version reports `1.1.1`.
- Select `Vava-living-website` and open **Browse files**.
- Root shows **Vava Theme** only.
- No `Developer Hub Plugin` virtual root is shown under the website repository.
- Navigate into Vava Theme and select an editable file such as `footer-home.php`.
- File path and file content load automatically.
- Select `Vava-living-website-patches` and open Browse files.
- The real patches repository folders load normally.
- Public site and wp-admin continue loading.

Do not authorize a Controlled Push as part of this hotfix verification.