#!/usr/bin/env bash
# Build script — OpenCart plugin'ini OC3 ve OC4 için ayrı .ocmod.zip'lere paketler.
#
# Usage:
#   bash build.sh              # version src/oc4/install.json'dan okur
#   bash build.sh 0.2.0        # explicit version
#
# Çıktı:
#   dist/dowaba-opencart-oc3-<version>.ocmod.zip  (OpenCart 3.x)
#   dist/dowaba_ai.ocmod.zip                       (OpenCart 4.x — adı install.json code ile aynı OLMALI)
#
# ÖNEMLI: OC4 marketplace/installer.php satır 98 (`$code = basename($file, '.ocmod.zip')`)
# zip ADINI olduğu gibi `code` olarak kullanır, install.json'daki code field'ını YOK SAYAR.
# Bu yüzden OC4 için zip adı tam olarak 'dowaba_ai.ocmod.zip' olmalı (install.json code ile birebir).
# Versiyon takibi için GitHub release tag'i ve install.json'daki version field'ı yeterli.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Version resolve
if [[ "${1:-}" ]]; then
  VERSION="$1"
elif [[ -f src/oc4/install.json ]]; then
  VERSION="$(grep -oE '"version"\s*:\s*"[^"]+"' src/oc4/install.json | head -1 | sed -E 's/.*"([^"]+)"$/\1/')"
else
  echo "ERROR: version source not found" >&2
  exit 1
fi

if [[ -z "${VERSION:-}" ]]; then
  echo "ERROR: VERSION empty" >&2
  exit 1
fi

echo "📦 Building dowaba-opencart v${VERSION} (OC3 + OC4)..."

# Pre-flight checks
if [[ ! -d src/oc4/upload ]]; then
  echo "ERROR: src/oc4/upload/ not found" >&2
  exit 1
fi
if [[ ! -d src/oc3/upload ]]; then
  echo "ERROR: src/oc3/upload/ not found" >&2
  exit 1
fi

mkdir -p dist
OUT_OC3="dist/dowaba-opencart-oc3-${VERSION}.ocmod.zip"
# OC4: zip adı install.json'daki `code` ile birebir aynı OLMALI (installer satır 98 zip ADINI code olarak kullanır).
# SADECE versiyonsuz zip — versiyonlu kopya kullanıcıları yanıltıyor (yanlış adı seçip "Extension does not exist" alıyorlar).
OUT_OC4="dist/dowaba_ai.ocmod.zip"
rm -f "$OUT_OC3" "$OUT_OC4"

# PHP syntax check — her iki source'u da kontrol et
echo "🔍 PHP syntax check..."
find src -name "*.php" -print0 | while IFS= read -r -d '' f; do
  if ! php -l "$f" > /dev/null 2>&1; then
    echo "❌ Syntax error in $f"
    php -l "$f"
    exit 1
  fi
done
echo "✅ PHP syntax OK (all files in src/)"

# OC4 build — install.json (OC4 native) + install.xml (Cloud Marketplace compat)
echo "📦 OC4: building $OUT_OC4..."
(cd src/oc4 && zip -qr "../../$OUT_OC4" install.json install.xml upload/ -x "*.DS_Store" "*/.git/*" "*/.gitkeep")
SIZE_OC4=$(du -k "$OUT_OC4" | cut -f1)
echo "  ✅ $OUT_OC4 (${SIZE_OC4} KB)"

# OC3 build
echo "📦 OC3: building $OUT_OC3..."
(cd src/oc3 && zip -qr "../../$OUT_OC3" install.xml upload/ -x "*.DS_Store" "*/.git/*" "*/.gitkeep")
SIZE_OC3=$(du -k "$OUT_OC3" | cut -f1)
echo "  ✅ $OUT_OC3 (${SIZE_OC3} KB)"

echo ""
echo "📋 Build summary:"
ls -lh "$OUT_OC3" "$OUT_OC4" 2>/dev/null
echo ""
echo "Upload to OpenCart admin → Extensions → Installer"
echo "  • OC 3.x: $OUT_OC3"
echo "  • OC 4.x: $OUT_OC4 (adı install.json code ile aynı — DEĞİŞTİRME)"
