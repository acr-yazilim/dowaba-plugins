# Dowaba Plugins

[Dowaba](https://dowaba.com) için resmi e-commerce platform entegrasyonları. Her plugin, kendi platformunun ürün/sipariş/müşteri verilerini Dowaba AI'ya **HTTP function** olarak sunar.

## Mimari

Dowaba **Bundle Import** sistemi sayesinde bu plugin'ler `.well-known/dowaba-bundle.json` formatında bir manifest yayınlar. Dowaba manifest'i çekip otomatik olarak `SiteConnection` + `FunctionDefinition` kayıtları üretir; AI çalıştırma zamanında plugin'in REST endpoint'lerini çağırır.

```
┌─────────────────┐    1. Müşteri sorar    ┌──────────────┐
│  WhatsApp/IG/   │ ─────────────────────► │  Dowaba AI   │
│  Mail/Voice     │                        └──────┬───────┘
└─────────────────┘                               │
                                                  │ 2. HTTP function call
                                                  ▼
                                          ┌───────────────────┐
                                          │  Plugin REST API  │
                                          │  (OpenCart/Woo/   │
                                          │   Shopify/...)    │
                                          └───────────────────┘
```

## Plugin'ler

| Platform | Durum | Versiyon | Dizin |
|---|---|---|---|
| **OpenCart 4.x** | 🚧 v0.1.0 geliştirme | — | [`opencart/`](./opencart/) |
| WooCommerce | 📋 Planlandı | — | — |
| Shopify | 📋 Planlandı | — | — |
| OpenCart 3.x | 📋 v0.2 (dual support) | — | — |

## Repo yapısı

```
dowaba-plugins/
├─ README.md              # bu dosya
├─ .gitignore
└─ opencart/              # OpenCart plugin (ilk)
   ├─ src/                # OCMOD content
   ├─ docker/             # lokal test ortamı
   ├─ test/               # e2e + phpunit
   ├─ build.sh            # → dist/*.ocmod.zip
   └─ README.md           # OpenCart-spesifik kurulum
```

## Genel kurallar

- Her plugin **ayrı semver** (`v0.1.0`, `v0.2.0`...)
- Tag formatı: `<platform>-vX.Y.Z` (örn `opencart-v0.1.0`)
- Lisans: MIT
- Auth: Plugin kurulumda random bearer token üretir, Dowaba panel'de yapıştırılır → her HTTP function call'da `Authorization: Bearer <token>` header'ı

## Geliştirme

```bash
cd opencart/
docker compose -f docker/docker-compose.yml up -d
# OpenCart admin: http://localhost:8080/admin (admin/admin123)
# Plugin upload: Extensions → Installer → dowaba-opencart-X.Y.Z.ocmod.zip
```

## Lisans

[MIT](./opencart/LICENSE) — her plugin için ayrı dosya (gelecekte WooCommerce GPLv2 vs. farklı lisans gerekebilir).
