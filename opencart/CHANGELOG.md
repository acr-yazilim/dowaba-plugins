# Changelog

Tüm önemli değişiklikler bu dosyada listelenir. [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) formatı, [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kuralları.

## [Unreleased]

### Added — v0.1.0 hedefi
- OpenCart 4.x OCMOD paket yapısı (`install.json` + `upload/`)
- 5-adımlı admin setup wizard (API key üret + manifest URL + IP whitelist + scope toggle + bağlantı testi)
- `.well-known/dowaba-bundle.json` manifest endpoint
- 8 read-only function: `product_search`, `product_detail`, `product_compare`, `stock_check`, `category_list`, `order_status`, `customer_lookup`, `cart_recover`
- 2-adımlı sipariş oluşturma: `order_preview` (preview cache 5dk TTL) → `order_confirm` (müşteri onayı sonrası)
- Bearer auth (sha256 hash karşılaştırma, plain DB'de yok)
- IP whitelist (opsiyonel, virgüllü liste)
- Scope guard (`read` default ON, `write` default OFF)
- `dowaba_audit` tablo + admin "Audit Log" sekmesi (son 100 + filtreleme, 30 gün retention)
- Docker test ortamı (`docker/docker-compose.yml`: OpenCart 4.x + MariaDB 11 + Mailpit)
- e2e smoke test (`test/e2e.sh`)
- PHPUnit unit testler (`test/phpunit/`: AuthTest, ScopeGuardTest, OrderPreviewTest)
- GitHub Actions release pipeline (`tag:opencart-v*` → `.ocmod.zip` artifact)

## [0.1.1] - 2026-05-23

### Fixed
- **KRİTİK** `OrderPreview::peek()` OpenCart File cache `get()` cache miss'te `[]` (boş array) döndürür, `null` değil. Replay protection bozuktu — aynı preview_id ile birden fazla order yaratılabiliyordu. Fix: `empty($value) || !isset($value['_preview_id'])` ek check.
- **KRİTİK** `Api::respond()` HTTP status code OC4 Response sınıfı tarafından yutuluyordu. Fix: 3 mekanizma birlikte (http_response_code + header() + response->addHeader).
- `Manifest` endpoint `config('config_url')` statik kurulum URL'i döndürüyordu — Cloudflare tunnel / ngrok / reverse proxy ortamlarında Dowaba'nın manifest'i okuduğu URL ile API çağırdığı URL farklı oluyordu (fail). Fix: `resolveBaseUrl()` — X-Forwarded-Host + HTTP_HOST + admin override fallback'leri.
- `orderConfirm()` order create transaction wrap yoktu, exception durumunda partial order DB'de kalabilirdi. Fix: START TRANSACTION / COMMIT / ROLLBACK.
- OC4 4.0.2.3 `model_checkout_order::addOrder()` PHP Warning'leri (master_id, subscription, payment_address_id, shipping_address_id) — bunlar product-level field. Fix: `orderProducts[]` her item'a `master_id` + `subscription` eklendi; order-level'a `payment_address_id` + `shipping_address_id`.
- OC4 totals modeli `'extension' => 'total'` yerine `'extension' => 'opencart'` — opencart extension'ı altındaki Total\SubTotal model'i load edilebilsin.
- `AuditLogger::ensureTable()` defensive — admin install hook tetiklenmediği durumlarda ilk write'ta tablo otomatik oluşur (idempotent CREATE IF NOT EXISTS).

### Changed
- GitHub Actions workflow `opencart/.github/workflows/` → repo root `.github/workflows/release-opencart.yml` (umbrella repo'da workflow tetiklenebilir hale geldi).
- Manifest'e `module_dowaba_ai_manifest_base_url` admin setting override eklendi (rakip CDN ardındaki kurulumlar için).

## [0.1.2] - 2026-05-23

### Fixed
- **KRİTİK** OC4 routing `.` ve `|` notation Dowaba HttpHandler tarafından URL-encode ediliyor (`%2E` / `%7C`) → route resolve fail. Çözüm: `api.php` tek `index()` + `?action=<method>` query dispatch. Whitelisted action map prompt injection koruması.
- **KRİTİK** Manifest `base_url` `?route=...` query string'i Dowaba HttpHandler tarafından DROP ediliyor (Guzzle URL parse + query overwrite). Çözüm: `base_url` SADECE schema+host+path; `route` query_template'e ayrı param olarak konur.
- `Api::readJsonBody()` content-type bağımsız body parse — JSON → $_POST → raw form-encoded fallback chain.

### Added
- Canlı Dowaba prod entegrasyon doğrulandı (Cloudflare tunnel + site_id=57): manifest fetch + auto_activate ✓, opc_product_search canlı çağrı 3 ürün döndü ✓.

### Known issues (v0.1.3)
- Dowaba HttpHandler POST endpoint'lerinde `body_template` substitute sonrası body `[]` (boş) gönderiyor — `body_template` ile substituteTree etkileşiminde regression. Plugin endpoint'leri (`order_preview`, `order_confirm`, `cart_recover`) bu nedenle prod'da Dowaba'dan çağrılamıyor. Lokal `curl` ile çalışıyor. Dowaba tarafında `HttpHandler::substituteTree` + `pruneEmpties` debug gerek.
- `opc_product_compare` array parameter (`product_ids`) Dowaba substitute'da boş geliyor — aynı kök sorun.
