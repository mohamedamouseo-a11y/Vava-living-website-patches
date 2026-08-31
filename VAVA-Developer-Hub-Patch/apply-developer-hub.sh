#!/usr/bin/env bash
set -euo pipefail

SITE_ROOT="${1:-$(pwd)}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_PLUGIN="$SCRIPT_DIR/wp-content/plugins/vava-developer-hub"
TARGET_PLUGIN="$SITE_ROOT/wp-content/plugins/vava-developer-hub"

if [ ! -d "$SITE_ROOT" ]; then
  echo "ERROR: target site root does not exist: $SITE_ROOT"
  exit 1
fi

if [ ! -f "$SITE_ROOT/wp-config.php" ] && [ ! -d "$SITE_ROOT/wp-admin" ]; then
  echo "ERROR: target does not look like a WordPress root: $SITE_ROOT"
  exit 1
fi

if [ ! -f "$SOURCE_PLUGIN/vava-developer-hub.php" ]; then
  echo "ERROR: Developer Hub source is missing from the patch repository."
  exit 1
fi

if [ -e "$TARGET_PLUGIN" ]; then
  echo "ERROR: $TARGET_PLUGIN already exists. Stop and review before reapplying."
  exit 1
fi

mkdir -p "$SITE_ROOT/wp-content/plugins"
cp -a "$SOURCE_PLUGIN" "$TARGET_PLUGIN"

echo "Developer Hub files copied from Vava-living-website-patches."

if command -v php >/dev/null 2>&1; then
  echo "Running PHP lint..."
  while IFS= read -r -d '' file; do
    php -l "$file"
  done < <(find "$TARGET_PLUGIN" -type f -name '*.php' -print0)
fi

if command -v wp >/dev/null 2>&1; then
  echo "Activating WordPress plugin..."
  (cd "$SITE_ROOT" && wp plugin activate vava-developer-hub)
else
  echo "WP-CLI not found. Activate 'Vava Developer Hub' manually from WordPress Admin -> Plugins."
fi

echo "VAVA Developer Hub patch applied successfully."
echo "Plugin path: $TARGET_PLUGIN"
