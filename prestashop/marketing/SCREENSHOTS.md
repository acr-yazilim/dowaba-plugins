# Screenshots Çekim Rehberi — PrestaShop Addons

addons.prestashop.com listing'inde en yüksek conversion için **8 zorunlu screenshot**. Mac'te `CMD+SHIFT+4` ile alıp `marketing/screenshots/` altına kaydet.

**Boyut**: 1280x720 (16:9) önerilir. PrestaShop Addons 600x600+ kabul ediyor ama 1280x720 ideal.

**Format**: PNG (lossless, metinler net).

**Naming convention**: `{numara}-{açıklama-kebab-case}.png` (alfabetik sıra önemli)

---

## Çekim Listesi

### 1. `01-module-config-overview.png`

**Ne göstermeli:** Modül "Configure" ekranı — `Modules → Module Manager → Dowaba AI → Configure`. Manifest URL, API Key prefix, Switch toggle'lar (Module enabled, Read scope, Write scope), IP Whitelist, Audit retention input'ları görünür.

**Çekim yeri:**
```
http://localhost:8091/admin-dowaba → Modules → Module Manager → Dowaba AI → Configure
```

**Hazırlık:**
- Modül install + aktive edilmiş olmalı
- Tüm form alanları görünür şekilde scroll en başta
- Tarayıcı pencere genişliği ~1400px (16:9 oran için)
- Browser zoom 100%
- PrestaShop BO native theme (default)

**Ne dikkat çekecek:** PrestaShop HelperForm pattern — tanıdık, profesyonel admin UX.

---

### 2. `02-api-key-generated.png`

**Ne göstermeli:** "Regenerate API Key" butonuna tıklandı, plain key yeşil banner'da görünüyor (`psm_xxxxxxxxxxxxx`).

**Hazırlık:**
- Configure ekranını aç
- "🔑 Regenerate API Key" butonuna bas
- PrestaShop `displayConfirmation` yeşil banner'da plain key görünür
- Banner görünür haldeyken screenshot

**Önemli:** Plain key gerçek bir prod key olmamalı — demo key uydur veya fresh PrestaShop install kullan.

**Ne dikkat çekecek:** "Plain bir kez gösterilir, sonra hash kalır" güvenlik mesajı.

---

### 3. `03-manifest-url-copy.png`

**Ne göstermeli:** Manifest URL read-only input + üstündeki "📋 Manifest URL (Copy to DoWaba Bundle Import)" başlık. URL örnek: `https://store.example.com/index.php?fc=module&module=dowaba_ai&controller=manifest`

**Hazırlık:**
- Configure ekranındaki manifest URL bölümünü zoom et (form-control input genişletilmiş)
- Native PrestaShop "Copy" yoksa "Select all" highlight ile göster

**Ne dikkat çekecek:** Tek-tıkla kopyalama kolaylığı.

---

### 4. `04-dowaba-bundle-import.png`

**Ne göstermeli:** Dowaba paneli `dowaba.com/admin/sites/X/integrations` → Bundle Import dialog. Manifest URL ve API key input'ları görünüyor.

**Hazırlık:**
- Tarayıcıda dowaba.com paneline gir (kendi hesabınla)
- Siteler → bir site seç → Entegrasyonlar
- "Bundle Import" butonuna bas
- Modal açıldığında manifest URL + API key alanları görünür
- 10 function preview listesi görünüyor (psm_product_search, psm_order_create vs.)

**Ne dikkat çekecek:** İki ürün arasındaki köprü — PrestaShop bilgisini Dowaba'ya aktarma.

---

### 5. `05-whatsapp-ai-conversation.png`

**Ne göstermeli:** WhatsApp Business hesabında müşteri ile AI arasındaki gerçek konuşma. Müşteri ürün soruyor, AI PrestaShop catalog'tan canlı cevap veriyor.

**Hazırlık:**
- Dowaba bağlı WhatsApp hesabını telefonda aç (veya WhatsApp Web)
- Kendi numarandan test mesajı at: "iPhone 15 Pro var mı?"
- AI cevap versin (PrestaShop'ta gerçek ürün)
- WhatsApp ekranını screenshot

**Maskeleme:** Telefon numarası ve müşteri ismini blur'la (privacy).

**Ne dikkat çekecek:** "Bu modül gerçekten WhatsApp'a bağlanıyor" kanıt.

---

### 6. `06-order-confirmation-flow.png`

**Ne göstermeli:** WhatsApp'ta sipariş onayı flow'u (3 mesaj):
1. AI: "📦 Sipariş özetin: 1× iPhone 15 Pro — 829€ + Kargo 5€ = 834€. Onaylıyor musun?"
2. Müşteri: "Evet"
3. AI: "✅ Siparişin oluştu #12345. Ödeme: [link]"

**Hazırlık:**
- Demo PrestaShop'ta gerçek bir sipariş flow'u
- 3 mesajı tek screenshot'ta yakala
- PrestaShop BO'da Order listesinde yeni sipariş görünür (split-screen ideal)

**Ne dikkat çekecek:** **En kritik screenshot** — "müşteri onaylı sipariş oluşturma" değer önerisi. PrestaShop'taki gerçek order ID görünmesi marketplace review için kritik.

---

### 7. `07-audit-log-table.png`

**Ne göstermeli:** Modül Configure ekranının altında (veya ayrı bir tab'da) "Audit Log" tablosu — son 10-20 satır görünüyor: timestamp, function_slug, status_code, duration_ms.

**Hazırlık:**
- Birkaç gerçek API call yap (Dowaba prod test veya curl)
- `ps_dowaba_audit` tablosu dolsun
- Configure ekranında audit log section görünür hale getir (gerekirse listing view eklenecek)

**Ne dikkat çekecek:** **Güvenlik + şeffaflık** — "her istek loglanıyor, izleyebilirsin".

---

### 8. `08-dowaba-function-list.png`

**Ne göstermeli:** Dowaba panel'de site → Entegrasyonlar → Functions sekmesi. 10 `psm_*` function'ın hepsi aktif (yeşil "Active" badge).

**Hazırlık:**
- Bundle Import sonrası fonksiyonlar otomatik aktif olmuş
- Function listesi: psm_product_search, psm_product_detail, psm_product_compare, psm_stock_check, psm_category_list, psm_order_status, psm_customer_lookup, psm_cart_recover, psm_order_preview, psm_order_confirm
- Hepsi yeşil yanmış halde

**Ne dikkat çekecek:** "Tek import ile 10 function geldi" — kurulum kolaylığı.

---

## Bonus screenshots (opsiyonel ama +conversion)

### 9. `09-multi-channel-overview.png`

Dowaba ana panel dashboard — WhatsApp + IG + TikTok + Mail + Voice + PrestaShop hep birlikte görünüyor. "All-in-one" message'ı.

### 10. `10-prestashop-validator-pass.png`

https://validator.prestashop.com/ üzerinde modül ZIP yüklü, **0 errors / 0 warnings** sonuç. PrestaShop Addons review team için güven.

### 11. `11-pricing-tiers.png`

dowaba.com/pricing sayfası — modül ücretsiz olduğu net görünüyor.

---

## 📷 Çekim Workflow

```bash
# Lokal Docker'ı başlat
cd prestashop/docker && docker compose up -d

# Modül install (PrestaShop CLI veya BO upload)
# BO: http://localhost:8091/admin-dowaba → Modules → Module Manager → Upload

# Tunnel başlat (Dowaba canlı çağrı için)
cloudflared tunnel --url http://localhost:8091 &

# 1-3: Modül config screenshots (lokal PrestaShop)
open http://localhost:8091/admin-dowaba

# 4: Dowaba panel screenshot (prod)
open https://dowaba.com/admin

# 5-6: WhatsApp screenshots (kendi telefonunla veya WhatsApp Web)

# 7-8: Audit log + function list — birkaç gerçek API call yaptıktan sonra

# 10: PrestaShop Validator (zip upload)
open https://validator.prestashop.com/
```

---

## 🎨 Görsel düzenleme önerileri

1. **Watermark koy**: Alt sağ köşede "dowaba.com" logo+text (küçük, %50 opacity)
2. **Hassas veri blur'la**: API key'lerin tam string'i, müşteri telefon/email'leri
3. **Vurgu yap**: Önemli butonları kırmızı/yeşil ok ile işaretle (annotate)
4. **Browser chrome temizle**: Sadece içerik (URL bar dahil edilmesin)
5. **PrestaShop BO breadcrumb göster**: Müşteri "burası BO'nun neresi" anlasın

**Mac'te CleanShot X** veya **Shottr** (free) kullanıyorsan annotate + blur kolay.

---

## ✅ Screenshot Quality Checklist

Her screenshot:
- [ ] 1280x720 veya daha büyük (resize edilmemiş)
- [ ] PNG format
- [ ] Hassas veri YOK (API key plain, müşteri info)
- [ ] Sharp + okunabilir (zoom yapınca yazılar net)
- [ ] Browser bar/devtool YOK
- [ ] Konu net (cluttered değil)
- [ ] Watermark var (logo, alt köşe)
- [ ] PrestaShop UI native theme (custom theme yok)

---

## 📁 Final dizin yapısı

```
prestashop/marketing/screenshots/
├── 01-module-config-overview.png
├── 02-api-key-generated.png
├── 03-manifest-url-copy.png
├── 04-dowaba-bundle-import.png
├── 05-whatsapp-ai-conversation.png
├── 06-order-confirmation-flow.png
├── 07-audit-log-table.png
├── 08-dowaba-function-list.png
├── 09-multi-channel-overview.png      (bonus)
├── 10-prestashop-validator-pass.png   (bonus)
└── 11-pricing-tiers.png               (bonus)
```

---

## 🎯 Conversion İpuçları

| Screenshot | Mesaj |
|---|---|
| `01` Config overview | "Tek ekran kurulum — karmaşık değil" |
| `02` API key generated | "Güvenlik standart — hash + plain bir kez" |
| `03` Manifest copy | "Tek-tıkla kopyalama, kolay UX" |
| `04` Bundle import | "Dowaba paneli ile köprü — sade flow" |
| `05` WhatsApp conv | "Gerçek WhatsApp, gerçek müşteri, gerçek AI" |
| `06` Order confirm | "Gerçek PrestaShop siparişi oluştu — kanıt" |
| `07` Audit log | "Şeffaflık + güvenlik denetim" |
| `08` Function list | "10 fonksiyon, tek import — değer" |
| `10` Validator pass | "PrestaShop Addons standartına uygun" |
