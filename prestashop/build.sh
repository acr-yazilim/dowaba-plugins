#!/usr/bin/env bash
# PrestaShop module build — addons.prestashop.com format ZIP

set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ "${1:-}" ]]; then
  VERSION="$1"
else
  VERSION=$(grep -oE "version\s*=\s*'[0-9]+\.[0-9]+\.[0-9]+'" src/dowaba_ai/dowaba_ai.php | head -1 | grep -oE "[0-9]+\.[0-9]+\.[0-9]+")
fi

if [[ -z "${VERSION:-}" ]]; then echo "ERROR: VERSION not found" >&2; exit 1; fi
echo "📦 Building dowaba-ai-prestashop v${VERSION}..."

mkdir -p dist
OUT="dist/dowaba-ai-prestashop-${VERSION}.zip"
rm -f "$OUT"

echo "🔍 PHP syntax check..."
find src -name "*.php" -print0 | while IFS= read -r -d '' f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "❌ Syntax error in $f"; php -l "$f"; exit 1
  fi
done
echo "✅ PHP OK"

# PrestaShop convention: dowaba_ai/ folder inside zip
cd src
zip -qr "$SCRIPT_DIR/$OUT" dowaba_ai/ -x "*.DS_Store" "*/.git/*"
cd "$SCRIPT_DIR"

SIZE=$(du -k "$OUT" | cut -f1)
echo "✅ $OUT (${SIZE} KB)"
unzip -l "$OUT" | head -15
