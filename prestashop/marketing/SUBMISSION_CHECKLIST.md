# PrestaShop Addons Submission Checklist

Adım adım kontrol listesi. Her checkbox tamamlanınca işaretle. Tahmini toplam süre: **~5 saat hazırlık + 2-4 hafta review bekleme** (PrestaShop OpenCart'tan daha yavaş onaylar).

---

## 🏗️ HAZIRLIK FAZI (~5 saat — sen yapacaksın)

### Phase 1: Hesap (30 dk + 24-48h bekleme)

- [ ] **addons.prestashop.com Partner hesabı aç**
  URL: https://addons.prestashop.com/en/login (alt: "Become a contributor")
  - "Developer / Module Author" rolünü seç
  - Email doğrula (inbox + spam'i kontrol et)
  - **24-48 saat bekle** — onay manuel (PrestaShop ekibi)
- [ ] **Partner profil doldur**
  - Display name: `Aydın Acar` veya `Dowaba`
  - Country: Türkiye (Avrupa pazar için EU notu ekle)
  - Bio: "AI customer engagement platform — WhatsApp/Instagram/TikTok/Mail/Voice integrations for e-commerce"
  - Website: https://dowaba.com
  - Support email: https://dowaba.com/destek (test mail at, alıyor musun?)
  - Logo: 200x200 PNG (dowaba.com logo download et)
  - VAT number: TR VKN (eğer var ise tax info kısmında)
- [ ] **Banking / tax info**
  - Free modül için zorunlu DEĞİL ama gelecek paid modüller için doldur
  - PayPal email veya banka IBAN (EU SEPA)
  - TR'den satıcılar için: KDV beyanı + faturalandırma şart

### Phase 2: Marketing varlıkları (1-2 saat — bana hazırlattın)

- [x] **README.md** Marketplace standardına yükseltildi (`prestashop/README.md`)
- [x] **MARKETPLACE_LISTING.md** — Türkçe copy-paste ready (`prestashop/marketing/MARKETPLACE_LISTING.md`)
- [x] **MARKETPLACE_LISTING_EN.md** — English copy-paste ready
- [x] **SCREENSHOTS.md** — Çekim rehberi
- [x] **banner.svg** — 600x300 SVG mockup
- [x] **banner-710x380.html** — HTML mockup (Chrome headless render)
- [x] **thumbnail-260x152.html** — HTML mockup
- [x] **PRIVACY.md** — Privacy Policy (TR + EN, GDPR + KVKK)

### Phase 3: Banner + thumbnail PNG render (15 dk)

- [ ] **banner-600x300.png üret**
  Seçenekler:
  - **Hızlı (Chrome headless)**:
    ```bash
    /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
      --headless --screenshot=banner-600x300.png --window-size=600,300 \
      --hide-scrollbars file://$PWD/banner.svg
    ```
  - **rsvg-convert** (Homebrew): `rsvg-convert -h 300 banner.svg > banner-600x300.png`
  - **Online**: https://cloudconvert.com/svg-to-png (600x300 boyut)
- [ ] **banner-710x380.png üret** (HTML mockup → PNG):
    ```bash
    /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
      --headless --screenshot=banner-710x380.png --window-size=710,380 \
      --hide-scrollbars file://$PWD/banner-710x380.html
    ```
- [ ] **thumbnail-260x152.png üret**:
    ```bash
    /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome \
      --headless --screenshot=thumbnail-260x152.png --window-size=260,152 \
      --hide-scrollbars file://$PWD/thumbnail-260x152.html
    ```
- [ ] **Logo + branding finalize**
  Eğer Dowaba'nın resmi logosu varsa banner.svg'deki "Dowaba AI" yazısının üstüne logo path ekle.

### Phase 4: Screenshots (1-2 saat)

Lokal Docker ayakta mı?
```bash
cd prestashop/docker && docker compose ps
# Beklenen: dwb-prestashop (8091) running
```

- [ ] **Docker hazır**: PrestaShop 8.1 + MariaDB 11 + PHP 8.2
- [ ] **Modül install**: Module Manager → Upload `dist/dowaba-ai-prestashop-0.1.0.zip`
- [ ] **8 zorunlu screenshot çek** (1280x720, PNG)
  - [ ] 01-module-config-overview.png
  - [ ] 02-api-key-generated.png
  - [ ] 03-manifest-url-copy.png
  - [ ] 04-dowaba-bundle-import.png
  - [ ] 05-whatsapp-ai-conversation.png ⚠️ Telefon numarası blur
  - [ ] 06-order-confirmation-flow.png ⚠️ Adres blur
  - [ ] 07-audit-log-table.png
  - [ ] 08-dowaba-function-list.png
- [ ] **Watermark koy** (CleanShot X veya Photoshop): alt sağ "dowaba.com" %50 opacity
- [ ] **`marketing/screenshots/` klasörüne kaydet**

### Phase 5: PrestaShop Validator + privacy + dowaba.com (45 dk)

- [ ] **PrestaShop Validator'da test et**: https://validator.prestashop.com/
  - ZIP yükle (`dist/dowaba-ai-prestashop-0.1.0.zip`)
  - **0 errors / 0 warnings** beklenen sonuç
  - Hata varsa düzelt + yeniden build
- [ ] **PRIVACY.md'yi dowaba.com'a yükle**: https://dowaba.com/privacy/prestashop-plugin (veya benzer URL)
- [ ] **Test mail at**: https://dowaba.com/destek — gerçekten gelir mi?
- [ ] **README.md'de bağlantıları test et**: https://docs.dowaba.com/prestashop varsa, yoksa README'den çıkar
- [ ] **dowaba.com/privacy** + **dowaba.com/terms** sayfaları canlı

### Phase 6: Demo URL (opsiyonel ama önerilir)

- [ ] **Demo PrestaShop kurulumu** (Hetzner / kendi sunucun)
  - Üzerinde Dowaba modülü yüklü ve bağlı
  - URL örnek: https://demo-prestashop.dowaba.com
- [ ] **Demo admin credentials**: review@dowaba.com / [özel parola]
- [ ] Demo'da test mağaza verisi (10-20 ürün, 3-5 kategori, birkaç dummy sipariş)
- [ ] EU pazarı için: demo en az 2 dilde (TR + EN) ve EUR para birimi

### Phase 7: ZIP build + final test (15 dk)

- [ ] `cd prestashop && ./build.sh 0.1.0`
- [ ] `unzip -l dist/dowaba-ai-prestashop-0.1.0.zip` ile içeriği kontrol et
  - `dowaba_ai/` klasörü root'ta olmalı (PrestaShop convention)
  - `dowaba_ai.php`, `logo.png`, `classes/`, `controllers/` hepsi var
- [ ] Fresh PrestaShop kuruluma upload + install et
- [ ] Configure ekranı açılıyor mu?
- [ ] API key generate çalışıyor mu?
- [ ] Manifest URL endpoint'i HTTP 200 dönüyor mu?
  ```bash
  curl https://demo-prestashop.dowaba.com/index.php?fc=module&module=dowaba_ai&controller=manifest
  ```

---

## 📤 SUBMISSION FAZI (~1 saat — sen yapacaksın)

### Single Listing (PrestaShop 1.7 + 8.x dual support)

- [ ] **Login**: https://addons.prestashop.com/en/login
- [ ] **Submit Product**: Partner dashboard → "Add a new product" → "Module"
- [ ] **Form doldur** (`MARKETPLACE_LISTING_EN.md` veya TR'den kopyala):
  - [ ] Module Name (max 64 char)
  - [ ] Technical Name: `dowaba_ai`
  - [ ] Author / Author URL
  - [ ] License: MIT (Open Source)
  - [ ] Category: Front Office Features → Customer Reassurance → Customer Service
  - [ ] Compatibility: ☑ 1.7.7.x, 1.7.8.x, 8.0.x, 8.1.x, 8.2.x
  - [ ] Version: 0.1.0
  - [ ] Tags (max 10)
  - [ ] Short Description (max 150 char)
  - [ ] Long Description (markdown)
  - [ ] Comments (review team notes)
- [ ] **Upload zip**: `dist/dowaba-ai-prestashop-0.1.0.zip`
- [ ] **Upload banner**: `banner-600x300.png` (zorunlu)
- [ ] **Upload module icon**: `src/dowaba_ai/logo.png` (zorunlu)
- [ ] **Upload screenshots** (sırayla 01-08, opsiyonel 09-11)
- [ ] **Pricing**: Free
- [ ] **Demo URL** (opsiyonel ama ÖNERİLİR)
- [ ] **Privacy Policy URL**: https://dowaba.com/privacy/prestashop-plugin
- [ ] **Submit for Review** butonuna bas

---

## ⏳ REVIEW FAZI (2-4 hafta — beklemek)

PrestaShop Addons review süreci OpenCart'tan daha titiz:
- Technical review (modül kodu okunur, validator hatası kabul edilmez)
- Marketing review (description + screenshots quality check)
- Legal review (GDPR uyumluluk, privacy policy)

- [ ] **Status check**: Partner dashboard her hafta kontrol
- [ ] **Email gelirse "approved"**: Listing canlı 🎉 + addons.prestashop.com URL
- [ ] **Email gelirse "rejected"**: Feedback oku → düzelt → resubmit
  - Yaygın red sebepleri:
    - PrestaShop Validator hatası (0 errors zorunlu)
    - Configuration page çalışmıyor
    - Install/uninstall hook eksik
    - Documentation eksik (README inside ZIP de olmalı)
    - i18n eksik (en azından English translation)
    - Hassas veri loglanıyor (audit log içeriği kontrol)
    - Privacy policy GDPR uyumlu değil

---

## 🎯 YAYIN SONRASI (sürekli)

### İlk 24 saat

- [ ] **dowaba.com banner ekle**: "PrestaShop Addons'da da var" link
- [ ] **Sosyal medya duyurusu**:
  - LinkedIn: Türk + Avrupa (Fransa/Almanya/İspanya) e-commerce decision makers
  - Twitter/X: #prestashop #ecommerce #ai
  - Türkiye + Avrupa PrestaShop Facebook grupları (5+ grup arama)
- [ ] **Email blast**: Dowaba mevcut müşterilere duyuru

### İlk hafta

- [ ] **YouTube tutorial** (3 dakika kurulum video — EN + TR)
- [ ] **PrestaShop resmi forum post**: https://www.prestashop.com/forums/forum/192-modules/ (Module Showcase bölümü)
- [ ] **eticaretmag.com / eticaretpro.com forum post**
- [ ] **İlk download'lardan müşteri review iste** (email otomasyonu)

### İlk ay

Hedef KPI:
- [ ] 80+ download (PrestaShop pazar OpenCart'tan daha küçük TR'de)
- [ ] 4.5+ rating (5+ review)
- [ ] 0 critical bug report
- [ ] 3+ olumlu görüş (testimonial)

### İlk 3 ay

- [ ] PrestaShop versiyonları için compat extend (yeni minor versionlar)
- [ ] EU pazarı için **multi-currency** desteği finalize (€/£/CHF)
- [ ] **Translations**: Fransızca, Almanca, İspanyolca, İtalyanca (PrestaShop EU pazar)

---

## 🆘 Sorun Olursa

| Sorun | Çözüm |
|---|---|
| Partner hesap onay gelmedi | contact@prestashop.com'a yaz |
| Submission form'da hata | Browser cache temizle, farklı browser dene |
| Validator "Error: missing hook" | `dowaba_ai.php` `install()` metodundaki `registerHook()` kontrol |
| Validator "Warning: deprecated function" | PrestaShop 8.x compat — eski API kullanımları |
| Review 4+ hafta gecikiyor | Partner forum'da soruş + email |
| Modul install fail (review) | Hassas: Validator pass olsa bile sandbox install fail edebilir — fresh install'da test et |
| Configure page boş | `getContent()` metodu return ediyor mu? `HelperForm` token doğru mu? |

---

## ✅ Hızlı tamamlanma kontrolü

Hepsi check ise hazırsın:
- [ ] PrestaShop Addons partner hesap **doğrulanmış**
- [ ] **README.md** + **MARKETPLACE_LISTING.md** + **MARKETPLACE_LISTING_EN.md** + **SCREENSHOTS.md** + **banner-600x300.png** + **PRIVACY.md** hazır
- [ ] **8 screenshot** çekilmiş ve watermark'lı
- [ ] **PrestaShop Validator** passed (0 errors)
- [ ] **Demo URL** (opsiyonel) ayakta
- [ ] **dowaba.com/privacy** ulaşılabilir
- [ ] **https://dowaba.com/destek** çalışır
- [ ] **GitHub releases** güncel (`prestashop-v0.1.0` tag)
- [ ] **`dist/dowaba-ai-prestashop-0.1.0.zip`** fresh install'da çalışıyor

Sonra: Single listing form'unu doldur, submit et, 2-4 hafta bekle.

🎉 İyi şanslar!

---

## 📚 Faydalı Linkler

- PrestaShop Addons: https://addons.prestashop.com
- Developer docs: https://devdocs.prestashop-project.org/
- Module Validator: https://validator.prestashop.com/
- Resmi forum (modules): https://www.prestashop.com/forums/forum/192-modules/
- Partner program: https://addons.prestashop.com/en/content/8-developers
- GDPR compliance guide: https://www.prestashop.com/en/blog/prestashop-and-gdpr
