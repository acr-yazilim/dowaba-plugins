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

## Yayın için kalan (kaynak hazır)
- **WooCommerce / OpenCart / Magento**: version bump + zip + GitHub release. (Üçü de **canlı test edildi**, düşük riskli — release'e hazır.)
