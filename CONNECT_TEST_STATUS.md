# Connect to DoWaba — Eklenti Test Durumu

> Tek-tık **"Connect to DoWaba"** deep-link özelliğinin her e-ticaret eklentisindeki durumu — tek bakışta.
> Detay: ilgili eklentinin `CHANGELOG.md`'si. Hedef sayfa: `dowaba.com/admin/connect?platform=<X>&manifest=…`
> DoWaba `/connect` (ConnectStoreView) **canlıda** (server2+3). Buton = eklentide; karşılayan sayfa = dowaba panelinde.

| Eklenti | Connect butonu | Canlı test | Tarih | Release |
|---|---|---|---|---|
| **PrestaShop** | ✅ + tek-tık (`#k=` key fragment) | ✅ **uçtan uca** (10 fonksiyon import) | 2026-06-01 | `prestashop-v0.2.9` ✓ |
| **WooCommerce** | ✅ + tek-tık (`#k=` key fragment) | ✅ render + href doğru | 2026-06-01 | — (kaynak, release kaldı) |
| **OpenCart** (OC3 + OC4) | ✅ (manifest pre-filled) | ✅ render + href doğru (OC4) | 2026-06-01 | — (kaynak, release kaldı) |
| **Magento** | ✅ (manifest pre-filled) | ✅ render + href doğru (2.4.7) | 2026-06-01 | — (kaynak, release kaldı) |
| Shopify | — (OAuth modeli) | yok — zaten tek-tık "authorize" | — | — |
| İkas | — (OAuth modeli) | yok — zaten tek-tık "authorize" | — | — |

**Özet:** Bundle-Import eklentilerinin (Presta/Woo/OC/Magento) hepsine deep-link butonu eklendi. **4'ü de CANLI test edildi** (Presta uçtan uca import; Woo + OC4 + Magento 2.4.7 render+href — 4 farklı framework: PHP/WP, Twig, Magento). Shopify/İkas OAuth olduğu için zaten tek-tık.

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

## Yayın için kalan (kaynak hazır)
- **OpenCart**: galeri bug fix dahil → version bump + zip + release (fix önemli, öncelikli).
- **WooCommerce / Magento**: version bump + zip + GitHub release. (Canlı test edildi, düşük riskli.)
