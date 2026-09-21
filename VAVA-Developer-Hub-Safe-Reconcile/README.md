# VAVA Developer Hub — Safe GitHub Reconcile

Work on the CURRENT live site via FTP only. Modify only the Developer Hub plugin/runtime needed for this feature.

## Goal
Add one temporary/admin-only Developer Hub action that safely prepares the live repository for a later normal "Push Pending Commits" from the Developer Hub UI.

Suggested label:
- EN: Prepare GitHub Sync
- AR: تجهيز المزامنة مع GitHub

## Important
All git commands MUST execute inside the existing Developer Hub server git runtime using its existing `proc_open` execution path.
Do not rely on SSH, shell access outside the plugin, or external probes.

## Action behavior
When the admin clicks the new action:

1. Confirm:
   - repository is connected/operational
   - current branch is `main`
   - remote `origin` exists

2. Inspect current working tree using the same safe-path/allowlist logic already used by Commit Push.
   Exclude temp probes, logs, cache, uploads, backups, generated temp files, and `.git`.

3. If there are legitimate uncommitted project changes:
   - stage only those safe paths
   - create ONE local commit:
     `VAVA final client updates before GitHub sync`
   If there are no uncommitted project changes, continue without creating an empty commit.

4. Run:
   - `git fetch origin main`
   - determine `origin/main`
   - compute ahead/behind

5. If local branch needs reconciliation, run:
   - `git rebase origin/main`

6. If rebase succeeds:
   - leave the rebased local commit(s) pending
   - DO NOT push
   - show exact success state in Developer Hub:
     - current branch
     - ahead/behind count
     - clean/dirty working tree
     - message that "Push Pending Commits" is now safe only when ahead > 0 and behind = 0

7. If ANY rebase conflict occurs:
   - collect conflicted filenames
   - immediately run `git rebase --abort` to restore the pre-rebase committed state
   - DO NOT resolve automatically
   - DO NOT push
   - show the conflicted filenames and a clear FAIL message in Developer Hub

## Error visibility
The current Hub records FAIL without a useful visible error. For this action, surface the sanitized git stderr/stdout in the operation result/details area so the admin can see the actual reason. Do not expose credentials/tokens.

## Safety
- Do not change remote URLs, credentials, branch tracking config, or GitHub authentication.
- Do not run reset --hard, clean -fd, checkout --, restore, force push, pull, merge, or destructive commands.
- Do not modify website/theme/plugin code except the Developer Hub implementation for this action.
- Do not push, merge, or create PRs.
- Keep existing Developer Hub actions unchanged.

## Validation
- PHP lint if available.
- `git diff --check` on edited Developer Hub files.
- Confirm the new action calls the existing runtime/proc_open path, not a separate execution mechanism.
- Confirm no push command is executed by the new action.
