# OpenCart Marketplace Listing — Copy-Paste Ready

Bu dosya OpenCart Marketplace partner dashboard'unda **submission formuna** doğrudan kopyalanmak üzere hazırlanmış metinleri içerir.

URL: https://www.opencart.com/index.php?route=account/login (Partner login)

---

## 📝 Listing #1 — Dowaba AI for OpenCart 4.x

### Field: **Extension Name** (max 64 char)

```
Dowaba AI — WhatsApp + Instagram + Mail AI Chatbot
```

### Field: **Author Name**

```
Aydın Acar (Dowaba)
```

### Field: **Author URL**

```
https://dowaba.com
```

### Field: **License**

```
MIT (Free / Open Source)
```

### Field: **Category** (dropdown)

```
Modules → Customer Service
```

(alternatif: Marketing, Reports, Tools)

### Field: **Compatibility** (checkbox list)

```
☑ 4.0.0.0
☑ 4.0.1.0
☑ 4.0.1.1
☑ 4.0.2.0
☑ 4.0.2.1
☑ 4.0.2.2
☑ 4.0.2.3
```

### Field: **Version**

```
0.2.20
```

### Field: **Short Description** (max 200 char)

```
AI chatbot connects your store to WhatsApp/Instagram/Mail. Customers search products, compare, and place orders in natural language. 24/7 multilingual support. 5-min setup.
```

### Field: **Long Description** (Markdown destekler, ~600-1000 kelime ideal)

```markdown
# Dowaba AI — Akıllı Müşteri Temsilcisi 7/24

Mağazana bir AI chatbot bağla; müşterilerin **WhatsApp**, **Instagram DM**, **E-posta** ve **Telefon** üzerinden ulaşıp ürün arayabilsin, karşılaştırabilsin, sipariş verebilsin. Türkçe + 30+ dil. 5 dakikada kurulum.

## 🎯 Ne Yapıyor?

Müşteri WhatsApp'tan yazıyor:
> "iPhone 15 Pro var mı? Fiyatı kaç?"

AI mağazandan **canlı** veri çekip cevaplıyor:
> "iPhone 15 Pro 256 GB stokta var, 64.999 TL. İndirim de var: bu hafta %5 (61.749 TL). Sipariş etmek ister misin?"

Müşteri: "Evet"

AI özet gösteriyor:
> "📦 Sipariş özeti: 1× iPhone 15 Pro — 61.749 TL + Kargo 49 TL = 61.798 TL. Adres: [müşteri adresi]. Onaylıyor musun?"

Müşteri: "Evet"

✅ Sipariş açıldı, ödeme link'i gönderildi.

## ✨ 10 AI Function

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

## 🚀 Hızlı Kurulum

1. Bu eklentiyi yükle: **Extensions → Installer → Upload**
2. Aktive et: **Extensions → Modules → Dowaba AI → Install → Edit**
3. 5-adımlı wizard'ı tamamla:
   - API key üret
   - Manifest URL kopyala
   - IP whitelist (opsiyonel)
   - Scope toggle (read default açık, write için bilinçli aç)
   - Bağlantı testi → ✅
4. Dowaba paneline gir: **dowaba.com → Siteler → Entegrasyonlar → Bundle Import**
5. Manifest URL + API Key yapıştır → 10 function otomatik aktif

## 🛡️ Güvenlik — Bankacılık Standardı

- **Bearer Token Auth** — `Authorization: Bearer opc_xxxxx` (sha256 hash, plain database'de YOK)
- **IP Whitelist** — Sadece Dowaba sunucularından gelen istekler kabul (opsiyonel)
- **Scope Guard** — `write` izni varsayılan KAPALI; sipariş oluşturma için bilinçli açılır (AI prompt injection koruması)
- **Order Confirmation Flow** — Müşteri onayı olmadan sipariş açılmaz
- **Replay Protection** — preview_id one-shot consume, 5 dk TTL
- **Audit Log** — Her gelen istek `dowaba_audit` tablosuna; 30 gün retention; admin panelinden görüntülenir
- **SSRF + SQL Injection Guard** — Dowaba HttpHandler + escape pattern
- **KVKK + GDPR Uyumlu** — Müşteri verisi şifrelenmiş, KVKK Madde 12 uyumlu

## 📊 Test edilmiş

OpenCart 4.0.2.3 + PHP 8.2 + MariaDB 11 ortamında **gerçek sipariş** doğrulandı. Plugin'in lokal Docker e2e test suite'i var, her release öncesi koşturuluyor.

## 💰 Fiyatlandırma

**Plugin tamamen ücretsiz** (MIT lisanslı). Dowaba SaaS planları:

- **Ücretsiz deneme**: 50 mesaj — başlamak için (kendi AI anahtarınızla)
- **Starter**: ₺199/ay (₺1.990/yıl) — 1.000 mesaj/ay · 1 mağaza
- **Pro**: ₺499/ay (₺4.990/yıl) — 5.000 mesaj/ay · 3 mağaza
- **Bayilik (Business)**: mağaza başı ₺3.999/yıl'dan — 5.000 mesaj/mağaza · white-label + alt kullanıcı · hacim indirimi

Detaylar: https://dowaba.com/pricing

## 🆘 Destek

- 📧 https://dowaba.com/destek (24 saat içinde yanıt)
- 💬 GitHub Issues: https://github.com/acr-yazilim/dowaba-plugins/issues
- 📚 Dokümantasyon: https://github.com/acr-yazilim/dowaba-plugins/tree/main/opencart
- 🎬 Video demo: [YouTube link burada]

## 📋 Sistem Gereksinimleri

- OpenCart 4.0.x
- PHP 8.0+ (8.2 önerilir)
- cURL, JSON, mbstring extensions
- HTTPS önerilir (HTTP de çalışır ama Dowaba'ya bağlantı için TLS şart)

## 🔄 Versiyon Geçmişi

- **v0.2.20** (2026-05-29): Kararlılık serisi — kurulum + API anahtarı kaydı düzeltmeleri (OC3+OC4), Authorization header fallback, PHP 7.0+ uyumu, ürün görsel/galeri desteği
- **v0.2.1** (2026-05-23): OC3 schema fixes (paralel listing için)
- **v0.2.0**: OpenCart 3.x dual support eklendi
- **v0.1.2**: OC4 routing fix + canlı entegrasyon doğrulandı
- **v0.1.1**: 4 kritik güvenlik fix
- **v0.1.0**: İlk public release

Tam değişiklik geçmişi: [CHANGELOG](https://github.com/acr-yazilim/dowaba-plugins/blob/main/opencart/CHANGELOG.md)

## 🌍 Dil Desteği

Plugin admin: 🇹🇷 Türkçe + 🇬🇧 English
AI yanıtları: Türkçe ana, Dowaba 30+ dil destekler

---

**English version available — see GitHub README.**
```

### Field: **Comments** (opsiyonel — review'cılara not)

```
This plugin connects OpenCart stores to Dowaba AI (https://dowaba.com), a multi-channel customer engagement platform. The plugin exposes 10 REST endpoints that Dowaba's AI calls to fetch product/order data and create customer-confirmed orders.

Security highlights:
- Bearer token (sha256 hashed in DB)
- Optional IP whitelist (defaults to no restriction)
- Scope guard (write default disabled — admin must explicitly enable for AI order creation)
- 2-step confirmation flow for orders (preview → customer confirmation → create)
- Audit log (30-day retention)
- SSRF + SQL injection guards

Tested on OpenCart 4.0.2.3 with PHP 8.2 + MariaDB 11. Live integration verified with real WhatsApp customer conversations creating actual orders.

The plugin is MIT licensed and open source: https://github.com/acr-yazilim/dowaba-plugins

Demo available: I can provide a test OpenCart instance + Dowaba demo account credentials if needed for review.
```

### Field: **Screenshots** — 5-10 görsel yüklenir

Bkz [SCREENSHOTS.md](./SCREENSHOTS.md) çekim rehberi. Önerilen sıralama:

1. `01-setup-wizard.png` (1280x720) — Setup wizard ana ekran
2. `02-api-key-generated.png` — API key üretildi banner
3. `03-manifest-url.png` — Manifest URL kopyala
4. `04-dowaba-bundle-import.png` — Dowaba panel Bundle Import
5. `05-whatsapp-conversation.png` — WhatsApp müşteri konuşması
6. `06-order-confirmation.png` — Sipariş özet + müşteri "Evet"
7. `07-audit-log.png` — Plugin admin audit log sekmesi
8. `08-function-list.png` — Dowaba panel'de 10 function

### Field: **Banner** (zorunlu, 1 görsel)

`banner-600x300.png` — `marketing/banner.svg` mock'undan render edilmiş PNG.

### Field: **Pricing**

```
Free
```

### Field: **Demo URL** (opsiyonel)

```
https://demo-opencart-4.dowaba.com
(admin: review@dowaba.com / [marketplace review için özel parola])
```

---

## 📝 Listing #2 — Dowaba AI for OpenCart 3.x

**Yukarıdaki #1 listing'i ile aynı, sadece şu alanlar değişiyor:**

### Field: **Extension Name**

```
Dowaba AI for OpenCart 3 — WhatsApp + Instagram + Mail Chatbot
```

### Field: **Compatibility**

```
☑ 3.0.3.0
☑ 3.0.3.1
☑ 3.0.3.2
☑ 3.0.3.3
☑ 3.0.3.4
☑ 3.0.3.5
☑ 3.0.3.6
☑ 3.0.3.7
☑ 3.0.3.8
☑ 3.0.3.9
```

### Field: **Long Description** (3.x notu eklenir)

Listing #1'in long description'ı + en üstte şu kutu eklenir:

```markdown
> 📌 **OpenCart 3.x sürümü için.** OpenCart 4.x kullanıyorsanız [diğer listing'imize](URL_TO_OC4_LISTING) bakın.
```

### Field: **Version**

```
0.2.20
```

### Field: **Comments** (review notes)

```
This is the OpenCart 3.x port of our Dowaba AI integration. Tested on OpenCart 3.0.3.9 with PHP 8.2.

Same security model as the OC 4.x version (already submitted/published):
- Bearer token + sha256 hash
- Scope guard
- 2-step confirmation flow for orders
- 30-day audit log

Schema adaptations made for OC 3.x:
- payment_method/shipping_method as strings (vs OC4 arrays)
- payment_code/shipping_code as separate fields
- addOrderHistory() method (vs OC4 addHistory)

Live tested: real orders #1 (150 USD) and #2 (249 USD) created via Dowaba prod → Cloudflare tunnel → local Docker OC 3.0.3.9.

Open source: https://github.com/acr-yazilim/dowaba-plugins
```

---

## ✅ Pre-Submission Checklist

Submission'a basmadan önce şunları kontrol et:

- [ ] Partner hesabı **email doğrulanmış** (24 saat bekledikten sonra)
- [ ] Profile tamamlandı (logo, bio, website, support email)
- [ ] Tax info ücretsiz extension için opsiyonel — eklemediysen sorun yok
- [ ] Yüklenen zip dosyası **production build** (build.sh ile üretilmiş, .ocmod.zip)
- [ ] OC3 ve OC4 zip'leri ayrı listing'lere yüklendi
- [ ] Screenshot'lar **gerçek plugin'i** gösteriyor (mock değil)
- [ ] Banner 600x300 px (resize edilmemiş, native boyut)
- [ ] Long description'da broken link YOK
- [ ] Support email çalışır durumda (test mail at, geliyor mu?)
- [ ] Demo URL erişilebilir (eğer eklendiyse)
- [ ] Privacy Policy URL canlı (dowaba.com/privacy veya marketing/PRIVACY.md)
- [ ] License = MIT (Free)
- [ ] GitHub releases tag'ler güncel (`opencart-v0.2.20`)

---

## 📬 Submission sonrası

1. **Status**: `Under Review` → 1-2 hafta bekle
2. **Email gelir**: Approved → live; Rejected → feedback ile
3. **Onaylanırsa**: Listing canlı, partner dashboard'da download stats göründü
4. **Reddedilirse**: Email'deki feedback'i okuyup düzelt → resubmit (genelde 2-3 döngüde geçer)

## 🎯 İlk hafta marketing kampanyası

Yayın günü:
- [ ] LinkedIn post (Türk e-commerce decision makers'lar için)
- [ ] Türkiye OpenCart Facebook grupları (10+ grup)
- [ ] eticaretmag.com / eticaretpro.com forum post
- [ ] Twitter/X duyurusu (#opencart #ecommerce hashtag'leri)
- [ ] dowaba.com ana sayfada "OpenCart Marketplace'te de var" badge
- [ ] YouTube tutorial (2 dakika kurulum video)
- [ ] Email blast Dowaba mevcut müşterilere

İlk 30 günde hedef:
- 100+ download
- 5+ pozitif review
- 4.5+ ortalama rating
