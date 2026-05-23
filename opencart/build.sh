#!/usr/bin/env bash
# Build script — packages OpenCart plugin into .ocmod.zip for Marketplace/GitHub Release
#
# Usage:
#   bash build.sh              # uses version from src/install.json
#   bash build.sh 0.1.0        # explicit version override
#
# Output: dist/dowaba-opencart-<version>.ocmod.zip

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Version resolve
if [[ "${1:-}" ]]; then
  VERSION="$1"
elif [[ -f src/install.json ]]; then
  VERSION="$(grep -oE '"version"\s*:\s*"[^"]+"' src/install.json | head -1 | sed -E 's/.*"([^"]+)"$/\1/')"
else
  echo "ERROR: Cannot resolve version — neither argv[1] nor src/install.json available" >&2
  exit 1
fi

if [[ -z "${VERSION:-}" ]]; then
  echo "ERROR: VERSION empty" >&2
  exit 1
fi

echo "📦 Building dowaba-opencart v${VERSION}..."

# Pre-flight checks
if [[ ! -d src/upload ]]; then
  echo "ERROR: src/upload/ not found — nothing to package" >&2
  exit 1
fi

# Clean + prepare
mkdir -p dist
OUT="dist/dowaba-opencart-${VERSION}.ocmod.zip"
rm -f "$OUT"

# PHP syntax check
echo "🔍 PHP syntax check..."
find src/upload -name "*.php" -print0 | while IFS= read -r -d '' f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "❌ Syntax error in $f"
    php -l "$f"
    exit 1
  fi
done
echo "✅ PHP syntax OK"

# Build zip — OCMOD 4.x format: zip root contains install.json + upload/
cd src
zip -qr "../$OUT" install.json upload/ \
  -x "*.DS_Store" "*/.git/*" "*/.gitkeep"
cd ..

# Verify
SIZE_KB=$(du -k "$OUT" | cut -f1)
echo "✅ Built: $OUT (${SIZE_KB} KB)"
echo ""
echo "Contents:"
unzip -l "$OUT" | tail -n +4 | head -20
echo ""
echo "Upload to OpenCart admin → Extensions → Installer"
