# Lokal Magento test ortamı — runbook (2026-05-30 canlı doğrulandı ✅)

Magento'yu lokalde kurmak OpenCart/WooCommerce'ten zordur. İki bilinen engel + çözümleri:

| Engel | Çözüm |
|---|---|
| `bitnami/magento` imajı **kaldırıldı** (2025 deprecation) | Kullanma — yok |
| `composer create-project magento/...` **Adobe auth key** ister | **Public GitHub source** kullan (self-contained, key gerektirmez) |

> Bu runbook gerçekten koşuldu: **e2e 7/0 PASS**, gerçek sipariş `#000000001` oluştu, manifest + 10 fonksiyon + write flow + replay + IDOR guard + audit log canlı doğrulandı. PHP host'ta 8.1–8.3 + tüm extension'lar yeterli.

## 1. DB + Search (container)

```bash
cd docker/ && docker compose up -d
# MySQL 8.0 → 127.0.0.1:3309 | OpenSearch 2.12 → 127.0.0.1:9201
```

## 2. Magento source (public GitHub — auth YOK)

```bash
INSTALL=~/Documents/magento-test
git clone --depth 1 -b 2.4.7 https://github.com/magento/magento2.git "$INSTALL"
cd "$INSTALL"
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --no-interaction
```

> `magento/magento2` repo `app/code/Magento`'da 220 modülü vendor'lar; `composer.json` `repo.magento.com` deklare etmez → 6 küçük util paketi packagist'ten gelir, **auth gerekmez**. Klon ~344MB, vendor ~149MB.

## 3. setup:install (DB + OpenSearch container'larına)

```bash
php -d memory_limit=-1 bin/magento setup:install \
  --base-url=http://localhost:8087/ \
  --db-host=127.0.0.1:3309 --db-name=magento --db-user=magento --db-password=magento \
  --admin-firstname=Aydin --admin-lastname=Acar --admin-email=admin@dowaba.local \
  --admin-user=admin --admin-password=Dowaba12345 \
  --language=en_US --currency=TRY --timezone=Europe/Istanbul --use-rewrites=1 \
  --search-engine=opensearch --opensearch-host=127.0.0.1 --opensearch-port=9201 \
  --opensearch-index-prefix=magento2 --opensearch-enable-auth=0 --backend-frontname=admin
```

> **MySQL 8 trigger uyarısı** (`You do not have the SUPER privilege ... CREATE TRIGGER`) zararsız (indexer "update on save" modunda kalır). compose `--log_bin_trust_function_creators=1` ile başlatır → uyarı çıkmaz. Mevcut DB'de tek seferlik: `docker exec dwb-mag-mysql mysql -uroot -pmagento_root -e "SET GLOBAL log_bin_trust_function_creators=1"`.

## 4. Modülü ekle + etkinleştir

```bash
mkdir -p app/code/Dowaba
cp -a /path/to/dowaba-plugins/magento/src/Dowaba/AiConnector app/code/Dowaba/AiConnector
php -d memory_limit=-1 bin/magento deploy:mode:set developer
php -d memory_limit=-1 bin/magento module:enable Dowaba_AiConnector
php -d memory_limit=-1 bin/magento setup:upgrade     # dowaba_ai_audit tablosunu declarative schema ile oluşturur
php -d memory_limit=-1 bin/magento cache:flush
```

## 5. Serve (Magento built-in PHP server)

```bash
php -d memory_limit=1G -S 127.0.0.1:8087 -t pub phpserver/router.php &
```

> ⚠️ **base_url redirect:** Magento `127.0.0.1`'i base_url `localhost`'a 302 yönlendirir. Test ederken **`http://localhost:8087`** kullan (`127.0.0.1` değil) — yoksa 302 alırsın.

## 6. Config + test API key (admin wizard yerine hızlı yol)

```bash
KEY="mgm_$(openssl rand -hex 32)"
php -r '$k=getenv("KEY");$p=new PDO("mysql:host=127.0.0.1;port=3309;dbname=magento","magento","magento");
$s=$p->prepare("INSERT INTO core_config_data(scope,scope_id,path,value)VALUES(\"default\",0,?,?)ON DUPLICATE KEY UPDATE value=VALUES(value)");
foreach([["dowaba_ai/general/status","1"],["dowaba_ai/scope/read","1"],["dowaba_ai/scope/write","1"],
["dowaba_ai/general/api_key_hash",hash("sha256",$k)],["dowaba_ai/general/api_key_prefix",substr($k,0,12)]] as $r)$s->execute($r);' KEY="$KEY"
php bin/magento cache:clean config
echo "$KEY"   # Bearer token
```

## 7. Smoke + senaryo testi

```bash
BASE_URL=http://localhost:8087 API_KEY=$KEY bash ../dowaba-plugins/magento/test/e2e.sh
# manifest 200 / auth 401 / bogus 400 / product_search 200
```

## 8. Örnek veri

`dwb-seed.php` ile (bkz repo test runbook): 4 simple product + stok. Kategori yoksa `categories` 0 döner (boş kurulumda alt kategori yok — normal).

## Doğrulanmış davranışlar / tuzaklar

- **`show_out_of_stock`** — modül frontend koleksiyonu kullanır → mağazanın `cataloginventory/options/show_out_of_stock` ayarına SAYGI duyar. 0 (default) → stoksuz ürün aramada gizli (storefront ile tutarlı); 1 → görünür (stok=0). **Bug değil.**
- **Preview vs gerçek total** — `order_preview` basit kargo hesabı (≥1000 ücretsiz) kullanır; gerçek Quote Magento flatrate'ini ($5/adet) uygular → küçük fark olabilir (v0.1 sınırı, [CHANGELOG](../CHANGELOG.md)).
- **order_confirm stok düşer** — 2 adetlik sipariş sonrası ürün stoğu 25→23 (inventory decrement doğrulandı).
- **developer mode** ilk istekte DI üretir (yavaş ~2s), sonrası hızlı. `php -S` tek-thread — sıralı curl için yeterli.

## Cloudflare tunnel (Dowaba prod → lokal)

```bash
cloudflared tunnel --url http://localhost:8087   # manifest HTTP_HOST'tan otomatik adapt olur
```
