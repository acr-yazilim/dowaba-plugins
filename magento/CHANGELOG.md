# Changelog

Tüm önemli değişiklikler bu dosyada listelenir. [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) formatı, [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kuralları.

## [Unreleased] - 2026-06-01 — Tek-tık "Connect to DoWaba"

### Added
- `settings.phtml` Step 2'ye **"Connect to DoWaba →"** butonu → `dowaba.com/admin/connect?platform=magento&manifest=…` (`escapeUrl` + `rawurlencode`; Block/composer'a dokunulmadı).

### ✅ Test — 2026-06-01 (CANLI)
- Magento **2.4.7** sıfırdan kuruldu (public GitHub source + MySQL/OpenSearch Docker + `php -S localhost:8087`, runbook ile); modül `app/code/Dowaba/AiConnector` enable + setup:upgrade.
- Gerçek Magento admin **"Dowaba AI — Setup & Settings"** sayfası: **"Connect to DoWaba" butonu render oluyor** ✓; href doğrulandı: `dowaba.com/admin/connect?platform=magento&manifest=<…/dowaba_ai/manifest>` ✓; manifest input dolu ✓.
- Henüz release edilmedi (sadece kaynak).

## [0.1.0] - 2026-05-30

### Added — İlk sürüm (OpenCart plugin'inden port)

- **`Dowaba_AiConnector` Magento 2 modülü** — Open Source & Adobe Commerce 2.4.4–2.4.7, PHP 8.1/8.2/8.3.
- **Public manifest endpoint** — `GET /dowaba_ai/manifest`, tunnel/proxy-aware dinamik `base_url` resolve
  (X-Forwarded-Host → HTTP_HOST → store base URL), admin override desteği.
- **10 AI function** (`mgm_*` slug prefix) — `Controller\Api\Index` tek `?action=` dispatcher + `Model\Api\Dispatcher`:
  - **read (8):** product_search (collection LIKE name/sku), product_detail (+ visible-on-front attributes),
    product_compare (common vs differences), stock_check (StockRegistry), category_list (root=level 2),
    order_status (increment_id/entity_id + email match — IDOR guard), customer_lookup (email + phone via
    customer_address_entity, son 5 sipariş), cart_recover.
  - **write (2):** order_preview (stok kontrolü + total + preview cache 5dk TTL) → order_confirm
    (Quote → guest order, Check/Money Order + Flat Rate, one-shot consume).
- **Güvenlik katmanı:**
  - `Model\Auth` — Bearer `mgm_` + sha256 `hash_equals`, IP whitelist, Authorization header strip fallback
    (`?token=` query), `api_key_last_used` direct-DB touch (config cache flush YOK).
  - `Model\ScopeGuard` — read default ON / write default OFF (prompt-injection guard).
  - `Model\OrderPreview` — Magento cache + JSON serializer, defensive `_preview_id` replay check.
  - `Controller\Api\Index` `CsrfAwareActionInterface` ile CSRF bypass (machine-to-machine, Bearer korumalı).
- **Audit log** — declarative `etc/db_schema.xml` (`dowaba_ai_audit`), lazy 1/500 retention purge (default 30 gün),
  24h per-function stats, admin "Audit Log" paneli.
- **Admin setup wizard** — top-level "Dowaba AI" menü → 5 adım (API key üret / manifest URL / aktivasyon+scope /
  IP whitelist / bağlantı testi) + audit log feed. AJAX: regeneratekey, testconnection, auditlog.
  Config `core_config_data` (`dowaba_ai/*`), Writer + tek config-cache flush.
- **Paketleme** — `build.sh` (PHP syntax + xmllint + version sync) → app/code zip + Marketplace package zip.
- **i18n** — tr_TR + en_US.
- **Docker** — bitnami Magento + MariaDB + OpenSearch lokal test ortamı.

### Notes

- **OpenCart paritesi:** 10 function, Bearer/scope/audit/preview pattern, manifest formatı birebir korundu.
  Magento-spesifik: ScopeConfig/Writer config storage, declarative schema, Quote-tabanlı guest order,
  clean URL routing (`/dowaba_ai/api` — OpenCart'taki dot/pipe routing bug'ı yok).
- **Bilinen sınırlar (v0.1):** order_confirm yalnızca **simple product** destekler (configurable/bundle Faz 2);
  shipping flat-rate, payment Check/Money Order (offline) — gerçek ödeme entegrasyonu Faz 2; cart_recover link
  üretir ama auto-login token henüz DB'ye bind edilmedi.
- **Yerel doğrulama:** PHP 8.3 syntax OK (tüm dosyalar), xmllint XML well-formed, manifest strict JSON Schema
  (array→items, object→properties) + mgm_ prefix + token fallback otomatik test PASS.
- **Canlı e2e doğrulama (2026-05-30, Magento 2.4.7 + PHP 8.3 + MySQL 8 + OpenSearch 2.12, GitHub-source kurulum):**
  `setup:upgrade` `dowaba_ai_audit` tablosunu declarative schema ile oluşturdu (kolonlar birebir). Manifest 200
  (10 fn, dinamik base_url, strict schema). Auth 401 (token yok) / 400 (geçersiz action). product_search/detail/
  stock/compare 200 gerçek ürünlerle. **Write flow: order_preview → order_confirm → gerçek sipariş `#000000001`
  (Quote API, checkmo+flatrate), stok 25→23 düştü.** Replay → 410, order_status yanlış email → 404 (IDOR guard).
  Audit log her çağrıyı DB'ye yazdı. `test/e2e.sh` **7/0 PASS**. Doğrulanan davranış: product_search mağazanın
  `show_out_of_stock` ayarına saygı duyar (storefront ile tutarlı). Runbook: [docker/README.md](./docker/README.md).
  Sıradaki: Dowaba prod → Bundle Import canlı entegrasyon (Cloudflare tunnel).

## [Unreleased]

### Added
- **product_detail çoklu-görsel galeri (OpenCart paritesi)** — `Dispatcher::shapeGallery()`
  `getMediaGalleryImages()` ile ürünün tüm görsellerini `gallery[]` (`{thumb, image}`) + `gallery_count`
  olarak döner. `getUrl()` öncelikli, boşsa `file` path'inden media base ile kurar. Best-effort try/catch
  (galeri okunamazsa detay yine döner).

### ✅ Test — 2026-06-01 (CANLI, galeri doğrulandı)
- Magento 2.4.7 (localhost:8087) — görselli test ürünü seed edildi (`dowaba-test-phone`: 1 kapak + 2 galeri;
  `addImageToMediaGallery` → media_gallery 3 kayıt + image/small_image/thumbnail rolleri).
- **product_search** → kapak `thumb`/`image` döndü ✓ (`/media/catalog/product/d/w/dwb_cover_1.jpg`).
- **product_detail** → `gallery_count: 3` + 3 görsel URL'i ✓ (önceki "Test bekliyor" maddesi **doğrulandı**).
- **10/10 fonksiyon** doğru: read'ler OK, product_compare 2-3 id validasyonu, order/customer IDOR guard
  "not found", write'lar scope-guard ile bloklu (default-deny). (order_preview→confirm gerçek sipariş akışı
  `[0.1.0]` notunda zaten `#000000001` ile kanıtlanmıştı.)
- OpenCart'taki Proxy/`method_exists` galeri bug'ı Magento'da **YOK** — `getMediaGalleryImages()` doğrudan
  ürün nesnesinden çağrılır (proxy katmanı yok). v0.1.1 release'e hazır.

### Planlanan
- order_confirm configurable/bundle product desteği
- Gerçek shipping/payment method seçimi (admin config)
- Görsel resize (thumb 200×200 / image 600×600 — şu an orijinal boy)
- cart_recover auto-login token DB bind
- Marketplace submission (Adobe Commerce Marketplace)
