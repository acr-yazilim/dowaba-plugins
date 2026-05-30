#!/usr/bin/env bash
# Build script — packages the Dowaba_AiConnector Magento 2 module.
#
# Usage:
#   bash build.sh             # version read from src/Dowaba/AiConnector/composer.json
#   bash build.sh 0.2.0       # explicit version
#
# Output:
#   dist/dowaba-magento-aiconnector-<version>.zip   → unzip at Magento ROOT (app/code/Dowaba/AiConnector/...)
#   dist/Dowaba_AiConnector-<version>.zip           → Marketplace/composer package (module root at zip top)
#
# Version is the single authority in composer.json; build.sh syncs module.xml
# setup_version and Model\Manifest::PLUGIN_VERSION via sed.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

MODULE_DIR="src/Dowaba/AiConnector"
COMPOSER_JSON="$MODULE_DIR/composer.json"

if [[ ! -f "$COMPOSER_JSON" ]]; then
  echo "ERROR: $COMPOSER_JSON not found" >&2
  exit 1
fi

# Version resolve
if [[ "${1:-}" ]]; then
  VERSION="$1"
else
  VERSION="$(grep -oE '"version"[[:space:]]*:[[:space:]]*"[^"]+"' "$COMPOSER_JSON" | head -1 | sed -E 's/.*"([^"]+)"$/\1/')"
fi
if [[ -z "${VERSION:-}" ]]; then
  echo "ERROR: VERSION empty" >&2
  exit 1
fi

echo "📦 Building Dowaba_AiConnector (Magento 2) v${VERSION}..."

# --- PHP syntax check
echo "🔍 PHP syntax check..."
find "$MODULE_DIR" -name "*.php" -print0 | while IFS= read -r -d '' f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "❌ Syntax error in $f"; php -l "$f"; exit 1
  fi
done
echo "✅ PHP syntax OK"

# --- XML well-formedness (if xmllint available)
if command -v xmllint > /dev/null 2>&1; then
  echo "🔍 XML well-formedness..."
  find "$MODULE_DIR" -name "*.xml" -print0 | while IFS= read -r -d '' f; do
    if ! xmllint --noout "$f" 2>/dev/null; then
      echo "❌ XML error in $f"; xmllint --noout "$f"; exit 1
    fi
  done
  echo "✅ XML OK"
else
  echo "⚠️  xmllint not found — skipping XML validation"
fi

# --- Version sync (composer.json is authority)
echo "🔁 Version sync (v${VERSION})..."
sed -i.bak -E "s/(\"version\"[[:space:]]*:[[:space:]]*\")[^\"]+(\")/\1${VERSION}\2/" "$COMPOSER_JSON"
sed -i.bak -E "s/(setup_version=\")[^\"]+(\")/\1${VERSION}\2/" "$MODULE_DIR/etc/module.xml"
sed -i.bak -E "s/(const PLUGIN_VERSION = ')[^']+(')/\1${VERSION}\2/" "$MODULE_DIR/Model/Manifest.php"
find "$MODULE_DIR" -name "*.bak" -delete

MODULE_VER=$(grep -oE "setup_version=\"[^\"]+\"" "$MODULE_DIR/etc/module.xml" | sed -E 's/.*"([^"]+)"/\1/')
MANIFEST_VER=$(grep -oE "PLUGIN_VERSION = '[^']+'" "$MODULE_DIR/Model/Manifest.php" | sed -E "s/.*'([^']+)'/\1/")
if [[ "$MODULE_VER" != "$VERSION" || "$MANIFEST_VER" != "$VERSION" ]]; then
  echo "❌ Version mismatch: composer=$VERSION module.xml=$MODULE_VER manifest=$MANIFEST_VER"; exit 1
fi
echo "  ✅ module.xml=${MODULE_VER}, Manifest=${MANIFEST_VER}"

mkdir -p dist
OUT_APPCODE="dist/dowaba-magento-aiconnector-${VERSION}.zip"
OUT_PACKAGE="dist/Dowaba_AiConnector-${VERSION}.zip"
rm -f "$OUT_APPCODE" "$OUT_PACKAGE"

EXCLUDES=( -x "*.DS_Store" "*/.git/*" "*/.gitkeep" )

# --- app/code layout zip (unzip at Magento root)
echo "📦 app/code zip: $OUT_APPCODE..."
STAGE="$(mktemp -d)"
mkdir -p "$STAGE/app/code/Dowaba"
cp -a "$MODULE_DIR" "$STAGE/app/code/Dowaba/AiConnector"
( cd "$STAGE" && zip -qr "$SCRIPT_DIR/$OUT_APPCODE" app "${EXCLUDES[@]}" )
rm -rf "$STAGE"
echo "  ✅ $OUT_APPCODE ($(du -k "$OUT_APPCODE" | cut -f1) KB)"

# --- Marketplace/composer package zip (module root at top)
echo "📦 package zip: $OUT_PACKAGE..."
( cd "$MODULE_DIR" && zip -qr "$SCRIPT_DIR/$OUT_PACKAGE" . "${EXCLUDES[@]}" )
echo "  ✅ $OUT_PACKAGE ($(du -k "$OUT_PACKAGE" | cut -f1) KB)"

echo ""
echo "📋 Build summary:"
ls -lh "$OUT_APPCODE" "$OUT_PACKAGE" 2>/dev/null
echo ""
echo "Manual install:  unzip $OUT_APPCODE at Magento root → bin/magento setup:upgrade"
echo "Marketplace:     upload $OUT_PACKAGE to the Magento Marketplace developer portal"
