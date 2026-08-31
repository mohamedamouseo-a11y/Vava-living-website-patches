# VAVA Developer Hub — Phase 03 Server Git Sync

Version: **2.0.0**

Single incremental patch over the currently deployed Developer Hub 1.1.2. It adds a TCRM-style **Server Git Sync** workflow inside WordPress Admin so code changed/built on the live server can be reviewed, committed and pushed back to GitHub.

## Features

- Server Git status: repository root, branch, HEAD, local changes, ahead/behind.
- Guarded **Connect Server Repository** when the FTP-deployed WordPress root has no `.git` metadata. It initializes Git, fetches `main`, and uses `git reset --mixed origin/main` so live files are not overwritten.
- **Review Changes** before any commit, with a 10-minute authorization tied to the exact HEAD, branch, commit message and safe change list.
- **Commit & Push** from the live server to `mohamedamouseo-a11y/Vava-living-website`.
- **Push Pending Commits** if a local commit succeeds but network/auth push fails.
- **Pull from GitHub (FF-only)**. Pull is blocked when local source-code changes exist and never exposes hard reset/force pull.
- Git operation lock and audit history.
- GitHub token can be supplied server-side using `VAVA_GITHUB_TOKEN` or saved from the UI encrypted at rest with AES-256-GCM. The raw token is never returned to JavaScript after save.

## Source-code scope

Server Git Sync stages only source-code paths under:

- `wp-content/themes/`
- `wp-content/plugins/`
- `wp-content/mu-plugins/`

Generated, uploaded, backup and sensitive paths are excluded, including uploads, caches, LiteSpeed data, upgrades, backups, database/archive/key files, configuration files and credential/private-key patterns.

## Requirements

- Existing active Vava Developer Hub **1.1.2** or newer.
- PHP `proc_open` available to the WordPress PHP process.
- `git` executable available to that process.
- GitHub write token for actual push/fetch against private/authenticated operations when required.

## Files in this patch

New:

- `includes/class-vava-devhub-credentials.php`
- `includes/class-vava-devhub-server-git-runtime.php`
- `includes/class-vava-devhub-server-git.php`
- `assets/server-git.js`
- `assets/server-git.css`

Replace:

- `includes/class-vava-devhub-github.php`
- `vava-developer-hub.php`

## FTP-safe deployment order

1. Back up every live file that will be replaced.
2. Upload `class-vava-devhub-credentials.php`.
3. Upload `class-vava-devhub-server-git-runtime.php`.
4. Upload `class-vava-devhub-server-git.php`.
5. Upload `server-git.js`.
6. Upload `server-git.css`.
7. Replace `class-vava-devhub-github.php`.
8. Replace `vava-developer-hub.php` **last**.
9. Confirm the plugin reports version `2.0.0` and hard-refresh Developer Hub.

## Critical safety behavior

- No arbitrary shell-command input exists in the UI.
- Git is executed through fixed argument arrays with `proc_open` and shell bypass.
- Origin must resolve to the approved Vava website GitHub repository.
- Repository root must match the configured WordPress/Git root.
- Review state is fingerprinted and rechecked immediately before commit.
- Staged files are revalidated before commit.
- Pull is `fetch` + `merge --ff-only` only.
- Tokens are redacted from Git errors/audit output.

## Rollback

If the plugin fatals after the final bootstrap replacement, restore the backed-up 1.1.2 `vava-developer-hub.php` first, then restore the previous `class-vava-devhub-github.php`. The five new Phase 03 files can remain unused or be removed after rollback.
