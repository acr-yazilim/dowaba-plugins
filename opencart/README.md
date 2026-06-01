# Dowaba AI for OpenCart

> 🇹🇷 **Türkçe** | [🇬🇧 English below](#english)

OpenCart mağazalarını **Dowaba AI**'ya bağlar. Müşteriler WhatsApp / Instagram / Mail / Voice (telefon) kanallarından doğal dilde ürün arar, karşılaştırır, sipariş verir. 7/24 AI asistan, Türkçe + 30+ dil.

[![OpenCart Marketplace](https://img.shields.io/badge/OpenCart_Marketplace-Live-success?logo=opencart)](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534)
[![Latest Release](https://img.shields.io/badge/latest-v0.2.22-blue)](https://github.com/acr-yazilim/dowaba-plugins/releases/tag/opencart-v0.2.22)
[![OpenCart 3.x](https://img.shields.io/badge/OpenCart-3.0.3.x-success)](https://www.opencart.com/)
[![OpenCart 4.x](https://img.shields.io/badge/OpenCart-4.0.x-success)](https://www.opencart.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./LICENSE)

## 📥 Direkt İndir

Mağaza versiyonuna göre:

- **OpenCart 4.x:** ⬇️ [dowaba_ai.ocmod.zip](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba_ai.ocmod.zip) — ⚠️ **dosya adını DEĞİŞTİRME** (`dowaba_ai.ocmod.zip` olduğu gibi kalsın). OC4 zip ADINI `code` olarak kullanır; rename install'ı bozar.
- **OpenCart 3.x:** ⬇️ [dowaba-opencart-oc3-0.2.22.ocmod.zip](https://github.com/acr-yazilim/dowaba-plugins/releases/download/opencart-v0.2.22/dowaba-opencart-oc3-0.2.22.ocmod.zip)

> OpenCart Marketplace üzerinden de kurabilirsin (otomatik update için tercih edilir): [📦 Marketplace](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534)
> Tüm sürümler: [GitHub Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)

📦 **[Install from OpenCart Marketplace →](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534)**

---

## 🎯 Ne yapar?

```
Müşteri WhatsApp'tan: "iPhone 15 var mı?"
   ↓
Dowaba AI → OpenCart'tan stok+fiyat çeker → "Evet, 64.999 TL stokta ✅"
   ↓
Müşteri: "Sipariş etmek istiyorum"
   ↓
AI özet gösterir → "Onaylıyor musun?" → Müşteri "Evet"
   ↓
✅ Sipariş #12345 oluştu, ödeme link'i gönderildi
```

## ✨ Özellikler

| Function | Ne yapar? |
|---|---|
| 🔍 `product_search` | Ad/SKU ile ürün ara |
| 📦 `product_detail` | Tam ürün bilgisi |
| ⚖️ `product_compare` | 2-3 ürünü yan yana karşılaştır |
| 📊 `stock_check` | Hızlı stok kontrolü |
| 🗂️ `category_list` | Kategori ağacı |
| 📋 `order_status` | Sipariş takibi (email match) |
| 👤 `customer_lookup` | Müşteri profili (KVKK uyumlu) |
| 🛒 `cart_recover` | Sepet hatırlatma link'i |
| ✅ `order_preview` | Sipariş özeti (müşteri onayı öncesi) |
| ✅ `order_confirm` | Sipariş oluştur (onaylı, replay korumalı) |

## 🚀 Kurulum (5 dakika)

### 1. Plugin yükle

[Releases](https://github.com/acr-yazilim/dowaba-plugins/releases) sayfasından **OpenCart sürümüne uygun** zip'i indir:

- **OpenCart 4.x**: `dowaba-opencart-oc4-X.Y.Z.ocmod.zip`
- **OpenCart 3.x**: `dowaba-opencart-oc3-X.Y.Z.ocmod.zip`

OpenCart admin → **Extensions → Installer** → upload.

### 2. Modülü aktive et

Admin → **Extensions → Modules → Dowaba AI** → **Install** → **Edit**.

### 3. Setup wizard

5 adımlı wizard'ı tamamla:
1. **API Key Üret** — `opc_xxxxx...` (kopyala, bir kez gösterilir)
2. **Manifest URL** — kopyala (`https://mağazan.com/index.php?route=extension/dowaba_ai/manifest`)
3. **IP Whitelist** (opsiyonel) — `178.105.68.170, 49.13.120.112` (Dowaba prod IP)
4. **Scope toggle** — `read` (varsayılan açık), `write` (sipariş için açacaksan)
5. **Bağlantı testi** — yeşil tik ✅

### 4. Dowaba paneline bağla

[dowaba.com](https://dowaba.com) → Siteler → [siten] → **Entegrasyonlar → Bundle Import**:
- Manifest URL yapıştır
- API Key yapıştır
- **İçe Aktar** → 10 function otomatik aktif

### 5. Test

WhatsApp Business hattından müşteri olarak yazın: **"iPhone modelleriniz neler?"** — AI mağazandan canlı çekip cevap vermeli.

## 🛡️ Güvenlik

| Katman | Detay |
|---|---|
| **Bearer auth** | `Authorization: Bearer opc_xxxxx` (sha256 hash karşılaştırma, plain DB'de YOK) |
| **IP whitelist** | Opsiyonel, virgüllü liste — Dowaba'nın 2 prod IP'si önerilir |
| **Scope guard** | `read` default ON, `write` default OFF — sipariş oluşturma için bilinçli açılır |
| **Order preview** | Sipariş yaratmadan önce müşteri özet onayı zorunlu (one-shot consume, replay korumalı) |
| **Audit log** | Her gelen API isteği `dowaba_audit` tablosuna kayıt, 30 gün retention |
| **SSRF + injection guard** | Dowaba HttpHandler URL whitelist + parameter escape |

## 📊 OpenCart sürüm uyumluluğu

| Versiyon | OpenCart 3.x | OpenCart 4.x | PHP |
|---|---|---|---|
| v0.1.x | ❌ | ✅ | 8.0+ |
| **v0.2.x (current)** | **✅** | **✅** | **8.0+** |

**Test edildiği ortamlar:**
- OpenCart 3.0.3.9 + PHP 8.2 + MariaDB 11
- OpenCart 4.0.2.3 + PHP 8.2 + MariaDB 11

## 🐳 Geliştirme

```bash
# Lokal Docker (her iki OC sürümü paralel)
cd docker/
docker compose up -d

# OC4: http://localhost:8080
# OC3: http://localhost:8081
# Mailpit: http://localhost:8025

# Build (dual paket)
bash build.sh
# → dist/dowaba-opencart-oc3-X.Y.Z.ocmod.zip
# → dist/dowaba-opencart-oc4-X.Y.Z.ocmod.zip

# E2E test
bash test/e2e.sh
```

## 📚 Dokümantasyon

- 📋 [CHANGELOG](./CHANGELOG.md) — Versiyon geçmişi
- 🏪 [Marketplace Listing](./marketing/MARKETPLACE_LISTING.md) — Resmi pazar yeri başvurusu için
- 📸 [Screenshots rehberi](./marketing/SCREENSHOTS.md) — Görsel hazırlama
- 🔒 [Privacy Policy](./marketing/PRIVACY.md) — KVKK + GDPR uyumluluk

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/acr-yazilim/dowaba-plugins/issues)
- 📖 [Dowaba dokümantasyonu](https://dowaba.com/docs)

## 📜 Lisans

[MIT](./LICENSE) — Aydın Acar (Dowaba) © 2026

---

<a name="english"></a>

## 🇬🇧 English

**Dowaba AI for OpenCart** connects your OpenCart store to Dowaba AI. Customers chat with an AI assistant via WhatsApp / Instagram DM / Email / Voice (phone) — searches products, compares, and places orders in natural language. 24/7 multilingual (TR + 30 languages).

### Features

10 AI functions: product search/detail/compare, stock check, category list, order status, customer lookup, cart recovery, **2-step confirmed order create** (preview → customer "yes" → confirm).

### Quick install

1. Download zip from [Releases](https://github.com/acr-yazilim/dowaba-plugins/releases) (OC3 or OC4)
2. Admin → **Extensions → Installer** → upload
3. **Modules → Dowaba AI → Install → Edit** → complete 5-step wizard
4. Copy **Manifest URL** + **API Key**
5. [dowaba.com](https://dowaba.com) panel → **Bundle Import** → paste both → done

### Security

Bearer token (sha256 hashed), optional IP whitelist, scope guard (read/write toggles), one-shot order preview cache (5 min TTL, replay-protected), audit log (30-day retention), SSRF guard.

### Compatibility

| Version | OC 3.0.3.x | OC 4.0.x | PHP |
|---|---|---|---|
| **v0.2.x** | ✅ | ✅ | 8.0+ |

### Support

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/acr-yazilim/dowaba-plugins/issues)

[MIT License](./LICENSE).
