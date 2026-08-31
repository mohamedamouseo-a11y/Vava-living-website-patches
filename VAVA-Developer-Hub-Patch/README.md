# VAVA Developer Hub Patch

Self-contained, server-ready deployment package for the Vava Living Developer Hub WordPress module.

## Source reference

The package mirrors the reviewed implementation from:

- Main repository: `mohamedamouseo-a11y/Vava-living-website`
- Base commit: `d9a45258df47257f41f8f5edb93ef7ec34a92822`
- Developer Hub commit: `c287295daeb06bbcc36882ad69f1df38eeb9cc4e`
- Original review PR: `#3`

The deployable source is now stored directly inside this patch repository under:

`VAVA-Developer-Hub-Patch/wp-content/plugins/vava-developer-hub/`

## What gets added

Only this standalone WordPress plugin:

`wp-content/plugins/vava-developer-hub/`

It does not modify WordPress Core or the active Vava theme.

## Apply on the server

Clone/pull `Vava-living-website-patches` on the server, then run:

```bash
bash /PATH/TO/Vava-living-website-patches/VAVA-Developer-Hub-Patch/apply-developer-hub.sh /PATH/TO/VAVA-WORDPRESS
```

The installer:

1. Confirms the target looks like a WordPress root.
2. Refuses to overwrite an existing `vava-developer-hub` plugin.
3. Copies the Developer Hub source directly from this patch repository.
4. Runs PHP lint when PHP CLI is available.
5. Activates the plugin automatically when WP-CLI is available.

## Manual copy alternative

```bash
cp -a \
  /PATH/TO/Vava-living-website-patches/VAVA-Developer-Hub-Patch/wp-content/plugins/vava-developer-hub \
  /PATH/TO/VAVA-WORDPRESS/wp-content/plugins/

cd /PATH/TO/VAVA-WORDPRESS
wp plugin activate vava-developer-hub
```

The original immutable diff reference is retained in `patches/PATCH-SOURCE.txt` for audit/reference only.

## GitHub access after deployment

Read-only repository monitoring works against the public repositories without a token.

Controlled Push requires a server-side token only:

```bash
VAVA_GITHUB_TOKEN=your_token_here
VAVA_GITHUB_OWNER=mohamedamouseo-a11y
```

Never commit the real token into either Vava repository.
