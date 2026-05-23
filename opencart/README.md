# Dowaba AI Integration for OpenCart

OpenCart 4.x mağazalarını Dowaba AI'ya bağlar. Müşteriler WhatsApp / Instagram / Mail / Voice kanallarından doğal dilde ürün arayabilir, karşılaştırabilir, sipariş verebilir.

> **Durum:** v0.1.0 geliştirme. OpenCart 4.x destekli. OpenCart 3.x v0.2'de gelecek.

## Özellikler

- 🔍 **Ürün arama** — "iPhone modelleriniz neler?"
- 📦 **Ürün detayı** — "Bu ürünün özellikleri?"
- ⚖️ **Ürün karşılaştırma** — "X ve Y arasında fark nedir?"
- 📊 **Stok kontrolü** — "Stokta var mı?"
- 🗂️ **Kategori listesi** — "Hangi ürün gruplarınız var?"
- 📋 **Sipariş durumu** — "Siparişim nerede?"
- 👤 **Müşteri sorgu** — "Geçmiş siparişlerim?"
- 🛒 **Sepet hatırlatma** — Re-engagement
- ✅ **Sipariş oluşturma (onaylı)** — AI özet gösterir → müşteri onaylar → sipariş açılır

## Kurulum

### Mağaza tarafı (OpenCart admin)

1. [Releases](https://github.com/rdtvaacar/dowaba-plugins/releases) sayfasından son `dowaba-opencart-X.Y.Z.ocmod.zip` indir
2. OpenCart admin → **Extensions → Installer** → yükle
3. **Extensions → Modules → Dowaba AI** → Install → Edit
4. Setup wizard'ı tamamla (API key üret + IP whitelist + scope toggle + bağlantı testi)
5. Wizard ekranında verilen **Manifest URL**'i kopyala

### Dowaba paneli

1. Dowaba paneline gir → **Siteler** → [siteni seç] → **Entegrasyonlar**
2. **Bundle Import** → yapıştır:
   - Manifest URL: kopyaladığın URL
   - API Key: plugin admin'inde üretilen `opc_...` token
3. **İçe Aktar** → 9 function otomatik aktif gelir (`auto_activate` flag sayesinde)

### Test

WhatsApp/IG'den siteye yazan müşteriye sor: "iPhone 15 var mı?" — AI mağazandan canlı çekip cevaplamalı.

## Yapı

```
opencart/
├─ src/                          # OCMOD content
│  ├─ install.json               # OC4 manifest
│  └─ upload/
│     ├─ admin/
│     │  ├─ controller/extension/module/dowaba.php
│     │  ├─ model/extension/module/dowaba.php
│     │  ├─ view/template/extension/module/dowaba.twig
│     │  └─ language/{en-gb,tr-tr}/
│     ├─ catalog/controller/extension/dowaba/
│     │  ├─ manifest.php         # .well-known/dowaba-bundle.json
│     │  └─ api.php              # 9 REST endpoint
│     └─ system/library/dowaba/
│        ├─ Auth.php             # Bearer + IP whitelist
│        ├─ ScopeGuard.php       # read/write toggle
│        ├─ AuditLogger.php      # dowaba_audit table
│        └─ OrderPreview.php     # 5dk TTL preview cache
├─ docker/                       # lokal test
├─ test/                         # e2e + phpunit
└─ build.sh                      # → dist/*.ocmod.zip
```

## Güvenlik

- **Bearer auth**: Her istek `Authorization: Bearer opc_xxx` header'ı taşır
- **IP whitelist**: Opsiyonel, virgüllü liste (Dowaba prod IP'leri: `178.105.68.170, 49.13.120.112`)
- **Scope guard**: `write` toggle default kapalı — sipariş oluşturma için açılır (AI prompt injection koruması)
- **Order preview**: Sipariş oluşturulmadan önce müşteri onayı zorunlu (`opc_order_preview` → `opc_order_confirm`)
- **Audit log**: Her gelen istek `dowaba_audit` tablosuna yazılır (30 gün retention)

## Geliştirme

```bash
# Lokal docker
cd docker/
docker compose up -d
# OpenCart: http://localhost:8080  | admin: http://localhost:8080/admin (admin/admin123)
# Mailpit:  http://localhost:8025

# Plugin upload (manuel):
# /admin → Extensions → Installer → ../dist/dowaba-opencart-0.1.0.ocmod.zip

# Build
bash build.sh

# Test
bash test/e2e.sh
```

## Yol haritası

- **v0.1.0** — OpenCart 4.x, 9 function, bearer auth, scope guard, audit log
- **v0.2.0** — HMAC-SHA256 auth, health check cron, OpenCart 3.x dual support, "Dowaba Mağaza Ekle" panel preset
- **v1.0.0** — OpenCart Marketplace başvurusu, multi-mağaza tek dowaba site bağlantısı

## Lisans

[MIT](./LICENSE)
