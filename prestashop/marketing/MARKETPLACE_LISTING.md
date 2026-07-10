# PrestaShop Addons Listing — Copy-Paste Ready (Türkçe)

Bu dosya **addons.prestashop.com** partner dashboard'unda submission formuna doğrudan kopyalanmak üzere hazırlanmış metinleri içerir.

URL: https://addons.prestashop.com/en/login (Partner login)
Partner program: https://addons.prestashop.com/en/content/8-developers

---

## 📝 Listing — Dowaba AI for PrestaShop 1.7 + 8.x

### Field: **Module Name** (max 64 char)

```
Dowaba AI — WhatsApp + Instagram + TikTok Chatbot
```

### Field: **Technical Name** (zip içindeki klasör adı — değiştirilemez)

```
dowaba_ai
```

### Field: **Author / Developer Name**

```
Aydın Acar (Dowaba)
```

### Field: **Author URL / Website**

```
https://dowaba.com
```

### Field: **License**

```
MIT (Open Source — Free)
```

### Field: **Category** (PrestaShop module taxonomy)

```
Front Office Features → Customer Reassurance → Customer Service
```

(alternatif: **Marketing → Advertising & Marketing → Comparison Site & Feed Management**)

### Field: **Compatibility** (PrestaShop versions)

```
☑ 1.7.7.x
☑ 1.7.8.x
☑ 8.0.x
☑ 8.1.x
☑ 8.2.x
```

### Field: **Module Version**

```
0.1.0
```

### Field: **Tags / Keywords** (max 10)

```
ai chatbot, whatsapp, instagram, tiktok, social commerce, customer service, conversational ai, sales automation, multi channel, gdpr
```

### Field: **Short Description** (max 150 char, marketplace listing kartı için)

```
AI chatbot mağazanı WhatsApp, Instagram DM ve TikTok'a bağlar. Müşteriler sohbet eder, ürün arar, sipariş verir. 5 dakika kurulum, 30+ dil.
```

### Field: **Long Description** (Markdown destekler, ideal 600-1200 kelime)

```markdown
# Dowaba AI — Akıllı Müşteri Temsilcisi 7/24

PrestaShop mağazana bir AI chatbot bağla; müşterilerin **WhatsApp**, **Instagram DM**, **TikTok**, **E-posta** ve **Telefon** üzerinden ulaşıp ürün arayabilsin, karşılaştırabilsin, **gerçek sipariş** verebilsin. Türkçe + 30+ dil. 5 dakikada kurulum.

## 🎯 Ne Yapıyor?

Müşteri WhatsApp'tan yazıyor:
> "iPhone 15 Pro var mı? Fiyatı kaç?"

AI mağazandan **canlı** veri çekip cevaplıyor:
> "iPhone 15 Pro 256 GB stokta var, 64.999 TL. İndirim de var: bu hafta %5 (61.749 TL). Sipariş etmek ister misin?"

Müşteri: "Evet"

AI özet gösteriyor:
> "📦 Sipariş özetin: 1× iPhone 15 Pro — 61.749 TL + Kargo 49 TL = 61.798 TL. Adres: [müşteri adresi]. Onaylıyor musun?"

Müşteri: "Evet"

✅ PrestaShop'ta sipariş açıldı, ödeme link'i gönderildi.

## ✨ 10 AI Function — Kurulumdan Sonra Otomatik Aktif

- 🔍 **Ürün arama** — Ad/SKU/kategori ile arama
- 📦 **Ürün detayı** — Tam bilgi (özellikler, görsel, fiyat, stok)
- ⚖️ **Ürün karşılaştırma** — 2-3 ürünü yan yana, ortak/farklı özellikler
- 📊 **Stok kontrolü** — Hızlı yes/no + adet
- 🗂️ **Kategori listesi** — Mağaza ağacı
- 📋 **Sipariş durumu** — Email match ile KVKK uyumlu sorgu
- 👤 **Müşteri sorgu** — Geçmiş siparişler (sadece doğrulanmış müşteri)
- 🛒 **Sepet hatırlatma** — Re-engagement link
- ✅ **Sipariş ön izleme** — Müşteri onayı öncesi özet
- ✅ **Sipariş onayla** — Replay korumalı, 5dk TTL

## 🚀 Hızlı Kurulum (5 dakika)

1. Bu modülü yükle: **Modules → Module Manager → Upload a Module → ZIP seç**
2. Aktive et: modül listesinde **Configure** linkine tıkla
3. 5-adımlı setup:
   - API key üret (sadece bir kez gösterilir)
   - Manifest URL kopyala
   - IP whitelist (opsiyonel — Dowaba prod IP'leri)
   - Read scope açık (default), Write scope (sipariş oluşturma) için bilinçli aç
   - Modül "Enabled" toggle açık
4. Dowaba paneline gir: **dowaba.com → Siteler → Entegrasyonlar → Bundle Import**
5. Manifest URL + API Key yapıştır → 10 function otomatik aktif

## 🛡️ Güvenlik — Bankacılık Standardı

- **Bearer Token Auth** — `Authorization: Bearer psm_xxxxx` (sha256 hash, plain database'de YOK)
- **IP Whitelist** — Sadece Dowaba sunucularından gelen istekler kabul (opsiyonel)
- **Scope Guard** — `write` izni varsayılan KAPALI; sipariş oluşturma için bilinçli açılır (AI prompt injection koruması)
- **Order Confirmation Flow** — Müşteri onayı olmadan sipariş açılmaz (2 adımlı flow)
- **Replay Protection** — preview_id one-shot consume, 5 dk TTL
- **Audit Log** — Her gelen istek `dowaba_audit` tablosuna; 30 gün retention; modül konfigürasyon ekranından görüntülenir
- **SSRF + SQL Injection Guard** — PrestaShop standartı escape + Db::execute prepared
- **KVKK + GDPR Uyumlu** — Müşteri verisi şifrelenmiş, GDPR Madde 6/1 + KVKK Madde 5 uyumlu

## 📊 Test edildi

PrestaShop 8.1 + PHP 8.2 + MariaDB 11 ortamında **gerçek sipariş** doğrulandı. Lokal Docker e2e test suite ile her release öncesi koşturulur.

## 💰 Fiyatlandırma

**Modül tamamen ücretsiz** (MIT lisanslı). Dowaba SaaS planları:

- **Free**: 100 mesaj/ay — Test ve küçük mağazalar
- **Starter**: ₺499/ay — 1.000 mesaj
- **Pro**: ₺1.499/ay — 10.000 mesaj
- **Enterprise**: Özel teklif

Detaylar: https://dowaba.com/pricing

## 🆘 Destek

- 📧 https://dowaba.com/destek (24 saat içinde yanıt)
- 💬 GitHub Issues: https://github.com/acr-yazilim/dowaba-plugins/issues
- 📚 Dokümantasyon: https://github.com/acr-yazilim/dowaba-plugins/tree/main/prestashop
- 🎬 Video demo: [YouTube link burada]

## 📋 Sistem Gereksinimleri

- PrestaShop 1.7.7.x veya 8.0+ (8.1 önerilir)
- PHP 8.0+ (8.2 önerilir)
- cURL, JSON, mbstring extensions
- HTTPS önerilir (TLS Dowaba bağlantısı için şart)

## 🔄 Versiyon Geçmişi

- **v0.1.0** (2026-05-26): İlk public release — 10 function, PS 1.7 + 8.x dual support

Tam değişiklik geçmişi: [CHANGELOG](https://github.com/acr-yazilim/dowaba-plugins/blob/main/prestashop/CHANGELOG.md)

## 🌍 Dil Desteği

Modül admin: 🇹🇷 Türkçe + 🇬🇧 English
AI yanıtları: Türkçe ana, Dowaba 30+ dil destekler (İngilizce, Almanca, Fransızca, İspanyolca, Arapça, Rusça vb.)

---

**English version available — see GitHub README.**
```

### Field: **Comments** (opsiyonel — addons.prestashop.com review team'e not)

```
This module connects PrestaShop stores to Dowaba AI (https://dowaba.com), a multi-channel customer engagement platform. The module exposes 10 REST endpoints that Dowaba's AI calls to fetch product/order data and create customer-confirmed orders.

Security highlights:
- Bearer token (sha256 hashed in DB, plain key never stored)
- Optional IP whitelist (defaults to no restriction)
- Scope guard (write default disabled — admin must explicitly enable for AI order creation)
- 2-step confirmation flow for orders (preview → customer confirmation → create)
- Audit log (30-day retention, visible in module config screen)
- SSRF + SQL injection guards (PrestaShop Db::execute prepared statements)

Tested on PrestaShop 8.1 with PHP 8.2 + MariaDB 11. Live integration verified with real WhatsApp customer conversations creating actual PrestaShop orders.

The module is MIT licensed and open source: https://github.com/acr-yazilim/dowaba-plugins/tree/main/prestashop

PrestaShop Validator: passed (no errors).
ZIP structure: dowaba_ai/ folder with bootstrap=true HelperForm config page.

Demo available: I can provide a test PrestaShop instance + Dowaba demo account credentials if needed for review.
```

### Field: **Screenshots** — 5-8 görsel yüklenir

Bkz [SCREENSHOTS.md](./SCREENSHOTS.md). Önerilen sıralama:

1. `01-module-config.png` (1280x720) — Modül "Configure" ekranı, 5 ayar görünür
2. `02-api-key-generated.png` — API key üretildi yeşil banner
3. `03-manifest-url.png` — Manifest URL input + copy hazır
4. `04-dowaba-bundle-import.png` — Dowaba panel Bundle Import dialog
5. `05-whatsapp-conversation.png` — WhatsApp müşteri konuşması
6. `06-order-confirmation.png` — Sipariş özet + müşteri "Evet"
7. `07-audit-log.png` — Modül konfigürasyon altında audit log tab
8. `08-function-list.png` — Dowaba panel'de 10 `psm_*` function aktif

### Field: **Banner** (zorunlu, 1 görsel)

`banner-600x300.png` — `marketing/banner.svg` mock'undan render edilmiş PNG.

### Field: **Module Icon** (zorunlu, 32x32 veya 64x64 PNG)

`src/dowaba_ai/logo.png` (mevcut, 64x64).

### Field: **Pricing**

```
Free
```

### Field: **Demo URL** (opsiyonel)

```
https://demo-prestashop.dowaba.com
(admin: review@dowaba.com / [marketplace review için özel parola])
```

---

## ✅ Pre-Submission Checklist

Submission'a basmadan önce kontrol et:

- [ ] PrestaShop Addons partner hesabı **email doğrulanmış** (~24-48h onay)
- [ ] Partner profil tamamlandı (logo, bio, website, support email)
- [ ] Tax info doldurulmuş (ücretsiz modül için opsiyonel ama önerilir)
- [ ] Yüklenen zip dosyası **production build** (`build.sh` ile üretilmiş)
- [ ] PrestaShop Validator passed: https://validator.prestashop.com/
- [ ] Screenshot'lar **gerçek modülü** gösteriyor (mock değil)
- [ ] Banner 600x300 px (Marketplace standart — resize edilmemiş native)
- [ ] Long description'da broken link YOK
- [ ] Support email çalışır durumda (https://dowaba.com/destek)
- [ ] Demo URL erişilebilir (eklenmişse)
- [ ] Privacy Policy URL canlı (`marketing/PRIVACY.md` veya dowaba.com/privacy)
- [ ] License = MIT (Free)
- [ ] GitHub releases tag güncel (`prestashop-v0.1.0`)
- [ ] Module Manager'da "Configure" linki açılıyor + form çalışıyor
- [ ] `dist/dowaba-ai-prestashop-0.1.0.zip` çalışan ZIP (install/uninstall test edildi)

---

## 📬 Submission sonrası

1. **Status**: `Under Review` → 2-4 hafta bekle (PrestaShop OpenCart'tan daha yavaş)
2. **Email gelir**: Approved → live + Marketplace listing URL
3. **Onaylanırsa**: Listing canlı, partner dashboard'da download/install stats
4. **Reddedilirse**: Email'deki feedback'i okuyup düzelt → resubmit
   - Yaygın red sebepleri:
     - PrestaShop Validator hatası
     - Configuration page çalışmıyor
     - Documentation eksik (README inside ZIP)
     - Hook implementation eksik (modül install fail)
     - i18n eksik (en azından EN translation)

---

## 🎯 İlk hafta marketing kampanyası

Yayın günü:
- [ ] LinkedIn post (Türk + Avrupa e-commerce decision makers)
- [ ] Türkiye PrestaShop Facebook grupları (5+ grup)
- [ ] PrestaShop Resmi Forum: https://www.prestashop.com/forums/ (Modules > Module Showcase)
- [ ] Twitter/X duyurusu (#prestashop #ecommerce #ai)
- [ ] dowaba.com ana sayfada "PrestaShop Addons'da da var" badge
- [ ] YouTube tutorial (3 dakika kurulum video)
- [ ] Email blast Dowaba mevcut müşterilere

İlk 30 günde hedef:
- 80+ download (PrestaShop OpenCart'tan daha küçük pazar)
- 5+ pozitif review
- 4.5+ ortalama rating
