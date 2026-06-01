# Changelog

## [Unreleased] - 2026-06-01 — Tek-tık "Connect to DoWaba"

### Added
- Ayar ekranına **"Connect to DoWaba"** butonu → `dowaba.com/admin/connect?platform=woocommerce&manifest=…` (manifest pre-filled). **Regenerate API Key** sonrası plain key butonun URL fragment'ine (`#k=`) eklenir → tam tek-tık (PrestaShop paritesi; fragment sunucuya/log'a gitmez).

### ✅ Test — 2026-06-01 (CANLI)
- Docker WP 6.7 + WooCommerce 9.5.1 (localhost:8090), gerçek wp-admin "DoWaba AI Settings".
- Connect butonu **render oluyor** ✓; href doğrulandı: `dowaba.com/admin/connect?platform=woocommerce&manifest=<…/wp-json/dowaba/v1/manifest>` ✓; manifest input dolu ✓.
- **Görsel + fonksiyon e2e (CANLI, görselli seed ürün):** product_search → `cover` + `thumb` (300×300) ✓;
  product_detail → `gallery_images` **3 görsel** (`is_cover`/`position`/`thumb`/`medium`/`full`) ✓; **10/10
  fonksiyon** doğru (compare 2-id validasyonu, stock=100, order/customer IDOR guard, cart_recover OK,
  write'lar scope-guard ile default-deny; scope açılınca order_preview→order_confirm **gerçek sipariş #14**
  447 USD).

## [0.3.0] - 2026-05-28

### Added — Görsel desteği (tüm kanallar)

- **Ürün listesinde `cover` field** (`woocommerce_single` ~600×600, fallback `full`) — mevcut `thumb` (300×300) korundu. AI'a iki boyut da expose edildi.
- **Ürün detayında `gallery_images[]` dizisi** — kapak + tüm WC `gallery_image_ids` her biri için `{thumb, medium, full, is_cover, position, alt}` döner. AI artık ürün galerisinden çoklu görsel okur, "kapak + 2 ek = max 3" kuralıyla kanallara native attach edilir.
- **Karşılaştırma response'unda `cover`** — `shape_product` generic değiştiği için 2-3 ürün karşılaştırmada her birinin kapak görseli otomatik döner.

### Fixed — Dowaba template engine path uyumu

- **`/product/{id}` → `/product/{{arg.product_id}}`**, **`/order/{id}` → `/order/{{arg.order_id}}`** — Dowaba runtime sadece `{{arg.X}}` double-brace template'leri parse eder; `{id}` single-brace WordPress REST regex sözdizimi literal kalıyordu → `wcm_product_detail` ve `wcm_order_status` her çağrıda 404 dönüyordu. OpenCart v0.2.12'de aynı düzeltme yapılmıştı; WooCommerce'te bu sürüme kadar kaçmıştı.

### Backend (Dowaba ana repo, eşzamanlı destek)

- `UnifiedAIService.FUNCTION_CALLING_RULES` SHOP GÖRSEL DESTEĞİ bloku eklendi (`[GORSEL:url]` direktifi + zorunlu format + yasak markdown image syntax).
- `AiMediaTagParser` markdown image fallback (`![alt](url)` + `[![alt](img)](link)` yakalar — AI prompt'tan saparsa kanal yine görseli attach eder).
- `MarkdownHelper.toHtml` negative lookbehind `(?<!\[GORSEL:)` ile linkifier tag URL'lerini bozmaz.
- `HttpHandler` GET fix: `Http::get($url, [])` Guzzle URL'in query string'ini strip ediyordu → `$query = null` (manuel inşa edilen `?q=...&limit=...` korunur).
- `BundleImportController` response'a `is_active` field + dinamik note ("otomatik aktive edildi" vs "tek tek aktive et") — `auto_activate: true` bundle'lar yanıltıcı pasif uyarısı göstermez.

### Notes

- Manifest schema `wcm_*` (v0.2.0'dan beri), 10 function aynı slug'lar. Migration gerekmez — mevcut Bundle Import "Var olanı güncelle" ile alınır, eski function tanımları yeni cover/gallery field tanımları + path template ile değişir.
- Live tested: WP 6.7 + WC 9.5.2 + PHP 8.2 + cloudflared tunnel + Dowaba prod site_id=76. `iphone var mı` → 2 ürün cover grid (Widget HTML), `iphone 15 pro detay` → 3 görsel galeri.

## [0.2.0] - 2026-05-26

### ⚠️ BREAKING

- **Tüm function slug ve API key prefix `opc_*` → `wcm_*`** — WooCommerce plugin'i yanlışlıkla OpenCart prefix kullanıyordu (35 yerde). Bayi aynı site'a hem OpenCart hem Woo plugin bağlarsa `opc_product_search` çakışıyordu. Şimdi `wcm_product_search`, `wcm_order_preview` … (10 fn) + Bearer key generator `wcm_` + Auth regex `/^wcm_[a-f0-9]{32,128}$/i`.
  - **Migration:** Mevcut kurulumlar için Dowaba paneli → Bundle Import yeniden çağrılmalı (eski `opc_*` function tanımları silinir, yeni `wcm_*` ile değiştirilir).

### Fixed

- **`wcm_order_preview` Gemini JSON Schema strict regression** — `items: {type: array}` ve `customer: {type: object}` boş tanımlıydı → Gemini "function declaration invalid" 400 reject → AI tüm tool listesini reddediyor → kullanıcıya silent "Şu an yanıt veremiyoruz" fallback. Birebir OpenCart 3'te yaşanan vakanın eşi (PLUGIN_DEV_GUIDE.md §3). Fix: nested `items.items.{product_id, quantity}` + `customer.properties.{phone, email, name, address, city}`.
- **Audit log lazy retention cleanup** — `purge_old()` metodu vardı ama `wp_schedule_event` ile cron'a bağlı değildi → audit tablosu sınırsız büyüme bug'ı. Şimdi `write()` her çağrıda 1/500 ihtimalle `dowaba_ai_audit_retention_days` (default 30) eski log'ları siler. Production disk-fill önlenir (WP cron disable edilse bile çalışır).

### Notes

- Backend (Dowaba ana repo) `BundleImportController::validateManifest` recursive Gemini JSON Schema check eklendi (2026-05-26). Yeni invalid manifest gönderildiğinde 422 ile import-time reject edilir.
- `UnifiedAIService::callGeminiWithRetry` 400 body parse → `schema_invalid` UserErrorRecorder banner. Plugin geliştiriciler artık debug için Laravel log + audit log birlikte kullanabilir.

## [0.1.0] - 2026-05-23

### Added — Initial release
- WordPress plugin (standalone, WooCommerce dependency)
- 10 AI functions via WP REST API (`/wp-json/dowaba/v1/`):
  - `opc_product_search`, `opc_product_detail`, `opc_product_compare`
  - `opc_stock_check`, `opc_category_list`
  - `opc_order_status`, `opc_customer_lookup`, `opc_cart_recover`
  - `opc_order_preview`, `opc_order_confirm` (2-step confirmed order create)
- Admin settings page (DoWaba AI menu, 5-step setup wizard)
- Bearer auth + SHA-256 hash + IP whitelist
- Scope guard (read/write toggle, write disabled by default)
- Order preview cache via WP Transients API (5-min TTL)
- Audit log table (`wp_dowaba_audit`, 30-day retention)
- WC API integration: `wc_get_products()`, `wc_create_order()`, `WC_Order::add_product()`
- Manifest endpoint for DoWaba Bundle Import
- Compatibility: WP 6.0+, WC 7.0+, PHP 8.0+
- Live tested: WP 6.7 + WC 10.7 + PHP 8.2 + MariaDB 11

### Canlı doğrulama (2026-05-23)
- ✅ Dowaba prod (site_id=76 "WooCommerce Test") bundle import → 10 fn auto_activate
- ✅ `opc_product_search` "iPhone" → 2 ürün (iPhone 15 Pro 64999 + iPhone 15 49999)
- ✅ `opc_order_preview` + `opc_order_confirm` → WC order #16 yaratıldı (64999 USD)
- ✅ Replay attack → 410 Gone (cache one-shot consume)
