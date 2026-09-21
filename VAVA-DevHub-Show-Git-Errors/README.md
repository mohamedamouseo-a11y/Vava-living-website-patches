# VAVA Developer Hub — Show Git Failure Details

Work only on the CURRENT live Developer Hub plugin via FTP.

## Goal
Operation history currently shows only PASS/FAIL, while backend audit already stores sanitized error details. Show those existing details so the exact Push Pending failure can be diagnosed.

## Modify ONLY
`wp-content/plugins/vava-developer-hub/assets/server-git.js`

## Exact change
Inside `renderAudit(items)` only:
- keep current action/time/user/PASS-FAIL UI
- when `item.details` contains non-empty scalar values, render them as a small escaped detail line under the action metadata
- especially surface `details.error`
- use existing `escapeHtml()`
- do not render tokens/credentials; backend audit/redact already sanitizes them

Do not change:
- any PHP
- any Git command or runtime behavior
- buttons/actions
- API calls
- authentication
- CSS unless absolutely unnecessary (prefer existing <small> styling)
- theme/frontend

Do not execute git operations.
Do not push.

## Validation
- confirm only server-git.js changed
- confirm renderAudit now displays existing audit details
- confirm no Git action code changed
- run syntax/static check if available
- run git diff --check
