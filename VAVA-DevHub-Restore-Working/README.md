# VAVA Developer Hub — Restore Working State

Work only on the CURRENT live Developer Hub plugin via FTP.

## Goal
Restore the Developer Hub admin page to its last known working state by removing ONLY the recent Prepare GitHub Sync / server-git.js cache-busting changes that introduced the fatal.

## Known-good reference
Use GitHub main as the reference ONLY for the affected sections:
Repo: mohmedamouseo-a11y/Vava-living-website
Path:
- wp-content/plugins/vava-developer-hub/includes/class-vava-devhub-server-git.php
- wp-content/plugins/vava-developer-hub/assets/server-git.js

Important: do NOT blindly overwrite the entire live files if they contain unrelated newer live changes. Diff live vs GitHub main and revert only the recent additions related to:
- wp_ajax_vava_devhub_git_prepare_sync
- ajax_prepare_sync()
- Prepare GitHub Sync button/handler
- filemtime cache-busting added specifically for server-git.js

Restore the original server-git.js enqueue version behavior from the known-good GitHub main file.

## Preserve
Keep all original existing Developer Hub actions unchanged:
- status
- connect
- preview/review
- commit push
- push pending
- pull
- original activity/history UI

Do not modify theme/frontend code.
Do not execute any git operation.
Do not push.

## Validation
- compare affected sections against GitHub main
- confirm no Prepare GitHub Sync hook/method/button remains
- confirm Developer Hub authenticated admin page loads without fatal
- confirm Server Git Sync tab renders
- run git diff --check on changed plugin files
