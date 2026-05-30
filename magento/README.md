# Dowaba AI for Magento 2

> 🇹🇷 **Türkçe** | [🇬🇧 English below](#english)

Magento 2 (Adobe Commerce / Open Source) mağazalarını **Dowaba AI**'ya bağlar. Müşteriler WhatsApp / Instagram / Mail / Voice (telefon) kanallarından doğal dilde ürün arar, karşılaştırır, sipariş verir. 7/24 AI asistan, Türkçe + 30+ dil.

[![Latest Release](https://img.shields.io/badge/latest-v0.1.0-blue)](https://github.com/acr-yazilim/dowaba-plugins/releases)
[![Magento 2.4.x](https://img.shields.io/badge/Magento-2.4.x-success)](https://magento.com/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%E2%80%938.3-777bb3)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./LICENSE)

> **Modül adı:** `Dowaba_AiConnector` · **Composer:** `dowaba/module-ai-connector`

---

## 🎯 Ne yapar?

```
Müşteri WhatsApp'tan: "iPhone 15 var mı?"
   ↓
Dowaba AI → Magento'dan stok+fiyat çeker → "Evet, 64.999 TL stokta ✅"
   ↓
Müşteri: "Sipariş etmek istiyorum"
   ↓
AI özet gösterir → "Onaylıyor musun?" → Müşteri "Evet"
   ↓
✅ Sipariş #000000123 oluştu (guest order, Check/Money Order)
```

## ✨ 10 AI Function

| Function | Scope | Ne yapar? |
|---|---|---|
| 🔍 `mgm_product_search` | read | Ad/SKU ile ürün ara |
| 📦 `mgm_product_detail` | read | Tam ürün bilgisi + özellikler |
| ⚖️ `mgm_product_compare` | read | 2-3 ürünü yan yana karşılaştır |
| 📊 `mgm_stock_check` | read | Hızlı stok kontrolü (StockRegistry) |
| 🗂️ `mgm_category_list` | read | Kategori ağacı |
| 📋 `mgm_order_status` | read | Sipariş takibi (increment_id + email match) |
| 👤 `mgm_customer_lookup` | read | Müşteri profili + son 5 sipariş (KVKK) |
| 🛒 `mgm_cart_recover` | read | Sepet hatırlatma link'i |
| ✅ `mgm_order_preview` | **write** | Sipariş özeti (müşteri onayı öncesi) |
| ✅ `mgm_order_confirm` | **write** | Sipariş oluştur (Quote → guest order, replay korumalı) |

## 🚀 Kurulum

### 1. Modülü yükle

**A) Composer (önerilen, yayınlandığında):**
```bash
composer require dowaba/module-ai-connector
```

**B) Manuel (zip):** [Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)'ten `dowaba-magento-aiconnector-X.Y.Z.zip` indir, Magento kök dizininde aç:
```bash
unzip dowaba-magento-aiconnector-X.Y.Z.zip   # → app/code/Dowaba/AiConnector/...
```

### 2. Etkinleştir

```bash
bin/magento module:enable Dowaba_AiConnector
bin/magento setup:upgrade
bin/magento setup:di:compile        # production mode'da
bin/magento cache:flush
```

`setup:upgrade` `dowaba_ai_audit` tablosunu (declarative schema) otomatik oluşturur.

### 3. Setup wizard

Admin → **Dowaba AI → Setup & Settings** → 5 adım:
1. **API Key Üret** — `mgm_xxxxx...` (kopyala, bir kez gösterilir)
2. **Manifest URL** — kopyala (`https://mağazan.com/dowaba_ai/manifest`)
3. **Etkinleştirme & İzinler** — Modül aç + `read` (varsayılan açık) / `write` (sipariş için)
4. **Güvenlik** (opsiyonel) — IP whitelist (`178.105.68.170, 49.13.120.112` Dowaba prod)
5. **Bağlantı testi** — yeşil tik ✅

### 4. Dowaba paneline bağla

[dowaba.com](https://dowaba.com) → Siteler → [siten] → **Entegrasyonlar → Bundle Import**:
- Manifest URL yapıştır + API Key yapıştır → **İçe Aktar** → 10 function otomatik aktif

### 5. Test

WhatsApp Business hattından müşteri olarak yazın: **"iPhone modelleriniz neler?"** — AI mağazandan canlı çekip cevap vermeli.

## 🛡️ Güvenlik

| Katman | Detay |
|---|---|
| **Bearer auth** | `Authorization: Bearer mgm_xxxxx` (sha256 hash karşılaştırma, plain DB'de YOK) |
| **Header fallback** | Authorization strip eden sunucularda `?token=` query fallback (opc/mgm format + hash yine doğrulanır) |
| **IP whitelist** | Opsiyonel, virgüllü liste — Dowaba'nın 2 prod IP'si önerilir |
| **Scope guard** | `read` default ON, `write` default OFF — sipariş oluşturma bilinçli açılır |
| **Order preview** | Sipariş yaratmadan önce müşteri özet onayı zorunlu (one-shot consume, replay korumalı) |
| **Audit log** | Her gelen API isteği `dowaba_ai_audit` tablosuna kayıt, 30 gün retention (lazy purge) |
| **CSRF** | API endpoint'i Bearer auth ile korunur (CSRF bypass — machine-to-machine) |

## 📊 Uyumluluk

| | Magento 2.4.4 – 2.4.7+ | PHP |
|---|---|---|
| **v0.1.x** | ✅ Open Source & Adobe Commerce | 8.1 / 8.2 / 8.3 |

## 🐳 Geliştirme

```bash
# Build (iki paket: app/code zip + Marketplace package)
bash build.sh
# → dist/dowaba-magento-aiconnector-X.Y.Z.zip   (Magento kökünde aç)
# → dist/Dowaba_AiConnector-X.Y.Z.zip           (Marketplace upload)

# Lokal test ortamı (bitnami Magento + MariaDB + OpenSearch)
cd docker && docker compose up -d    # ilk boot ~5-10 dk
```

> Magento lokal kurulumu ağırdır; detay için [docker/README.md](./docker/README.md).

## 📚 Dokümantasyon

- 📋 [CHANGELOG](./CHANGELOG.md)
- 🏪 [Marketplace Listing](./marketing/MARKETPLACE_LISTING.md) (TR) · [EN](./marketing/MARKETPLACE_LISTING_EN.md)
- 🔒 [Privacy Policy](./marketing/PRIVACY.md) — KVKK + GDPR
- ✅ [Submission Checklist](./marketing/SUBMISSION_CHECKLIST.md)
- 🧭 Plugin mimarisi: [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md)

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/acr-yazilim/dowaba-plugins/issues)

## 📜 Lisans

[MIT](./LICENSE) — Aydın Acar (Dowaba) © 2026

---

<a name="english"></a>

## 🇬🇧 English

**Dowaba AI for Magento 2** connects your Magento store (Open Source or Adobe Commerce) to Dowaba AI. Customers chat with an AI assistant via WhatsApp / Instagram DM / Email / Voice — searching products, comparing, and placing orders in natural language. 24/7 multilingual (TR + 30 languages).

### Features

10 AI functions: product search/detail/compare, stock check, category list, order status, customer lookup, cart recovery, **2-step confirmed order create** (preview → customer "yes" → confirm).

### Quick install

```bash
composer require dowaba/module-ai-connector     # or unzip the app/code package at Magento root
bin/magento module:enable Dowaba_AiConnector
bin/magento setup:upgrade && bin/magento cache:flush
```

Then Admin → **Dowaba AI → Setup & Settings** → complete the 5-step wizard → copy **Manifest URL** + **API Key** → [dowaba.com](https://dowaba.com) panel → **Bundle Import** → paste both → done.

### Security

Bearer token (sha256 hashed), optional IP whitelist, scope guard (read/write toggles), one-shot order preview cache (5 min TTL, replay-protected), audit log (30-day retention), CSRF-exempt machine-to-machine API.

### Compatibility

| Version | Magento | PHP |
|---|---|---|
| **v0.1.x** | 2.4.4 – 2.4.7+ | 8.1 / 8.2 / 8.3 |

### Support

- 📧 [dowaba.com/destek](https://dowaba.com/destek) · 💬 [GitHub Issues](https://github.com/acr-yazilim/dowaba-plugins/issues)

[MIT License](./LICENSE).
