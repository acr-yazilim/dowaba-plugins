#!/usr/bin/env bash
# Dowaba AI Connector (Magento) — smoke test.
#
# Usage:
#   BASE_URL=http://localhost:8080 API_KEY=mgm_xxxx bash test/e2e.sh
#   bash test/e2e.sh https://store.com mgm_xxxx
#
# Checks: manifest endpoint, auth rejection, unknown action, authed product_search.
# Requires: curl, jq.

set -uo pipefail

BASE_URL="${1:-${BASE_URL:-http://localhost:8080}}"
API_KEY="${2:-${API_KEY:-}}"
BASE_URL="${BASE_URL%/}"

PASS=0; FAIL=0
ok()   { echo "  ✅ $1"; PASS=$((PASS+1)); }
bad()  { echo "  ❌ $1"; FAIL=$((FAIL+1)); }

if ! command -v jq >/dev/null 2>&1; then echo "jq gerekli (brew install jq)"; exit 1; fi

echo "🔌 BASE_URL=$BASE_URL"
echo ""

# --- 1) Manifest endpoint
echo "1) GET /dowaba_ai/manifest"
MANIFEST=$(curl -s -w '\n%{http_code}' "$BASE_URL/dowaba_ai/manifest")
CODE=$(echo "$MANIFEST" | tail -1)
BODY=$(echo "$MANIFEST" | sed '$d')
[ "$CODE" = "200" ] && ok "HTTP 200" || bad "HTTP $CODE"
SCHEMA=$(echo "$BODY" | jq -r '.schema_version // empty' 2>/dev/null)
[ -n "$SCHEMA" ] && ok "schema_version=$SCHEMA" || bad "schema_version yok"
FNC=$(echo "$BODY" | jq -r '.functions | length' 2>/dev/null)
[ "$FNC" = "10" ] && ok "10 function" || bad "function sayısı=$FNC (beklenen 10)"
PLATFORM=$(echo "$BODY" | jq -r '.platform // empty' 2>/dev/null)
[ "$PLATFORM" = "magento" ] && ok "platform=magento" || bad "platform=$PLATFORM"
echo ""

# --- 2) Auth rejection (no Bearer)
echo "2) GET /dowaba_ai/api?action=products (auth YOK)"
CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/dowaba_ai/api?action=products&q=test")
[ "$CODE" = "401" ] && ok "HTTP 401 (Bearer required)" || bad "HTTP $CODE (beklenen 401)"
echo ""

# --- 3) Unknown action
echo "3) GET /dowaba_ai/api?action=bogus"
CODE=$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/dowaba_ai/api?action=bogus&token=${API_KEY:-x}")
{ [ "$CODE" = "400" ] || [ "$CODE" = "401" ]; } && ok "HTTP $CODE (reddedildi)" || bad "HTTP $CODE"
echo ""

# --- 4) Authed product_search
if [ -n "$API_KEY" ]; then
  echo "4) GET /dowaba_ai/api?action=products (Bearer ile)"
  RESP=$(curl -s -w '\n%{http_code}' -H "Authorization: Bearer $API_KEY" "$BASE_URL/dowaba_ai/api?action=products&q=a&limit=3")
  CODE=$(echo "$RESP" | tail -1); BODY=$(echo "$RESP" | sed '$d')
  if [ "$CODE" = "200" ]; then
    CNT=$(echo "$BODY" | jq -r '.count // 0' 2>/dev/null)
    ok "HTTP 200 (count=$CNT)"
  elif [ "$CODE" = "503" ]; then
    bad "HTTP 503 — modül kapalı veya API key üretilmedi (admin wizard'ı tamamla)"
  else
    bad "HTTP $CODE"
  fi
else
  echo "4) (atlandı — API_KEY verilmedi)"
fi

echo ""
echo "── Sonuç: $PASS PASS / $FAIL FAIL ──"
[ "$FAIL" = "0" ] && exit 0 || exit 1
