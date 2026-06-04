# DoWaba Plugins

[DoWaba](https://dowaba.com) için resmi e-commerce platform entegrasyonları. Her plugin, kendi platformunun ürün/sipariş/müşteri verilerini DoWaba AI'ya **HTTP function** olarak sunar.

[![OpenCart Marketplace](https://img.shields.io/badge/OpenCart_Marketplace-Live-success?logo=opencart)](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534)
[![WordPress.org](https://img.shields.io/badge/WordPress.org-Submitted-yellow?logo=wordpress)](https://wordpress.org/plugins/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./opencart/LICENSE)

---

## 🎯 Ne Yapıyor?

Müşteriler **WhatsApp**, **Instagram DM**, **TikTok** üzerinden yazıyor. DoWaba AI mağazandan **canlı** veri çekip cevaplıyor, ürün karşılaştırıyor, müşteri onayıyla sipariş açıyor.

```
Müşteri Instagram'dan: "iPhone 15 Pro var mı?"
   ↓
DoWaba AI → mağazadan canlı → "Evet, 64.999 TL stokta"
   ↓
Müşteri: "Sipariş et" → AI özet → "Onaylıyor musun?" → "Evet"
   ↓
✅ Mağaza siparişi #12345 oluştu
```

## 📦 Plugins — Direkt Download

| Platform | En Son Versiyon | ZIP Download | Marketplace |
|---|---|---|---|
| **PrestaShop 1.7+ / 8.x / 9.x** | **v0.2.9** | — (yalnızca Addons üzerinden) | ✅ [PrestaShop Addons](https://addons.prestashop.com/en/customer-service/97927-dowaba-ai-sell-on-whatsapp-instagram-tiktok.html) |
| **OpenCart 4.x** | **v0.2.22** | [⬇️ OC4 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba_ai.ocmod.zip) | [📦 OpenCart Marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534) |
| **OpenCart 3.x** | **v0.2.22** | [⬇️ OC3 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba-opencart-oc3-0.2.22.ocmod.zip) | [📦 OpenCart Marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534) (aynı listing) |
| **WooCommerce** | **v0.3.1** | [⬇️ ZIP indir](https://github.com/acr-yazilim/dowaba-plugins/releases/download/woocommerce-v0.3.1/dowaba-ai-0.3.1.zip) | ⏳ wordpress.org submission |
| **Magento 2.4.x** | **v0.1.1** | [⬇️ ZIP indir](https://github.com/acr-yazilim/dowaba-plugins/releases/download/magento-v0.1.1/dowaba-magento-aiconnector-0.1.1.zip) | ⏳ Adobe Commerce Marketplace submission |
| **Shopify** | — | OAuth-based (download yok) | ⏳ App Store Billing API resubmit |
| **İkas** | — | OAuth-based (download yok) | ⏳ Partner App onayı |

> 📋 **Tüm release'ler ve eski sürümler:** [GitHub Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)

## 🚀 Hızlı kurulum

### PrestaShop
1. **[🛒 PrestaShop Addons'tan edin](https://addons.prestashop.com/en/customer-service/97927-dowaba-ai-sell-on-whatsapp-instagram-tiktok.html)** (resmi dağıtım — ZIP yalnızca Addons üzerinden verilir)
2. PrestaShop BO → Modules → Module Manager → Upload a module → ZIP'i sürükle
3. **Configure** → API key + Manifest URL üret → DoWaba paneline Bundle Import yap

### OpenCart
1. Mağazanın versiyonuna göre ZIP indir:
   - [⬇️ OC4 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba_ai.ocmod.zip) — **adı `dowaba_ai.ocmod.zip` kalmalı, değiştirme** (OC4 zip adından `code` okuyor)
   - [⬇️ OC3 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba-opencart-oc3-0.2.22.ocmod.zip)
2. Admin → Extensions → Installer → Upload → ZIP'i yükle → Install
3. Extensions → Extensions → Modules → DoWaba AI → Install (yeşil +) → Edit
4. **"Yeni API Key Üret"** → `opc_xxxxx...` + Manifest URL kopyala → DoWaba Bundle Import

### WooCommerce
1. [⬇️ WooCommerce ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/woocommerce-v0.3.1/dowaba-ai-0.3.1.zip) indir
2. WP admin → Plugins → Add New → Upload Plugin → ZIP'i yükle → Activate
3. **DoWaba AI** menü → 5-step wizard → Bundle Import

### Magento 2
1. `composer require dowaba/module-ai-connector` **veya** [⬇️ Magento ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/magento-v0.1.1/dowaba-magento-aiconnector-0.1.1.zip)'i Magento kökünde aç
2. `bin/magento module:enable Dowaba_AiConnector && bin/magento setup:upgrade && bin/magento cache:flush`
3. Admin → **Dowaba AI → Setup & Settings** → 5-step wizard → Bundle Import

Detaylı kurulum: her plugin'in kendi README'sinde.

## 📁 Repo yapısı

```
dowaba-plugins/
├─ opencart/              # OpenCart 3.x + 4.x dual support
│  ├─ src/{oc3,oc4}/      # Platform-spesifik kaynak
│  ├─ docker/             # Lokal test
│  ├─ marketing/          # Banner, screenshot guide, listing copy
│  ├─ CHANGELOG.md
│  └─ README.md
├─ woocommerce/           # WordPress + WooCommerce
│  ├─ dowaba-ai.php       # Plugin main file
│  ├─ includes/           # Auth, ScopeGuard, AuditLogger, OrderPreview, Manifest, Api, Admin
│  ├─ admin/views/        # Settings page
│  ├─ docker/             # Lokal WP+WC test
│  └─ readme.txt          # wp.org format
├─ magento/               # Magento 2.4.x (Dowaba_AiConnector)
│  ├─ src/Dowaba/AiConnector/   # Modül kaynağı (etc/, Controller/, Model/, Block/, view/, i18n/)
│  ├─ docker/             # bitnami Magento + MariaDB + OpenSearch
│  ├─ marketing/          # Listing, privacy, submission checklist
│  ├─ build.sh            # app/code zip + Marketplace package zip
│  ├─ CHANGELOG.md
│  └─ README.md
├─ .github/workflows/     # GH Actions release per platform
├─ LESSONS_LEARNED.md     # 🧠 Retrospective + reusable patterns
└─ README.md              # bu dosya
```

## 🛡️ Güvenlik (her plugin'de)

| Katman | Detay |
|---|---|
| Bearer auth | SHA-256 hashed token, plain key never stored |
| IP whitelist | Opsiyonel — DoWaba prod IP: `178.105.68.170, 49.13.120.112` |
| Scope guard | `read` default ON, `write` default OFF |
| Order confirmation | 2-step preview → "yes" → confirm (replay-protected) |
| Audit log | 30-day retention, viewable in admin |
| Compliance | GDPR + KVKK uyumlu |

## 🌍 Dil Desteği

- **Plugin admin**: 🇬🇧 English + 🇹🇷 Türkçe
- **AI customer chat**: 30+ languages (TR, EN, AR, DE, ES, FR, RU, ...)

## 📚 Documentation

- [LESSONS_LEARNED.md](./LESSONS_LEARNED.md) — Mimari pattern, reusable artifacts, bug catalog
- [PLUGIN_DEV_GUIDE.md](./PLUGIN_DEV_GUIDE.md) — Yeni platform yazma rehberi (tek otorite)
- [opencart/README.md](./opencart/README.md) — OpenCart-spesifik
- [woocommerce/README.md](./woocommerce/README.md) — WooCommerce-spesifik
- [magento/README.md](./magento/README.md) — Magento 2-spesifik

## 💰 Pricing

Plugins are **100% free** (MIT License). DoWaba SaaS:
- **Free trial** — 50 messages to get started (with your own AI key)
- **Starter** — $4.99/mo ($49.99/yr) · 1,000 messages/mo · 1 store
- **Pro** — $12.49/mo ($124.99/yr) · 5,000 messages/mo · 3 stores
- **Business / Reseller** — from $99.99/yr per store · 5,000 messages/store · white-label, sub-accounts, volume discounts (up to 25%)

Prices in USD; customers in Türkiye are billed in ₺ (Starter ₺199, Pro ₺499 / month). See https://dowaba.com/pricing

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/acr-yazilim/dowaba-plugins/issues)
- 📚 [docs.dowaba.com](https://dowaba.com/docs)

## 📜 Lisans

[MIT](./opencart/LICENSE) — Aydın Acar (DoWaba) © 2026
