# Screenshots Çekim Rehberi — OpenCart Marketplace

Marketplace'te en yüksek conversion için **8 zorunlu screenshot**. Mac'te `CMD+SHIFT+4` ile alıp `marketing/screenshots/` altına kaydet.

**Boyut**: 1280x720 (16:9) önerilir. Marketplace 600x600+ kabul ediyor ama 1280x720 ideal.

**Format**: PNG (lossless, metinler net).

**Naming convention**: `{numara}-{açıklama-kebab-case}.png` (alfabetik sıra önemli)

---

## Çekim Listesi

### 1. `01-setup-wizard-overview.png`

**Ne göstermeli:** Plugin admin'inin ana ekranı — 5-adımlı wizard'ın tamamı görünüyor.

**Çekim yeri:**
```
http://localhost:8080/admin → Extensions → Modules → Dowaba AI → Edit
```

**Hazırlık:**
- Plugin yüklü ve aktif olmalı
- Tüm 5 step görünür şekilde scroll en başta
- Tarayıcı pencere genişliği ~1400px (16:9 oran için)
- Browser zoom 100%

**Ne dikkat çekecek:** "5-step setup" akışı — anlaşılır ve şeffaf.

---

### 2. `02-api-key-generated.png`

**Ne göstermeli:** API key "Yeniden Üret" tıklanıp plain key yeşil banner'da görünüyor.

**Hazırlık:**
- Setup wizard'ı aç
- "🔑 API Anahtarı Yenile" butonuna tıkla
- Yeşil banner çıkar: `opc_xxxxxxxxxxxxxxxxxxxx...`
- Banner görünür haldeyken screenshot

**Önemli:** Plain key gerçek bir prod key olmamalı — demo key uydur veya OC kurulumunu fresh yap.

**Ne dikkat çekecek:** "Plain bir kez gösterilir, sonra hash kalır" güvenlik mesajı.

---

### 3. `03-manifest-url-copy.png`

**Ne göstermeli:** Manifest URL kopyalama hazır + "Kopyalandı" toast.

**Hazırlık:**
- Step 2'deki Manifest URL inputu görünür
- "📋 Kopyala" butonuna basıldıktan sonra (toast görünüyor)

**Ne dikkat çekecek:** Kullanıcı dostu kopyalama UX.

---

### 4. `04-connection-test-success.png`

**Ne göstermeli:** Step 5 "Bağlantı testi" sonucu yeşil — "✅ Bağlantı başarılı".

**Hazırlık:**
- Step 5'teki "🔌 Bağlantı testi" butonuna bas
- Yeşil sonuç kutusu çıkar
- `schema_version=1.0, 10 function bulundu` yazısı görünüyor

**Ne dikkat çekecek:** "Plugin gerçekten çalışıyor" güveni.

---

### 5. `05-dowaba-panel-bundle-import.png`

**Ne göstermeli:** Dowaba paneli `dowaba.com/admin/sites/X/integrations` → Bundle Import dialog.

**Hazırlık:**
- Tarayıcıda dowaba.com paneline gir (kendi hesabınla)
- Siteler → bir site seç → Entegrasyonlar
- "Bundle Import" butonuna bas
- Modal açıldığında manifest URL ve API key inputları görünüyor

**Ne dikkat çekecek:** İki ürün arasındaki köprü — kullanıcı OpenCart bilgisini Dowaba'ya nasıl aktarıyor.

---

### 6. `06-whatsapp-ai-conversation.png`

**Ne göstermeli:** WhatsApp Business hesabında müşteri ile AI arasındaki gerçek konuşma.

**Hazırlık:**
- Dowaba bağlı WhatsApp hesabını telefonda aç
- Kendi numarandan test mesajı at: "iPhone 15 var mı?"
- AI cevap versin
- WhatsApp ekranını telefonda screenshot (PNG)
- Veya Mac'te WhatsApp Web kullan, daha temiz screenshot

**Ne dikkat çekecek:** "Bu plugin gerçekten WhatsApp'a bağlanıyor" kanıt.

**Maskeleme:** Telefon numarası ve müşteri ismini blur'la (privacy).

---

### 7. `07-order-confirmation-flow.png`

**Ne göstermeli:** WhatsApp'ta sipariş onayı flow'u (3 mesaj):
1. AI: "📦 Sipariş özetin: 1× iPhone 15 Pro — 64.999 TL + Kargo 49 TL = 65.048 TL. Onaylıyor musun?"
2. Müşteri: "Evet"
3. AI: "✅ Siparişin oluştu #12345. Ödeme: [link]"

**Hazırlık:**
- Demo mağazada gerçek bir sipariş flow'u
- 3 mesajı tek screenshot'ta yakala

**Ne dikkat çekecek:** **En kritik screenshot** — "müşteri onaylı sipariş oluşturma" değer önerisi.

---

### 8. `08-audit-log-table.png`

**Ne göstermeli:** Plugin admin'indeki "Audit Log" sekmesi — son 10-20 satır görünüyor.

**Hazırlık:**
- Birkaç gerçek API call yap (curl ile veya Dowaba prod test)
- Audit log dolsun
- Refresh'le table doldur

**Ne dikkat çekecek:** **Güvenlik + şeffaflık** — "her istek loglanıyor, izleyebilirsin".

---

## Bonus screenshots (opsiyonel ama +conversion)

### 9. `09-function-list-dowaba.png`

Dowaba panel'de site → Entegrasyonlar → Functions sekmesi. 10 `opc_*` function'ın hepsi aktif (yeşil badge).

### 10. `10-multi-channel-overview.png`

Dowaba ana panel dashboard — WhatsApp + IG + Mail + Voice + OpenCart hep birlikte görünüyor. "All-in-one" message'ı.

### 11. `11-pricing-tiers.png`

dowaba.com/pricing sayfası — plugin ücretsiz olduğu net görünüyor.

---

## 📷 Çekim Workflow

```bash
# Lokal Docker'ı başlat (eğer kapattıysan)
cd opencart/docker && docker compose up -d

# Tunnel başlat (Dowaba canlı çağrı için)
cloudflared tunnel --url http://localhost:8080 &

# 1-4: Plugin admin screenshots (lokal)
open http://localhost:8080/admin

# 5: Dowaba panel screenshot (prod)
open https://dowaba.com/admin

# 6-7: WhatsApp screenshots (kendi telefonunla veya WhatsApp Web)

# 8-9: Audit log + function list — birkaç gerçek API call yaptıktan sonra
```

---

## 🎨 Görsel düzenleme önerileri

1. **Watermark koy**: Alt sağ köşede "dowaba.com" logo+text (küçük, %50 opacity)
2. **Hassas veri blur'la**: API key'lerin tam string'i, müşteri telefon/email'leri
3. **Vurgu yap**: Önemli butonları kırmızı/yeşil ok ile işaretle (annotate)
4. **Browser chrome temizle**: Sadece içerik (URL bar dahil edilmesin)

**Macte CleanShot X** veya **Shottr** (free) kullanıyorsan annotate + blur kolay.

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

---

## 📁 Final dizin yapısı

```
opencart/marketing/screenshots/
├── 01-setup-wizard-overview.png
├── 02-api-key-generated.png
├── 03-manifest-url-copy.png
├── 04-connection-test-success.png
├── 05-dowaba-panel-bundle-import.png
├── 06-whatsapp-ai-conversation.png
├── 07-order-confirmation-flow.png
├── 08-audit-log-table.png
├── 09-function-list-dowaba.png         (bonus)
├── 10-multi-channel-overview.png       (bonus)
└── 11-pricing-tiers.png                (bonus)
```
