# VAVA Developer Hub — Phase 03.1 Backup Exclusion Hotfix

Version: **2.0.1**

Small incremental hotfix over Developer Hub 2.0.0. It closes the Server Git Sync gap that allowed plugin/theme backup directories such as `vava-developer-hub-backup/` to be treated as normal source code.

## What changes

- Any source-code path whose directory name is `backup`, `backups`, or uses a backup suffix such as `*-backup`, `*_backup`, `*.backup`, including dated variants such as `*-backup-20260831`, is excluded from Review and normal staging.
- Legitimate plugin names that merely contain the word backup without a backup suffix, such as `backupbuddy`, are not blocked by this rule.
- Already-tracked backup directories are detected with `git ls-files`.
- On the next approved **Commit & Push**, already-tracked backup directories are removed from the **Git index only** with `git rm -r --cached`. Their live server files remain untouched.
- The review fingerprint includes the tracked-backup cleanup set, so a changed cleanup target invalidates the review authorization.
- Review preview explicitly lists backup directories scheduled to be removed from Git tracking.
- Staged-path verification allows excluded backup paths only when they are staged as deletions; every other excluded staged path still aborts and resets staging.

## Live files changed

Only two plugin files are replaced:

1. `includes/class-vava-devhub-server-git-runtime.php`
2. `vava-developer-hub.php`

No JavaScript, CSS, theme, WordPress Core, token, or credential changes are included.

## Deployment order

1. Back up both live files.
2. Replace `includes/class-vava-devhub-server-git-runtime.php` first.
3. Replace `vava-developer-hub.php` last.
4. Confirm plugin version `2.0.1`.
5. Hard-refresh Developer Hub and open **Server Git Sync**.

## Important cleanup behavior

The repository currently contains `wp-content/plugins/vava-developer-hub-backup/` from the previous Server Git push. This hotfix does **not** delete that backup directory from the live server.

After this hotfix is deployed, the next user-approved Review + Commit & Push will stage that tracked backup directory as a Git deletion while preserving the physical server backup. The review preview will disclose the cleanup before the Commit & Push button is authorized.

## Validation performed before publishing

- PHP syntax lint passed for both changed PHP files.
- Backup classification checks passed for `*-backup`, dated backup suffixes and `_backup`.
- A temporary Git repository integration test confirmed that a tracked backup directory is staged as `D` while the backup file remains physically present on disk.
