#!/usr/bin/env bash
# WooCommerce plugin build — WordPress standardı ZIP paketi.

set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ "${1:-}" ]]; then
  VERSION="$1"
else
  VERSION=$(grep -oE "Version:\s*[0-9]+\.[0-9]+\.[0-9]+" dowaba-ai.php | head -1 | awk '{print $2}')
fi

if [[ -z "${VERSION:-}" ]]; then echo "ERROR: VERSION not found" >&2; exit 1; fi
echo "📦 Building dowaba-ai v${VERSION}..."

mkdir -p dist
OUT="dist/dowaba-ai-${VERSION}.zip"
rm -f "$OUT"

echo "🔍 PHP syntax check..."
find . -name "*.php" -not -path "./dist/*" -not -path "./docker/*" -print0 | while IFS= read -r -d '' f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "❌ Syntax error in $f"; php -l "$f"; exit 1
  fi
done
echo "✅ PHP OK"

WORK=$(mktemp -d)
mkdir -p "$WORK/dowaba-ai"
cp -r dowaba-ai.php readme.txt uninstall.php includes admin languages "$WORK/dowaba-ai/" 2>/dev/null || true
[[ -f LICENSE ]] && cp LICENSE "$WORK/dowaba-ai/"

cd "$WORK"
zip -qr "$SCRIPT_DIR/$OUT" dowaba-ai/ -x "*.DS_Store" "*/.git/*"
cd "$SCRIPT_DIR"
rm -rf "$WORK"

SIZE=$(du -k "$OUT" | cut -f1)
echo "✅ $OUT (${SIZE} KB)"
echo ""
unzip -l "$OUT" | head -15
