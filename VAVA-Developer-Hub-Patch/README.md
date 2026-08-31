# VAVA Developer Hub Patch

Server-ready deployment package for the Vava Living Developer Hub WordPress module.

## Source

This package is pinned to the reviewed Developer Hub implementation from:

- Main repository: `mohamedamouseo-a11y/Vava-living-website`
- Base commit: `d9a45258df47257f41f8f5edb93ef7ec34a92822`
- Developer Hub commit: `c287295daeb06bbcc36882ad69f1df38eeb9cc4e`
- Original review PR: `#3`

## What gets added

Only this standalone WordPress plugin:

`wp-content/plugins/vava-developer-hub/`

It does not modify WordPress Core or the active Vava theme.

## Apply on the server

From the live Vava WordPress Git working tree:

```bash
bash /PATH/TO/Vava-living-website-patches/VAVA-Developer-Hub-Patch/apply-developer-hub.sh /PATH/TO/VAVA-WORDPRESS
```

The installer:

1. Confirms the target is a Git working tree.
2. Refuses to continue if the Developer Hub plugin already exists.
3. Downloads the exact pinned Git diff between the two commit SHAs.
4. Runs `git apply --check` first.
5. Applies the patch only if the check passes.
6. Runs PHP lint on the plugin PHP files when PHP CLI is available.
7. Activates the plugin automatically when WP-CLI is available.

## Manual application

The exact immutable diff source is stored in `patches/PATCH-SOURCE.txt`.

You can also download that diff and run:

```bash
git apply --check /tmp/vava-developer-hub.patch
git apply /tmp/vava-developer-hub.patch
wp plugin activate vava-developer-hub
```

## GitHub access after deployment

Read-only repository monitoring works against the public repos without a token.

Controlled Push requires a server-side token only:

```bash
VAVA_GITHUB_TOKEN=your_token_here
VAVA_GITHUB_OWNER=mohamedamouseo-a11y
```

Never commit the real token into either Vava repository.
