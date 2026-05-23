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
