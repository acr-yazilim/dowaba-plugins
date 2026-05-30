# PrestaShop Addons — Marketplace & Validation (tek otorite)

> **Bu dosya, PrestaShop modülünün addons.prestashop.com Marketplace'teki yayın/validation süreci için TEK
> OTORİTE operasyon dokümanıdır.** Submission durumu, fee, validation aşamaları, validator compliance ve
> panel izleme akışı burada toplanır. **PrestaShop submission/validation'a dair iş yapmadan önce burayı oku.**
>
> Tamamlayıcı dosyalar (içerik tekrarı yok, referans):
> - **Submission ÖNCESİ adım-adım hazırlık checklist'i** → [marketing/SUBMISSION_CHECKLIST.md](./marketing/SUBMISSION_CHECKLIST.md)
> - **Plugin mimarisi / 10 function / manifest / auth (cross-plugin)** → [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md)
> - **Kurulum + kullanıcı dokümanı** → [README.md](./README.md) · **Sürüm geçmişi** → [CHANGELOG.md](./CHANGELOG.md)

Son güncelleme: **2026-05-30**

---

## 1. Canlı durum (snapshot — 2026-05-30)

Marketplace partner panelinden okunan güncel durum. Sayfa: `https://addons.prestashop.com/sellers/en/products/97927`

| Alan | Değer |
|---|---|
| **Ürün ID** | `97927` |
| **Ürün adı** | DoWaba AI — Sell on WhatsApp, Instagram & TikTok |
| **Tip** | Module · One-time purchase |
| **Technical validation** | 🟡 **Reviewing** — "No feedback yet from the technical validation team" |
| **Product sheet** | 🟡 **Reviewing** |
| **Status** | **Offline** (validation bitene kadar normal) |
| **Submit edilen versiyon** | **0.2.7** (panel "Versions" tablosu), yükleme **26/05/2026** |
| **Beyan edilen uyumluluk** | 1.7.0.0 – 9.1.3 |
| **Yıllık ürün ücreti (99 €)** | ✅ **Paid** — sonraki tahsilat 26/05/2027 ("Still active until may 26, 2027") |
| **Messages kutusu** | 📭 **Boş** — PrestaShop'tan aktif talep/uyarı YOK |

**Fiyatlandırma (panel "Details"):** Gross product price 49,99 € · Business care 20 €/yıl · Online price 69,99 €.

> ⚠️ **Not — README ile sürüm farkı:** [README.md](./README.md) direkt indirme linki **v0.2.8** gösteriyor ama
> Marketplace'e submit edilen versiyon **0.2.7**. GitHub'da daha yeni release olması Marketplace'i otomatik
> güncellemez — Marketplace ayrı bir submission ister (bkz § 6).

---

## 2. Validation süreci (resmi kurallar)

PrestaShop Addons, 29 Nisan 2024'ten beri tüm modüllere kapsamlı kalite kontrolü uygular. Kaynak:
[Validation Process for your addons](https://helpcenter-partners.prestashop.com/hc/en-us/articles/18497178944914-Validation-Process-for-your-addons).

### 2.1 İki aşama + süreler

| Aşama | Ne kontrol edilir | Ortalama süre |
|---|---|---|
| **Product sheet** | Açıklama, görseller, kategori, fiyat, marketing kalitesi | **< 1 hafta** |
| **Technical validation** | Modül kodu okunur, validator hataları, install/uninstall, güvenlik | **~2 hafta** |

> Panel'deki **Validation center** (`/sellers/en/products/validation`) bu süreleri birebir beyan eder:
> *"On average the validation team takes under one week for a product sheet and two weeks for a technical
> validation."* PrestaShop, OpenCart'tan **daha yavaş** onaylar — 2-4 hafta normaldir.

### 2.2 Yıllık 99 € ürün ücreti (zorunlu)

- Her satılan modül/tema için **yıllık 99 € (HT)**, kart otomatik tahsilat (anniversary date).
- **Ödenmezse ürün satışa konulamaz** ("you will not be able to put any of your product back on sale until you pay").
- Yıllık ücret, o yıl içinde **sınırsız submission** kapsar — tek limit **günde 3 submission**.
- Panel: ürün sayfası → **"Product fees"** sekmesi → ödeme durumu + sonraki tahsilat tarihi + "Cancel annual fees".
  İptal edersen ürün **deaktive** olur ama katalogda kalır.

### 2.3 Red olursa ne olur

- Reddedilirse **detaylı feedback raporu** gelir (panel + Messages). Düzelt → yeniden submit.
- Yıllık ücret kapsamında **istediğin kadar deneme** yapabilirsin (günde 3 limiti dışında ek ücret yok).
- Şu an feedback **yok** → süreç sorunsuz ilerliyor, henüz reddedilmedik.

---

## 3. Panel izleme haritası (durum nereden okunur)

Bir "ne durumda?" sorusunda şu 3 yere bak:

| Sekme / sayfa | URL | Ne gösterir |
|---|---|---|
| **Ürün → Details** | `/sellers/en/products/97927` | Technical validation + Product sheet status, 9.0.0 uyarısı, fiyat, ürün key |
| **Ürün → Product fees** | aynı sayfa, "Product fees" tab | 99 € yıllık ücret ödeme durumu (Paid/—) + tahsilat tarihi |
| **Validation center** | `/sellers/en/products/validation` | Tüm submission'ların durumu + "VIEW FEEDBACK" + resmi süre beyanı |
| **Messages** | `/en/seller-contact.php` | PrestaShop ekibinin gönderdiği mesajlar (red/eksik bildirimi buraya düşer) |

**Kural:** Messages boş + Validation center "Reviewing" + fee "Paid" ise → **eksik yok, beklemek doğru.**
Red/eksik olsaydı Messages'a feedback raporu düşerdi.

---

## 4. PrestaShop 9.0 uyumluluk meselesi

Ürün sayfasında kırmızı X'li uyarı: **"Not available on PrestaShop 9.0.0 — the declared compatibility range
for this product does not include PrestaShop 9.0.0"** (uyumluluk tablosu 1.7.0.0–9.1.3 yazsa bile).

### Kök neden

- Modül kodu **9.0.0'ı kapsıyor**: [src/dowaba_ai/dowaba_ai.php](./src/dowaba_ai/dowaba_ai.php) →
  `ps_versions_compliancy = ['min' => '1.7.0', 'max' => '9.1.99']`.
- Çelişki, Addons platformunun sürümleri **tek tek** (discrete) tutmasından kaynaklanır: range "1.7.0.0–9.1.3"
  görünse de, bu versiyona **beyan edilen sürüm listesinde 9.0.0 işaretli değil** (9.0.1+ / 9.1.x var).

### Önem derecesi — DÜŞÜK, bloker DEĞİL

- Mevcut "Reviewing" sürecini **durdurmaz**. Sadece "bu zip, 9.0.0 mağazalarda listelenmez" demektir.
- 9.0.0, PS 9 serisinin ilk sürümü; çoğu kullanıcı 9.0.1+ / 9.1.x'te. Kayıp küçük.

### Resmi kural (9.0 sayfası)

[PRESTASHOP VERSION 9.0](https://helpcenter-partners.prestashop.com/hc/en-us/articles/23013341414034-PRESTASHOP-VERSION-9-0):
*"Even if your addon is already compatible, you must resubmit it to be reviewed by our validation team. Once
validated, the compatibility mention with the latest version will be displayed on the product sheet."*
Yani 9.0 uyumluluğunun rozeti için validator + resubmit gerekir.

### Aksiyon (acil değil — review bitince)

1. [validator.prestashop.com](https://validator.prestashop.com/) ile zip'i 9.0 için test et (0 error hedef).
2. Yeni versiyon submit ederken 9.0.0'ı sürüm listesine ekle.
3. ⚠️ **Mevcut review bitmeden yeni versiyon atma** → review'ı **sıfırlar** (bkz § 6).

---

## 5. PrestaShop Validator Compliance (zorunlu — Addons reddetme #1 sebebi)

[validator.prestashop.com](https://validator.prestashop.com/) → **tüm kategoriler 0 error** olmalı, yoksa
Addons reddeder. Validator'ı geçmenin 3 adımı: (1) dev environment + display_errors, (2) PrestaShop dev
standartları, (3) Validator tool'a zip/GitHub URL yükle ve raporu sıfırla.
([Validator dokümantasyonu](https://validator.prestashop.com/documentation))

> Bu bölüm daha önce [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md) § 8'deydi; PrestaShop tek otoritesi
> burası olduğu için buraya taşındı. Dev guide artık buraya referans veriyor.

### 5.1 Security — en sık fail

**Her klasörde `index.php`** (directory listing engeli):

```php
<?php
/**
 * Dowaba AI Integration for PrestaShop
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Location: ../");
exit;
```

**Root `.htaccess`** (directory listing + dosya erişim engeli):

```apache
Options -Indexes
<FilesMatch "\.(php|inc|tpl|sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>
<Files "index.php">
    Order allow,deny
    Allow from all
</Files>
```

### 5.2 Structure — her PHP dosyasının başı

```php
<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
```

### 5.3 Licenses — her PHP dosyasının file doc comment'i

```php
/**
 * Dowaba AI Integration for PrestaShop — <module description>
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
```

Eksik field → validator "Missing @author tag in file comment" verir.

### 5.4 Translations / Standards

- `$this->l('...')` runtime translate, OK.
- **PSR-12**: spacing, brace position, control structure brace'leri (`if () { ... }` zorunlu, tek-satır if yasak).

### 5.5 PrestaShop-özel SQL tuzağı

- **Validator strict SQL_MODE**: INSERT'lerde tüm NOT NULL kolonlar dolu olmalı; gerekirse
  `SET SESSION sql_mode=''` ile gevşet. (Eskiden [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md) § 15 #10.)

### 5.6 Dosya yapısı (zip kökü = `dowaba_ai/`)

```
dowaba_ai/
├── dowaba_ai.php          # Main module class (ps_versions_compliancy burada)
├── logo.png               # 32×32 module icon
├── index.php              # ⚠️ her klasörde zorunlu (§5.1)
├── .htaccess              # ⚠️ root (§5.1)
├── classes/{index.php, Auth.php, ScopeGuard.php, OrderPreview.php, AuditLogger.php}
├── controllers/{index.php, admin/index.php, front/{index.php, manifest.php, api.php}}
├── views/{index.php, templates/{index.php, hook/index.php}}
├── translations/index.php
└── sql/index.php
```

> Bunun cross-plugin (OC/Woo/Shopify/İkas) karşılığı + manifest/auth/scope mimarisi
> [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md) § 7 ve § 5'te.

---

## 6. Yeni versiyon submit etme kuralları

- Her submission **yeni bir review** başlatır. **Devam eden bir "Reviewing" varken yeni zip atma** → mevcut
  inceleme büyük ihtimalle sıfırlanır, 2 haftalık süre baştan işler.
- Marketplace, GitHub release'lerini **otomatik takip etmez**. Yeni sürüm (örn. 0.2.8) Marketplace'te
  görünmesi için partner panelden **manuel submit** gerekir ("Versions → Upload a new version").
- Aynı `major.minor.patch` içinde breaking olmayan re-upload kabul edilir; günde max 3 submission.
- Sürüm semantiği: `0.1.x` beta · `0.2.x` production-ready · `1.0.0` = approved + 100+ download.

---

## 7. Troubleshooting

| Belirti | Olası neden / kontrol |
|---|---|
| "Uzun süredir Reviewing" | 2 hafta ortalamayı doldurmadıysa **normal**. Messages boşsa eksik yok, bekle. |
| Ürün "Offline" | Validation bitene kadar normal. Approve sonrası online olur. |
| "Not available on 9.0.0" uyarısı | Bloker değil (§ 4). Beyan edilen sürüm listesi 9.0.0'ı içermiyor. |
| Fee "—" / ödenmemiş | 99 € yıllık ücret ödenmeden ürün satışa çıkamaz (§ 2.2). |
| Red geldi | Messages'taki feedback raporunu oku → düzelt → resubmit (günde 3 limit). |
| 2 haftadan çok Reviewing + ses yok | **Contact us** (`/en/contact-form.php`) veya `contact@prestashop.com`'dan kibar durum sorusu. |
| Validator "missing hook" | `dowaba_ai.php` `install()` içindeki `registerHook()` kontrol. |

---

## 8. Referanslar

- **Submission öncesi hazırlık (adım-adım)** → [marketing/SUBMISSION_CHECKLIST.md](./marketing/SUBMISSION_CHECKLIST.md)
- **Marketplace listing metinleri (TR/EN)** → [marketing/MARKETPLACE_LISTING.md](./marketing/MARKETPLACE_LISTING.md) · [..._EN.md](./marketing/MARKETPLACE_LISTING_EN.md)
- **Privacy / GDPR** → [marketing/PRIVACY.md](./marketing/PRIVACY.md) · **Screenshots rehberi** → [marketing/SCREENSHOTS.md](./marketing/SCREENSHOTS.md)
- **Plugin mimarisi (cross-plugin)** → [../PLUGIN_DEV_GUIDE.md](../PLUGIN_DEV_GUIDE.md)
- **Kurulum + sürümler** → [README.md](./README.md) · [CHANGELOG.md](./CHANGELOG.md)

**Resmi PrestaShop linkleri:**
- Partner panel: https://addons.prestashop.com/sellers/en/products/97927
- Validation Process: https://helpcenter-partners.prestashop.com/hc/en-us/articles/18497178944914-Validation-Process-for-your-addons
- PrestaShop 9.0 (resubmit kuralı): https://helpcenter-partners.prestashop.com/hc/en-us/articles/23013341414034-PRESTASHOP-VERSION-9-0
- Module Validator: https://validator.prestashop.com/ · dokümantasyon: https://validator.prestashop.com/documentation
- Developer docs: https://devdocs.prestashop-project.org/
- Resmi forum (modules): https://www.prestashop.com/forums/forum/192-modules/
