# Connect to DoWaba — Eklenti Test Durumu

> Tek-tık **"Connect to DoWaba"** deep-link özelliğinin her e-ticaret eklentisindeki durumu — tek bakışta.
> Detay: ilgili eklentinin `CHANGELOG.md`'si. Hedef sayfa: `dowaba.com/admin/connect?platform=<X>&manifest=…`
> DoWaba `/connect` (ConnectStoreView) **canlıda** (server2+3). Buton = eklentide; karşılayan sayfa = dowaba panelinde.

| Eklenti | Connect butonu | Canlı test | Tarih | Release |
|---|---|---|---|---|
| **PrestaShop** | ✅ + tek-tık (`#k=` key fragment) | ✅ **uçtan uca** (10 fonksiyon import) | 2026-06-01 | `prestashop-v0.2.9` ✓ |
| **WooCommerce** | ✅ + tek-tık (`#k=` key fragment) | ✅ **uçtan uca** (kapak+galeri+10 fn, sipariş #14) | 2026-06-01 | `woocommerce-v0.3.1` ✓ |
| **OpenCart** (OC3 + OC4) | ✅ (manifest pre-filled) | ✅ **uçtan uca** (kapak+galeri+10 fn, sipariş #10; 🐛 galeri fix) | 2026-06-01 | `opencart-v0.2.22` ✓ |
| **Magento** | ✅ (manifest pre-filled) | ✅ **uçtan uca** (kapak+galeri+10 fn, 2.4.7) | 2026-06-01 | `magento-v0.1.1` ✓ |
| Shopify | — (OAuth modeli) | yok — zaten tek-tık "authorize" | — | — |
| İkas | — (OAuth modeli) | yok — zaten tek-tık "authorize" | — | — |

**Özet:** Bundle-Import eklentilerinin (Presta/Woo/OC/Magento) hepsine deep-link butonu eklendi. **4'ü de CANLI uçtan uca test edildi** (kapak görseli + galeri + 10 fonksiyon; 4 farklı framework: PHP/WP, Twig, Magento DI) ve **4'ü de yayınlandı** (Presta v0.2.9, Woo v0.3.1, OC v0.2.22, Magento v0.1.1). OpenCart'ta galeri görsel bug'ı bulunup düzeltildi. Shopify/İkas OAuth olduğu için zaten tek-tık.

## Lokal test ortamı (Docker)
| Eklenti | URL | Admin |
|---|---|---|
| PrestaShop 8.1 | http://localhost:8091/admin-dowaba | admin@dowaba.local / admin123 |
| WooCommerce (WP 6.7) | http://localhost:8090/wp-admin | admin / DowabaTest-2026 |
| OpenCart 4.0.2.3 | http://localhost:8080/admin | admin / DowabaTest-2026 |
| Magento 2.4.7 | http://localhost:8087/admin (php -S) | admin / Dowaba12345 |

## DoWaba test hesabı (validator + genel test)
- `prestashop-reviewer@dowaba.com` / `PrestaReview-2026` — **2FA bypass'lı** (mailbox erişimi gerekmez), "Reviewer Test Store" sitesi hazır.

## Ürün görseli + fonksiyon testi (2026-06-01)

> Soru: "kapak fotoğrafı (ilk görsel) gemini sorunca geliyor mu, sonra müşteri isterse detaylı/galeri görselleri geliyor mu, tüm fonksiyonlar çalışıyor mu?"

| Eklenti | Kapak (search) | Galeri (detail) | 10 fonksiyon | Yöntem |
|---|---|---|---|---|
| **OpenCart** (OC4 4.0.2.3) | ✅ `thumb`/`image` | ✅ **5 görsel** (bug bulundu+düzeltildi) | ✅ 10/10 + gerçek sipariş #10 | **CANLI** (19 demo ürün) |
| **Magento** (2.4.7) | ✅ `thumb`/`image` | ✅ **3 görsel** ("Test bekliyor"→doğrulandı) | ✅ 10/10 | **CANLI** (seed ürün) |
| **PrestaShop** (8.1) | ✅ `cover_image` | ✅ **2 görsel** | ✅ 10/10 | **CANLI** (19 demo ürün) |
| **WooCommerce** (9.5.1) | ✅ `cover`/`thumb` | ✅ **3 görsel** | ✅ 10/10 + gerçek sipariş #14 | **CANLI** (seed ürün) |

**4/4 CANLI test edildi** — her birinde product_search kapak görseli + product_detail galeri görselleri + 10
fonksiyon (read'ler OK, order/customer IDOR guard, write'lar scope-guard ile default-deny; scope açılınca
order_preview→order_confirm gerçek sipariş). 4 farklı framework: OpenCart (PHP/Twig), Magento (DI/getMediaGalleryImages),
PrestaShop (Product::getCover/getImages), WooCommerce (WP REST/get_gallery_image_ids).

**🐛 Bulunan + düzeltilen bug (OpenCart OC3+OC4):** `product_detail` galeri görselleri OC4'te **HİÇ
dönmüyordu** (`gallery_count: 0`). Kök neden: galeri `method_exists($model,'getProductImages')` guard'lı;
OpenCart model'i bir **Proxy** (`__get` magic) olduğu için `method_exists` daima `false` → galeri sessizce
boştu. + OC4 metot adı `getImages`'e rename edilmiş. Fix: guard kaldırıldı, doğrudan çağrı + sürüm fallback.
Detay: [opencart/CHANGELOG.md](./opencart/CHANGELOG.md). **Diğer 3 plugin'de bu bug YOK** — doğrudan framework
çağrısı kullanırlar (Proxy/method_exists pattern'i yok), canlı testte galeri hepsinde döndü.

## GitHub release durumu — 4/4 YAYINLANDI ✓ (2026-06-01)
- **OpenCart** `opencart-v0.2.22` (galeri bug fix dahil) ✓
- **Magento** `magento-v0.1.1` ✓
- **WooCommerce** `woocommerce-v0.3.1` ✓
- **PrestaShop** `prestashop-v0.2.9` ✓

## Marketplace / mağaza başvuru durumu (GitHub'dan AYRI)
- **PrestaShop Addons** (`addons.prestashop.com/.../97927`): v0.2.7 docs eksikliğinden reddedilmişti → **v0.2.9 zip + entegrasyon rehberi PDF** ile yeniden gönderilecek (upload formu hazırlandı; Aydın zip attach + submit yapacak). Compatibility 1.7.0 → 9.1.3.
- **WooCommerce** → doğru kanal **WordPress.org plugin directory** (ücretsiz, connector'a uygun). WooCommerce.com Marketplace ücretli/komisyonlu + dış-SaaS upsell yasağı nedeniyle bu plugin'e UYMUYOR.
- **OpenCart Marketplace** + **Adobe Commerce Marketplace**: opsiyonel, ileride.
