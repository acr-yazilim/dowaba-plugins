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
| **PrestaShop 1.7+ / 8.x** | **v0.2.5** | [⬇️ ZIP indir](https://github.com/acr-yazilim/dowaba-plugins/releases/download/prestashop-v0.2.5/dowaba-ai-prestashop-0.2.5.zip) | ⏳ addons.prestashop.com submission |
| **OpenCart 4.x** | **v0.2.3** | [⬇️ OC4 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.3/dowaba-opencart-oc4-0.2.3.ocmod.zip) | [📦 OpenCart Marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534) |
| **OpenCart 3.x** | **v0.2.3** | [⬇️ OC3 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.3/dowaba-opencart-oc3-0.2.3.ocmod.zip) | [📦 OpenCart Marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534) (aynı listing) |
| **WooCommerce** | **v0.2.0** | [⬇️ ZIP indir](https://github.com/acr-yazilim/dowaba-plugins/releases/download/woocommerce-v0.2.0/dowaba-ai-0.2.0.zip) | ⏳ wordpress.org submission |
| **Shopify** | — | OAuth-based (download yok) | ⏳ App Store Billing API resubmit |
| **İkas** | — | OAuth-based (download yok) | ⏳ Partner App onayı |

> 📋 **Tüm release'ler ve eski sürümler:** [GitHub Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)

## 🚀 Hızlı kurulum

### PrestaShop
1. [⬇️ PrestaShop ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/prestashop-v0.2.5/dowaba-ai-prestashop-0.2.5.zip) indir
2. PrestaShop BO → Modules → Module Manager → Upload a module → ZIP'i sürükle
3. **Configure** → API key + Manifest URL üret → DoWaba paneline Bundle Import yap

### OpenCart
1. Mağazanın versiyonuna göre ZIP indir:
   - [⬇️ OC4 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.3/dowaba-opencart-oc4-0.2.3.ocmod.zip)
   - [⬇️ OC3 ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.3/dowaba-opencart-oc3-0.2.3.ocmod.zip)
2. Admin → Extensions → Installer → Upload → ZIP'i yükle → Refresh Modifications
3. 5-step wizard → DoWaba Bundle Import

### WooCommerce
1. [⬇️ WooCommerce ZIP](https://github.com/acr-yazilim/dowaba-plugins/releases/download/woocommerce-v0.2.0/dowaba-ai-0.2.0.zip) indir
2. WP admin → Plugins → Add New → Upload Plugin → ZIP'i yükle → Activate
3. **DoWaba AI** menü → 5-step wizard → Bundle Import

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
- [opencart/README.md](./opencart/README.md) — OpenCart-spesifik
- [woocommerce/README.md](./woocommerce/README.md) — WooCommerce-spesifik

## 💰 Pricing

Plugin'ler **100% free** (MIT License). DoWaba SaaS:
- **Free**: 100 messages/month
- **Starter**: $19/month — 1,000 messages
- **Pro**: $49/month — 10,000 messages
- **Enterprise**: Custom

https://dowaba.com/pricing

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/rdtvaacar/dowaba-plugins/issues)
- 📚 [docs.dowaba.com](https://dowaba.com/docs)

## 📜 Lisans

[MIT](./opencart/LICENSE) — Aydın Acar (DoWaba) © 2026
