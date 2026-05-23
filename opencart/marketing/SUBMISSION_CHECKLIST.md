# OpenCart Marketplace Submission Checklist

Adım adım kontrol listesi. Her checkbox tamamlanınca işaretle. Tahmini toplam süre: **~4 saat hazırlık + 1-2 hafta review bekleme**.

---

## 🏗️ HAZIRLIK FAZI (~4 saat — sen yapacaksın)

### Phase 1: Hesap (30 dk + 24h bekleme)

- [ ] **OpenCart.com'da Partner hesabı aç**
  URL: https://www.opencart.com/index.php?route=account/register
  - "Partner" rolünü seç
  - Email doğrula (inbox + spam'i kontrol et)
  - **24 saat bekle** — onay manuel
- [ ] **Partner profil doldur**
  https://www.opencart.com/index.php?route=account/account
  - Display name: `Aydın Acar` veya `Dowaba`
  - Country: Türkiye
  - Bio: "AI customer engagement platform — WhatsApp/IG/Mail/Voice integrations"
  - Website: https://dowaba.com
  - Support email: support@dowaba.com (test mail at, alıyor musun?)
  - Logo: 200x200 PNG (dowaba.com logo download et)
- [ ] **Tax info** (ücretsiz extension için opsiyonel — atlayabilirsin)

### Phase 2: Marketing varlıkları (1-2 saat — bana hazırlattın)

- [x] **README.md** Marketplace standardına yükseltildi (`opencart/README.md`)
- [x] **MARKETPLACE_LISTING.md** — Copy-paste ready metin (`opencart/marketing/MARKETPLACE_LISTING.md`)
- [x] **SCREENSHOTS.md** — Çekim rehberi (`opencart/marketing/SCREENSHOTS.md`)
- [x] **banner.svg** — 600x300 SVG mockup (`opencart/marketing/banner.svg`)
- [x] **PRIVACY.md** — Privacy Policy (`opencart/marketing/PRIVACY.md`)

### Phase 3: Banner PNG render (15 dk)

- [ ] **banner-600x300.png üret**
  Seçenekler:
  - **Hızlı**: SVG'yi browser'da aç (Safari/Chrome) → CMD+S → "Save Screen" → 600x300 crop
  - **CLI**: `rsvg-convert -h 300 banner.svg > banner-600x300.png` (`brew install librsvg`)
  - **Online**: https://cloudconvert.com/svg-to-png (600x300 boyut)
  - **Figma**: SVG'yi Figma'ya import et → Export PNG → 600x300
- [ ] **Logo + branding finalize**
  Eğer Dowaba'nın gerçek logosu varsa banner.svg'de "Dowaba AI" yazısının üstüne logo path ekle.

### Phase 4: Screenshots (1-2 saat)

Lokal Docker ayakta mı?
```bash
docker compose -f /Users/aydinacar/Documents/dowaba-plugins/opencart/docker/docker-compose.yml ps
```

- [ ] **Docker hazır**: dwb-opencart (8080) + dwb-opencart3 (8081)
- [ ] **8 zorunlu screenshot çek** (1280x720, PNG)
  - [ ] 01-setup-wizard-overview.png
  - [ ] 02-api-key-generated.png
  - [ ] 03-manifest-url-copy.png
  - [ ] 04-connection-test-success.png
  - [ ] 05-dowaba-panel-bundle-import.png
  - [ ] 06-whatsapp-ai-conversation.png ⚠️ Telefon numarası blur
  - [ ] 07-order-confirmation-flow.png ⚠️ Adres blur
  - [ ] 08-audit-log-table.png
- [ ] **Watermark koy** (CleanShot X veya Photoshop): alt sağ "dowaba.com" %50 opacity
- [ ] **`marketing/screenshots/` klasörüne kaydet**

### Phase 5: Privacy + dowaba.com (30 dk)

- [ ] **PRIVACY.md'yi dowaba.com'a yükle**: https://dowaba.com/privacy/opencart-plugin (veya benzer URL)
- [ ] **Test mail at**: support@dowaba.com — gerçekten gelir mi?
- [ ] **README.md'de bağlantıları test et**: https://docs.dowaba.com/opencart varsa, yoksa README'den çıkar

### Phase 6: Demo URL (opsiyonel ama önerilir)

- [ ] **Demo OpenCart kurulumu** (Hetzner / Vercel / Heroku / kendi sunucun)
  - Üzerinde Dowaba plugin yüklü ve bağlı
  - URL örnek: https://demo-oc.dowaba.com
- [ ] **Demo admin credentials**: review@dowaba.com / [özel parola]
- [ ] Demo'da test mağaza verisi (10-20 ürün, 3-5 kategori, birkaç dummy sipariş)

---

## 📤 SUBMISSION FAZI (~1 saat — sen yapacaksın)

### Listing #1: OpenCart 4.x

- [ ] **Login**: https://www.opencart.com/index.php?route=account/login
- [ ] **Add Extension**: Partner dashboard → "Add Extension" butonu
- [ ] **Form doldur** (`MARKETPLACE_LISTING.md` Listing #1 bölümünden kopyala):
  - [ ] Extension Name
  - [ ] Author / Author URL
  - [ ] License: MIT
  - [ ] Category: Modules → Customer Service
  - [ ] Compatibility: ☑ tüm 4.0.x versiyonları
  - [ ] Version: 0.2.1
  - [ ] Short Description (max 200 char)
  - [ ] Long Description (markdown)
  - [ ] Comments (review notes)
- [ ] **Upload zip**: `dist/dowaba-opencart-oc4-0.2.1.ocmod.zip`
- [ ] **Upload banner**: `banner-600x300.png`
- [ ] **Upload screenshots** (sırayla 01-08)
- [ ] **Pricing**: Free
- [ ] **Demo URL** (opsiyonel)
- [ ] **Submit for Review** butonuna bas

### Listing #2: OpenCart 3.x

Aynı flow, sadece:
- [ ] Extension Name → "...for OpenCart 3..."
- [ ] Compatibility → ☑ 3.0.3.x versiyonları
- [ ] Upload zip → `dist/dowaba-opencart-oc3-0.2.1.ocmod.zip`
- [ ] Long Description'a OC4 listing'e link ekle
- [ ] Diğer her şey aynı

---

## ⏳ REVIEW FAZI (1-2 hafta — beklemek)

- [ ] **Status check**: Partner dashboard her gün kontrol
- [ ] **Email gelirse "approved"**: Listing canlı 🎉
- [ ] **Email gelirse "rejected"**: Feedback oku → düzelt → resubmit
  - Yaygın red sebepleri:
    - Documentation eksik
    - Screenshot mock görünüyor (gerçek değil)
    - Long description çok kısa
    - License belirsiz
    - Plugin install fail oluyor (test edemiyorlar)

---

## 🎯 YAYIN SONRASI (sürekli)

### İlk 24 saat

- [ ] **dowaba.com banner ekle**: "OpenCart Marketplace'te de var" link
- [ ] **Sosyal medya duyurusu**:
  - LinkedIn: Türk e-commerce decision makers
  - Twitter/X: #opencart #ecommerce
  - Türkiye OpenCart Facebook grupları (10+ grup arama)
- [ ] **Email blast**: Dowaba mevcut müşterilere duyuru

### İlk hafta

- [ ] **YouTube tutorial** (2 dakika kurulum video)
- [ ] **eticaretmag.com / eticaretpro.com forum post**
- [ ] **İlk download'lardan müşteri review iste** (email otomasyonu)

### İlk ay

Hedef KPI:
- [ ] 100+ download
- [ ] 4.5+ rating (5+ review)
- [ ] 0 critical bug report
- [ ] 3+ olumlu görüş (testimonial)

---

## 🆘 Sorun Olursa

| Sorun | Çözüm |
|---|---|
| Partner hesap onay gelmedi | support@opencart.com'a yaz |
| Submission form'da hata | Browser cache temizle, farklı browser dene |
| Zip upload fail (boyut) | OC marketplace 5MB limit — bizim 36 KB OK |
| Review 3 hafta gecikiyor | Partner forum'da soruş veya support'a yaz |
| Plugin marketplace'te canlı ama indirilmiyor | URL/cache problemi — partner support |

---

## ✅ Hızlı tamamlanma kontrolü

Hepsi check ise hazırsın:
- [ ] OpenCart partner hesap **doğrulanmış**
- [ ] **README.md** + **MARKETPLACE_LISTING.md** + **SCREENSHOTS.md** + **banner-600x300.png** + **PRIVACY.md** hazır
- [ ] **8 screenshot** çekilmiş ve watermark'lı
- [ ] **Demo URL** (opsiyonel) ayakta
- [ ] **dowaba.com/privacy** ulaşılabilir
- [ ] **support@dowaba.com** çalışır
- [ ] **GitHub releases** güncel (opencart-v0.2.1 tag)

Sonra: 2 listing form'unu sırayla doldur, submit et, 1-2 hafta bekle.

🎉 İyi şanslar!
