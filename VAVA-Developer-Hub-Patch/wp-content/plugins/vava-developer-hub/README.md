# Vava Developer Hub

Operational WordPress admin module for the Vava Living engineering workflow.

## What it provides

- GitHub connection status without exposing credentials to browser JavaScript.
- Switch between `Vava-living-website` and `Vava-living-website-patches`.
- Branch selector and recent commit activity.
- Open pull-request view.
- Branch comparison with changed-file patch previews.
- Two-step controlled push: Review & Security Check -> Authorize Push.
- 10-minute, single-use review authorization.
- Remote SHA revalidation immediately before write to prevent pushing over a newer remote edit.
- Server-side repository/path allowlists, file-size/type limits, protected-path rules and credential scanning.

## Server configuration

The module works in read-only mode against public GitHub data without a token. Authenticated access and Controlled Push require a GitHub token stored on the server only.

Preferred: set an environment variable available to PHP-FPM / the web server:

```bash
VAVA_GITHUB_TOKEN=your_token_here
VAVA_GITHUB_OWNER=mohamedamouseo-a11y
```

Alternative in `wp-config.php` (never commit the real token):

```php
define('VAVA_GITHUB_TOKEN', getenv('VAVA_GITHUB_TOKEN') ?: '');
define('VAVA_GITHUB_OWNER', 'mohamedamouseo-a11y');
```

The token must have repository read access. Controlled Push additionally requires write access to repository contents.

## WordPress activation

1. Deploy the `wp-content/plugins/vava-developer-hub/` directory to the server.
2. Open WordPress Admin -> Plugins.
3. Activate **Vava Developer Hub**.
4. Open **Developer Hub** in the WordPress Admin sidebar.

## Controlled Push policy

For the main website repository, writes are restricted to:

- `wp-content/themes/vava-living-theme-ar-v1/`
- `wp-content/plugins/vava-developer-hub/`

Developer Hub blocks WordPress core/configuration, `.env`, uploads, caches, Git internals, workflow files and obvious credential/private-key signatures.

Controlled Push never deploys the server automatically. It writes an explicitly reviewed file to the selected GitHub branch. Server deployment remains a separate operation.
