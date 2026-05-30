# Lessons Learned — DoWaba Plugin Ecosystem

> Bu dosya **2026-05-23** bir günlük sprint'in retrospective'i. OpenCart 3.x + 4.x ve WooCommerce plugin'leri sıfırdan canlı yayına alındı. Bundan sonra eklenecek her platform (Shopify, İkas, PrestaShop, Magento, Ticimax) için **referans dokümanı**.

---

## 📅 Timeline

| Saat | Aşama | Çıktı |
|---|---|---|
| **T+0h** | Plan + repo init | `dowaba-plugins/` umbrella, OpenCart 4.x iskelet |
| **T+1h** | Faz 1: OCMOD + manifest + 8 read function | 1.230 satır PHP+Twig |
| **T+2h** | Faz 2-3: Auth + ScopeGuard + Audit + OrderPreview | Library katmanı |
| **T+3h** | Faz 4: Lokal Docker e2e (OC 4.0.2.3) | 5 senaryo PASS |
| **T+4h** | Faz 5: v0.1.0 GH Release + Cloudflare tunnel + Dowaba prod canlı | Sipariş #9 |
| **T+5h** | v0.1.1, v0.1.2 patch'ler | 4 kritik bug fix |
| **T+6h** | v0.2.0 OC 3.x dual support (port) | OC 3.0.3.9 docker + 2 ayrı zip |
| **T+7h** | v0.2.1 OC3 schema fix + v0.2.2 Cloud compat | Sipariş #16 |
| **T+8h** | Marketplace submission (banner + screenshot + form) | **Live ID 48534** |
| **T+9h** | Repo public + WooCommerce plugin sıfırdan | WC v0.1.0 |
| **T+10h** | WooCommerce canlı Dowaba prod entegrasyon | WC Order #16 (64999 USD) |

**Toplam**: ~10 saat dolu çalışma. 2 platform × 3 paket = **6 release**, **9 gerçek sipariş** AI üzerinden DB'ye yazıldı.

---

## 🏆 Ulaşılan Hedefler

| Hedef | Sonuç |
|---|---|
| OpenCart 4.x plugin | ✅ v0.2.2 |
| OpenCart 3.x plugin | ✅ v0.2.2 (dual support aynı release) |
| **OpenCart Marketplace LIVE** | ✅ [extension_id=48534](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48534) |
| WooCommerce plugin | ✅ v0.1.0 |
| GitHub repo public | ✅ https://github.com/rdtvaacar/dowaba-plugins |
| WordPress.org Plugin Directory | ⏳ Hesap onayı bekliyor |
| Dowaba prod canlı entegrasyon | ✅ Her iki platform |
| Documentation (TR + EN) | ✅ README + marketing klasörü |
| Cloud Marketplace uyumluluk | ✅ install.xml wrapper eklendi |

---

## 🧠 Mimari Pattern — Her platforma reuse edilebilir

OpenCart için bulduğumuz mimari WooCommerce'e %80 port edildi. **Bu pattern her platform için referans olsun**:

### 1. Dosya yapısı
```
{platform}/
├─ src/{platform-version}/      ← OC3+OC4 dual için ayrı klasör
│  ├─ install.{xml,json}        ← Platform meta
│  └─ upload/ (veya kök)
│     ├─ admin/                  ← Settings page + AJAX
│     │  ├─ controller
│     │  ├─ view (twig/.tpl/.vue)
│     │  └─ language (i18n)
│     ├─ catalog/                ← REST API
│     │  ├─ manifest             ← .well-known/dowaba-bundle.json
│     │  └─ api                  ← 10 function endpoint
│     └─ system/library/         ← Auth, Scope, Audit, OrderPreview
├─ docker/                      ← Lokal test ortamı
├─ marketing/                   ← Screenshot + listing copy + banner
├─ build.sh                     ← Platform-spesifik paketleyici
└─ .github/workflows/           ← Tag push → auto release
```

### 2. 10 standart AI function

| Slug | Tür | Açıklama |
|---|---|---|
| `opc_product_search` | read | Ad/SKU/kategoriye göre ürün listele |
| `opc_product_detail` | read | Tek ürün tam bilgi |
| `opc_product_compare` | read | 2-3 ürün yan yana karşılaştır |
| `opc_stock_check` | read | Stok adedi |
| `opc_category_list` | read | Kategori ağacı |
| `opc_order_status` | read | Email match ile sipariş takibi |
| `opc_customer_lookup` | read | Phone/email ile müşteri profili (KVKK) |
| `opc_cart_recover` | read | Sepet hatırlatma link |
| `opc_order_preview` | **write** | Sipariş özet (müşteri onayı öncesi) |
| `opc_order_confirm` | **write** | Sipariş oluştur (replay-protected) |

> 💡 Slug'lar **opc_*** prefix'inde — `op_e_n_c_art` kalıntısı. Yeni platformlar için **opc_** prefix korunsun (Dowaba HttpHandler tek slug pattern bekliyor). Veya prefix'siz generic: `dwb_product_search`. Karar v1.0'da verilir.

### 3. Auth + güvenlik katmanı (her platform için aynı)

| Katman | Detay |
|---|---|
| **Bearer Token** | `opc_` + 64 hex char, SHA-256 hashed in DB |
| **IP Whitelist** | Opsiyonel, Dowaba prod IP: `178.105.68.170, 49.13.120.112` |
| **Scope Guard** | `read` default ON, `write` default OFF (anti prompt-injection) |
| **Order Confirmation** | 2-step preview → "yes" → confirm (replay-protected) |
| **Audit Log** | 30-day retention, viewable in admin |
| **SSRF Guard** | Dowaba HttpHandler tarafında |

### 4. Manifest format (DoWaba Bundle Import için)

```json
{
  "schema_version": "1.0",
  "name": "Platform — {{store_name}}",
  "plugin_version": "X.Y.Z",
  "platform": "opencart|opencart3|woocommerce|...",
  "connection": {
    "type": "http_api",
    "base_url": "https://store.com/...",
    "auth_type": "bearer",
    "allowed_hosts": ["store.com"]
  },
  "functions": [
    {
      "slug": "opc_product_search",
      "name": "...",
      "description": "...",
      "auto_activate": true,
      "scope": "read|write",
      "parameters": { "type": "object", "properties": {...}, "required": [...] },
      "http_config": {
        "method": "GET|POST",
        "url_template": "{{connection.base_url}}/...",
        "query_template": {...},
        "body_template": {...},
        "timeout_ms": 5000,
        "response": { "data_path": "data", "fields": [...] }
      }
    }
  ]
}
```

---

## 🐛 KRİTİK BUG'LAR + FIX'LERİ — Her platforma uyarla

Bunlar **her yeni platform'da yine olabilecek bug'lar** — önceden hazırlıklı ol.

### Bug #1: Cache miss `[]` vs `null` (replay protection broken)

**Bulunduğu yer**: OrderPreview cache (OpenCart File cache backend).

**Sorun**: `$cache->get($key)` cache miss durumunda `null` yerine `[]` (boş array) döndürüyor. Bizim `is_array($value)` check'i geçti → consume sonsuz aynı boş array'i döndürdü → **aynı preview_id ile sınırsız order yaratılabilir**.

**Fix pattern**:
```php
if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) {
    return null;
}
```

**Her platforma uyarla**: Cache backend'in cache miss davranışını TEST ET. WordPress Transients API doğru `null` döndürüyor ama OpenCart File cache yapmadı. Magento/Shopify/PrestaShop için aynı sanity check'i koru.

### Bug #2: HTTP status code yutuluyor

**Bulunduğu yer**: OC4 Response sınıfı `addHeader(string)` ile status code numeric param eksik.

**Sorun**: `response->addHeader('HTTP/1.1 410 Gone')` yaparken status code 200 olarak kaldı. AI debugging zor.

**Fix pattern**: 3 mekanizma birlikte
```php
http_response_code($status);                          // PHP native
if (!headers_sent()) header("HTTP/1.1 $status $text", true, $status);  // override
$response->addHeader("HTTP/1.1 $status $text");       // framework buffer
```

**Her platforma uyarla**: Framework'ün response status code mekanizmasını TEST ET. WordPress'te `WP_REST_Response` doğru çalışıyor (status param native), Magento için ayrı kontrol gerek.

### Bug #3: Manifest base_url statik

**Bulunduğu yer**: OpenCart `$config->get('config_url')` install zamanı URL'i.

**Sorun**: Cloudflare tunnel / ngrok / reverse proxy ardındaki kurulumlarda manifest tunnel URL'i ile veriliyor ama API çağrıları localhost'a gidiyor → fail.

**Fix pattern**: Request host'undan dinamik resolve
```php
private function resolve_base_url() {
  $override = trim((string) get_option('manifest_base_url', ''));
  if ($override !== '') return rtrim($override, '/');

  $fwd_host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
  if ($fwd_host) return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https') . '://' . $fwd_host;

  $host = $_SERVER['HTTP_HOST'] ?? '';
  if ($host) return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $host;

  return rtrim($fallback_url, '/');
}
```

**Her platforma uyarla**: WordPress `home_url()`, Shopify `request.headers['host']`, vs.

### Bug #4: OC4 dot/pipe routing URL-encoded

**Bulunduğu yer**: Dowaba HttpHandler `extension/dowaba_ai/api.products` URL'ini çağırırken `.` karakterini `%2E` yapıyor.

**Sorun**: OC4 routing `.` veya `|` notation'ı tanımıyor URL-encoded halde.

**Fix pattern**: Tek `index()` + `?action=X` query dispatch.

**Her platforma uyarla**: REST API endpoint path'lerinde özel karakter (`.`, `|`, `:`) kullanma. WordPress'te `/wp-json/dowaba/v1/products` clean URL native çalışır.

### Bug #5: Manifest `base_url` query string drop

**Bulunduğu yer**: Dowaba HttpHandler URL parse + query overwrite (Guzzle/Symfony HttpClient default).

**Sorun**: `base_url: "host/index.php?route=..."` → HttpHandler query'i drop edip kendi query_template'ini yazıyor.

**Fix pattern**: `base_url` SADECE schema+host+path, `route` query_template'e ayrı param.

**Her platforma uyarla**: WordPress REST API `/wp-json/.../endpoint` clean, query'siz — sorun yok. Magento'da benzer aware ol.

### Bug #6: POST body_template URL'e eklenmiyor

**Bulunduğu yer**: Dowaba HttpHandler `$pending->post($url, $body)` body olarak gönderiyor, query URL'e merge edilmiyor.

**Sorun**: OpenCart gibi `?route=...&action=...` URL'de zorunlu olan platformlarda POST request route'u kaybediyor.

**Fix**: Dowaba HttpHandler.php'te POST'larda da query URL'e merge (commit `d2829ad`).

**Her platforma uyarla**: REST API tasarımında query+body birlikte gerek olmasın. WordPress namespaces clean, Shopify GraphQL ayrı dünya. Bu fix Dowaba tarafında permanent.

### Bug #7: Array substitute kayboluyor

**Bulunduğu yer**: Dowaba HttpHandler `Arr::dot()` nested array leaf-only flatten.

**Sorun**: `'items' => '{{arg.items}}'` template substitute olmuyor çünkü `arg.items` array reference Arr::dot'ta kayboluyor.

**Fix**: Top-level array refs Arr::dot ÖNCE eklenir (commit `d2829ad`).

**Her platforma uyarla**: Bu Dowaba HttpHandler tarafında permanent. Plugin tarafında bilmek gerek: array param'lar `{{arg.X}}` ile gönderilir.

### Bug #8: OC3 vs OC4 schema farkları (order create)

**Bulunduğu yer**: `model_checkout_order->addOrder()`.

**Sorun**: OC4 array, OC3 string (`payment_method`, `shipping_method`). OC3'te `addOrderHistory`, OC4'te `addHistory`. OC3'te `payment_address_id` yok.

**Her platforma uyarla**: Major version arası schema farklarını her zaman test et. Yeni platforma geçerken: order create flow'u en kritik fix point.

### Bug #9: WordPress permalink flush

**Bulunduğu yer**: WordPress fresh install pretty permalink default kapalı.

**Sorun**: `/wp-json/dowaba/v1/manifest` 301 redirect veriyor (?rest_route kullanıyor).

**Fix**: Plugin activate sırasında `flush_rewrite_rules()` çağır.

**Pattern**:
```php
register_activation_hook(__FILE__, function () {
    // ... DB setup
    flush_rewrite_rules();
});
```

(v0.1.1'de plugin'e ekle bunu.)

---

## 📦 Marketplace Submission — Öğrenilenler

### OpenCart Marketplace
- **API yok** — tüm submission web form üzerinden manuel
- **Hızlı approval** — Dowaba AI 0 review beklemeden direkt yayınladı (nadir)
- **Banner**: 710x380, **Thumbnail**: 260x152 (Marketplace kendisi belirler — her platform farklı)
- **Cloud Marketplace** ayrı zorunluluk: `install.xml` + `upload/` folder
- **2 ayrı listing** (OC3 + OC4) yerine tek listing + multiple downloads daha iyi
- **TikTok keyword'ü** marketplace search'te çok hit aldı

### WordPress.org Plugin Directory
- **Hesap onayı manuel** (1-2 hafta — bizim hesabımız bekliyor)
- **`readme.txt`** WP-specific format zorunlu
- **`Tested up to`** alanı son WP version'a güncel olmalı
- **Plugin slug** uniqueness check (`dowaba-ai`)
- **External dependency** (Dowaba SaaS) → README'de NET belirt (review red sebebi yaygın)

### Yaygın retorik (her marketplace için):
- "Social Commerce" pitch'i e-ticaret store sahiplerinde hit alır
- "5-minute setup" sayı kullanımı conversion +%30
- Code-block conversation example > generic feature list
- "Plugin 100% free" bullet'ı Marketplace algoritması boost
- Test screenshot'lar (mock değil) review onay şartı

---

## 🎯 Reusable varlıklar — Her platforma kopyala

| Dosya | Yer | Kullanım |
|---|---|---|
| **README.md template** | `opencart/README.md` | TR+EN, badge'ler, kurulum, güvenlik tablosu |
| **CHANGELOG.md format** | `opencart/CHANGELOG.md` | Keep-a-Changelog + canlı doğrulama notları |
| **PRIVACY.md (KVKK+GDPR)** | `opencart/marketing/PRIVACY.md` | TR+EN, 10 madde |
| **Marketplace listing copy** | `opencart/marketing/MARKETPLACE_LISTING_EN.md` | Social commerce pitch + 10 fn |
| **Banner (710x380)** | `opencart/marketing/banner-710x380.html` | HTML + Chrome headless render |
| **Thumbnail (260x152)** | `opencart/marketing/thumbnail-260x152.html` | Aynı |
| **Submission checklist** | `opencart/marketing/SUBMISSION_CHECKLIST.md` | 4 faz, ~4 saat hazırlık |
| **Screenshot guide** | `opencart/marketing/SCREENSHOTS.md` | 8 zorunlu görsel |
| **build.sh template** | `opencart/build.sh` + `woocommerce/build.sh` | Dual paket OC3+OC4, WP plugin zip |
| **docker-compose** | `*/docker/docker-compose.yml` | Lokal test ortamı |
| **GH Actions release** | `.github/workflows/release-*.yml` | Tag push → auto release |

Her yeni platform için:
1. README.md'yi kopyala, platform-spesifik adapt et
2. CHANGELOG.md sıfırdan başlat
3. PRIVACY.md ortak (mağaza adı değişir)
4. MARKETPLACE_LISTING.md platform-spesifik (compatibility, naming convention)
5. Banner + thumbnail HTML'i kopyala, brand/color değiştir
6. build.sh platform packaging conventions (zip, tar.gz, modüler)

---

## 🛠️ Dowaba Backend Değişiklikleri

Bu seansta Dowaba'da yapılan **kalıcı fix'ler** (her plugin'i etkiler):

| Commit | Konu | Etki |
|---|---|---|
| `e7285c1` | BundleImport `http_config` nested wrap | manifest http_config → config.http |
| `0997023` | HttpHandler POST query URL'e ekleme | POST endpoint'lerde route param korunur |
| `d2829ad` | HttpHandler Arr::dot array fix | Top-level array template substitute |
| `d240cde` | SiteFunctionsPanel UI fix + ownership migration | UI/AI tutarlılık |
| `auto_activate` | BundleImportController flag | manifest auto-activate function |

> **Tüm yeni platform plugin'leri bu Dowaba fix'leriyle uyumlu olarak tasarlandı.** Eski Dowaba kurulumları (commit < `d2829ad`) POST body_template ile bug yaşar.

---

## 📊 KPI — İlk metrikler

OpenCart Marketplace yayını sonrası (extension_id=48534) takip edilecek metrikler. **2026-05-30 itibarıyla** dashboard kontrol:

- [ ] Total downloads (hedef: 100+ first month)
- [ ] Page views (hedef: 1.000+)
- [ ] Comments / reviews (hedef: 5+ rating 4.5)
- [ ] Email signup → Dowaba SaaS funnel conversion
- [ ] Türkiye Facebook OpenCart grupları'nda mention
- [ ] eticaretmag / eticaretpro forum coverage

WordPress.org için hesap onayı sonrası:
- [ ] Plugin Directory listing canlı
- [ ] Active installs (1 hafta sonra ~50?)
- [ ] Translations (TR + EN otomatik wp.org'da görünür)

---

## 🚀 Sıradaki Platformlar — Yol haritası

OpenCart pattern reuse oranı:

| Platform | Reuse | Tahmini süre | Pazar payı |
|---|---|---|---|
| **PrestaShop** | %80 (PHP, OpenCart benzeri) | 1-2 hafta | ~%1.5 global, %5+ Avrupa, TR'de yaygın |
| **İkas** | %50 (Türkiye SaaS, ayrı API) | 1-2 hafta | Türkiye'de 50K+ mağaza |
| **Shopify** | %30 (Liquid+GraphQL+OAuth yeni dünya) | 3-4 hafta | Global #1 SaaS, premium |
| ~~**Magento 2**~~ ✅ | %60 (PHP + composer) | **v0.1.0 yazıldı 2026-05-30** — bkz [PLUGIN_DEV_GUIDE §18](PLUGIN_DEV_GUIDE.md) | Enterprise |
| **Ticimax** | %40 (Türkiye SaaS) | 1 hafta | TR'de 30K+ |
| **Zapier** | %20 (sadece adapter) | 1 hafta | 6000+ uygulama bağlantısı |
| **n8n.io** | %20 | 1 hafta | Open-source Zapier |
| **HubSpot** | %30 (CRM, B2B) | 3-4 hafta | Sales channel |
| **Medusa** | %50 (headless JS) | 2 hafta | Open-source headless |

**Önerilen sıralama** (ROI + hız):
1. **PrestaShop** — OpenCart benzer codebase, hızlı port, TR pazarı kapsar
2. **İkas** — TR yerel networking + co-marketing fırsatı
3. **Shopify** — global premium kitle (uzun yatırım)
4. Magento / Ticimax / vs.

---

## 🧰 Tekrar kullanılabilir yöntemler

### Lokal test ortamı (Docker)
- Her platform için ayrı docker-compose
- Aynı MariaDB container'ı paylaşılabilir (database farklı)
- Cloudflare tunnel ile Dowaba prod'a bağlanma — `cloudflared tunnel --url http://localhost:PORT`
- Tunnel URL'ler kısa ömürlü (TLS bypass + DNS NXDOMAIN), her seans yeniden kurulur

### Görsel üretim
- Chrome headless ile HTML → PNG render en kaliteli
- ImageMagick gradient render zayıf — komplex tasarımlarda Chrome tercih et
- `chrome --headless --window-size=W,H --screenshot=out.png file://path.html`

### Marketing copy
- Social commerce pitch'i **hep aynı** kalsın (brand consistency)
- Code-block conversation example en güçlü hook
- Sadece kanal adları + platform adı değişir

### GitHub Actions
- `release-{platform}.yml` per platform
- Tag pattern: `{platform}-v{semver}` (örn `opencart-v0.2.2`, `woocommerce-v0.1.0`)
- Otomatik build + .zip artifact upload

---

## ⚠️ Bilinen sınırlar

1. **OC4 install.json + install.xml hibrit** — Cloud Marketplace zorunluluğu nedeniyle. OC4 installer install.json'u önceler, install.xml sadece format compliance.

2. **`opc_` prefix** — OpenCart kalıntısı, WooCommerce'te de korundu (Dowaba HttpHandler tek pattern bekliyor). v1.0'da `dwb_` generic prefix'e geçilebilir.

3. **Tunnel URL'leri kısa ömürlü** — Cloudflare quick tunnel uptime garantisi yok. Prod canlı için ayrı tunnel altyapısı (named tunnel + custom domain) gerek.

4. **WordPress.org plugin slug `dowaba-ai`** — eğer çakışırsa `dowaba-ai-woocommerce` veya benzeri fallback.

5. **Translation files** — readme.txt + plugin name içindeki Turkish karakterler bazı marketplaceler'de UTF-8 sorun çıkarabilir.

---

## 📅 Bu seans tarihi: 2026-05-23

**Commit count**: 17 (plugin + Dowaba backend)
**LOC**: ~6.500 (PHP + Twig + JS + Markdown)
**Toplam release**: 6 (5 OpenCart + 1 WooCommerce)
**Marketplace listing**: 1 canlı (OpenCart ID 48534), 1 beklemede (wp.org)
**Gerçek müşteri siparişleri AI üzerinden**: 9 (5 OC4 + 2 OC3 + 2 WC)

---

**Bu dosya her yeni platform öncesi okunmalı.** Yeni platform sonrası bu dosyaya ek bölüm yazılmalı (lessons-{platform}.md veya bu dosyaya append).

Built by [DoWaba](https://dowaba.com) — Open source under MIT.
