#!/usr/bin/env bash
# E2E smoke test — Plugin end-to-end doğrulama
#
# Önkoşul: docker compose up -d (OpenCart 4.x ayakta), plugin yüklü + setup tamamlanmış
#
# Test akışı:
#   1. Manifest fetch + schema_version=1.0 doğrula
#   2. Bearer auth: doğru key → 200, yanlış key → 401
#   3. 8 read-only function curl ile çağrı (search/detail/compare/stock/category/order_status/customer_lookup/cart_recover)
#   4. Order flow: preview → confirm → 410 Gone (replay)
#   5. dowaba_audit DB satır sayısı verify

set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
API_KEY="${DOWABA_API_KEY:-}"  # set in env veya plugin admin'den kopyala

if [[ -z "$API_KEY" ]]; then
  echo "ERROR: DOWABA_API_KEY env var set edilmedi"
  echo "Plugin admin'inden API key kopyala: DOWABA_API_KEY=opc_xxx bash test/e2e.sh"
  exit 1
fi

PASS=0
FAIL=0

assert_eq() {
  local expected="$1"
  local actual="$2"
  local label="$3"
  if [[ "$expected" == "$actual" ]]; then
    echo "✅ $label"
    PASS=$((PASS+1))
  else
    echo "❌ $label — expected=$expected actual=$actual"
    FAIL=$((FAIL+1))
  fi
}

# TODO Faz 4: Aşağıdaki test case'ler doldurulacak
# Şu an placeholder — Faz 1+2+3 bitince yazılır

echo "=== Dowaba OpenCart Plugin E2E ==="
echo "BASE_URL: $BASE_URL"
echo "API_KEY:  ${API_KEY:0:12}..."
echo ""

# 1. Manifest
echo "1. Manifest fetch..."
# MANIFEST=$(curl -fsS "$BASE_URL/index.php?route=extension/dowaba/manifest")
# SCHEMA=$(echo "$MANIFEST" | jq -r '.schema_version')
# assert_eq "1.0" "$SCHEMA" "Manifest schema_version=1.0"

echo "⏳ Faz 4 sonu doldurulacak"
echo ""
echo "==== RESULT: $PASS pass / $FAIL fail ===="

[[ $FAIL -eq 0 ]] || exit 1
