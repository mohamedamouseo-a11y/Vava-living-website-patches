#!/usr/bin/env bash
set -euo pipefail

SITE_ROOT="${1:-$(pwd)}"
PATCH_URL="https://github.com/mohamedamouseo-a11y/Vava-living-website/compare/d9a45258df47257f41f8f5edb93ef7ec34a92822...c287295daeb06bbcc36882ad69f1df38eeb9cc4e.diff"
TMP_PATCH="$(mktemp /tmp/vava-developer-hub.XXXXXX.patch)"
trap 'rm -f "$TMP_PATCH"' EXIT

cd "$SITE_ROOT"

if [ ! -d .git ]; then
  echo "ERROR: $SITE_ROOT is not a Git working tree."
  exit 1
fi

if [ -e wp-content/plugins/vava-developer-hub ]; then
  echo "ERROR: wp-content/plugins/vava-developer-hub already exists. Stop and review before reapplying."
  exit 1
fi

if command -v curl >/dev/null 2>&1; then
  curl -fsSL "$PATCH_URL" -o "$TMP_PATCH"
elif command -v wget >/dev/null 2>&1; then
  wget -qO "$TMP_PATCH" "$PATCH_URL"
else
  echo "ERROR: curl or wget is required."
  exit 1
fi

if [ ! -s "$TMP_PATCH" ]; then
  echo "ERROR: downloaded patch is empty."
  exit 1
fi

echo "Running patch pre-check..."
git apply --check "$TMP_PATCH"

echo "Applying VAVA Developer Hub patch..."
git apply "$TMP_PATCH"

if command -v php >/dev/null 2>&1; then
  echo "Running PHP lint..."
  find wp-content/plugins/vava-developer-hub -type f -name '*.php' -print0 | while IFS= read -r -d '' file; do
    php -l "$file"
  done
fi

if command -v wp >/dev/null 2>&1; then
  echo "Activating WordPress plugin..."
  wp plugin activate vava-developer-hub
else
  echo "WP-CLI not found. Activate 'Vava Developer Hub' manually from WordPress Admin -> Plugins."
fi

echo "VAVA Developer Hub patch applied successfully."
echo "Plugin path: $SITE_ROOT/wp-content/plugins/vava-developer-hub"
