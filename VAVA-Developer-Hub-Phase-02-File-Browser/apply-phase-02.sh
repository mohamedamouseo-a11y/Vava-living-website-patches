#!/usr/bin/env bash
set -euo pipefail

SITE_ROOT="${1:-$(pwd)}"
PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_REL="wp-content/plugins/vava-developer-hub"
SOURCE_DIR="$PATCH_DIR/$PLUGIN_REL"
TARGET_DIR="$SITE_ROOT/$PLUGIN_REL"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${TMPDIR:-/tmp}/vava-devhub-phase02-$STAMP"

if [ ! -f "$TARGET_DIR/vava-developer-hub.php" ]; then
  echo "ERROR: Existing Vava Developer Hub was not found at $TARGET_DIR"
  exit 1
fi

for source_file in \
  "$SOURCE_DIR/vava-developer-hub.php" \
  "$SOURCE_DIR/includes/class-vava-devhub-file-browser.php" \
  "$SOURCE_DIR/assets/file-browser.js" \
  "$SOURCE_DIR/assets/file-browser.css"; do
  if [ ! -f "$source_file" ]; then
    echo "ERROR: Missing patch source file: $source_file"
    exit 1
  fi
done

if command -v php >/dev/null 2>&1; then
  php -l "$SOURCE_DIR/vava-developer-hub.php"
  php -l "$SOURCE_DIR/includes/class-vava-devhub-file-browser.php"
fi

mkdir -p "$BACKUP_DIR/includes" "$BACKUP_DIR/assets"
cp -p "$TARGET_DIR/vava-developer-hub.php" "$BACKUP_DIR/vava-developer-hub.php"
[ ! -f "$TARGET_DIR/includes/class-vava-devhub-file-browser.php" ] || cp -p "$TARGET_DIR/includes/class-vava-devhub-file-browser.php" "$BACKUP_DIR/includes/"
[ ! -f "$TARGET_DIR/assets/file-browser.js" ] || cp -p "$TARGET_DIR/assets/file-browser.js" "$BACKUP_DIR/assets/"
[ ! -f "$TARGET_DIR/assets/file-browser.css" ] || cp -p "$TARGET_DIR/assets/file-browser.css" "$BACKUP_DIR/assets/"

echo "Backup: $BACKUP_DIR"

# Supporting files first. Bootstrap is replaced last so an active plugin never
# requires a class before the class file exists.
install -m 0644 "$SOURCE_DIR/includes/class-vava-devhub-file-browser.php" "$TARGET_DIR/includes/class-vava-devhub-file-browser.php"
install -m 0644 "$SOURCE_DIR/assets/file-browser.js" "$TARGET_DIR/assets/file-browser.js"
install -m 0644 "$SOURCE_DIR/assets/file-browser.css" "$TARGET_DIR/assets/file-browser.css"
install -m 0644 "$SOURCE_DIR/vava-developer-hub.php" "$TARGET_DIR/vava-developer-hub.php"

if command -v php >/dev/null 2>&1; then
  php -l "$TARGET_DIR/vava-developer-hub.php"
  php -l "$TARGET_DIR/includes/class-vava-devhub-file-browser.php"
fi

if grep -q "VAVA_DEVHUB_VERSION', '1.1.0'" "$TARGET_DIR/vava-developer-hub.php"; then
  echo "Developer Hub Phase 02 installed: version 1.1.0"
else
  echo "ERROR: Version verification failed. Restore from $BACKUP_DIR"
  exit 1
fi

echo "No plugin activation command was run. Existing activation state was preserved."
