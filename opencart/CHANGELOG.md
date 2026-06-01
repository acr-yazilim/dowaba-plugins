# Changelog

Tüm önemli değişiklikler bu dosyada listelenir. [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) formatı, [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kuralları.

## [0.2.22] - 2026-06-01 — product_detail galeri fix (KRİTİK) + Tek-tık "Connect to DoWaba"

### Fixed — 🐛 product_detail galeri görselleri OC4'te HİÇ dönmüyordu (KRİTİK, OC3+OC4)
- **Kök neden:** galeri yükleme `method_exists($this->model_catalog_product, 'getProductImages')`
  guard'ı ile korunuyordu. OpenCart model'i bir **`Proxy`** (`__get` magic, bilinmeyen key'de Exception)
  olduğu için `method_exists()` proxy'de DAİMA `false` döner → guard hiçbir zaman geçilmiyordu →
  `gallery: []`, `gallery_count: 0` **sessizce**. Ayrıca OC4'te metot adı `getImages`, OC3'te
  `getProductImages` (OC4 rename'i) — eski kod yalnızca OC3 adını kontrol ediyordu.
- **Çözüm:** `method_exists` guard'ı kaldırıldı. Model doğrudan çağrılıyor (OC4 `getImages`, başarısızsa
  OC3 `getProductImages` fallback — Proxy `__get` bilinmeyen metotta zaten Exception fırlatır, try/catch yakalar).
- **Canlı doğrulama (OC4 4.0.2.3, localhost:8080):** product_detail `id=40` (iPhone) artık **5 galeri görseli**
  döndürüyor (önceden 0). product_search kapak görseli (`thumb` 200×200 + `image` 600×600) zaten çalışıyordu —
  o `getProducts` direkt çağrı (guard'sız) olduğu için etkilenmemişti.

### Changed
- OC3 + OC4: mevcut "DoWaba'ya git / Bundle Import" butonu artık **deep-link** → `dowaba.com/admin/connect?platform=opencart&manifest={{ manifest_url|url_encode }}` (manifest pre-filled).

### ✅ Test — 2026-06-01 (CANLI)
- Docker OpenCart 4.0.2.3 (localhost:8080) — env sıfırdan restore edildi (kaynak indirildi + config.php yazıldı + modül `extension/dowaba_ai/` altına yerleştirildi + permission).
- Gerçek OC4 admin "Dowaba AI Integration" settings: Connect butonu **render oluyor** ✓; href doğrulandı: `dowaba.com/admin/connect?platform=opencart&manifest=<encoded>` ✓ (twig `url_encode` çalışıyor); manifest input dolu ✓.
- **Görsel + fonksiyon e2e (CANLI, 19 demo ürün):** product_search → her ürün kapak `thumb`/`image` ✓; product_detail
  id=40 → 5 galeri görseli ✓; **10/10 fonksiyon** doğru (read'ler OK, order/customer IDOR guard "not found",
  write'lar scope-guard ile bloklu; scope_write açılınca order_preview→order_confirm **gerçek sipariş #10**
  oluşturdu — subtotal 202 + kargo 49 = 251).
- **OC4 catalog route notu:** manuel kurulumda extension catalog controller'ları + system library'leri OC4
  autoloader kuralı gereği **ana `catalog/controller/extension/dowaba_ai/` + `system/library/extension/dowaba_ai/`**
  dizinlerine konmalı (namespace `Opencart\Catalog`/`Opencart\System` → ana dizin). Marketplace zip installer bunu
  otomatik yapar; manuel `cp` test kurulumlarında elle yerleştirilir.
- **Yayınlandı:** `opencart-v0.2.22` (2026-06-01) — OC4 `dowaba_ai.ocmod.zip` + OC3 `dowaba-opencart-oc3-0.2.22.ocmod.zip`.

## [0.2.21] - 2026-05-30

### Added — Kurulum sihirbazında DoWaba'ya yönlendirme (upsell CTA)

- **Sihirbaz üstü CTA kutusu (OC3 + OC4, TR + EN)** — "DoWaba'da Ücretsiz Başla" (login → kayıt
  orada açılır) + "Mesaj Paketleri & Fiyatlar" (pricing) butonları. Eklenti ücretsiz; yapay zekâ
  mesaj paketleri (1.000 / 5.000 / 50.000 mesaj/ay) DoWaba'da satılır — şeffaflık notu (OpenCart
  freemium-bridge kuralı gereği listing'de açıkça belirtilir).
- **Manifest adımı altında "Bundle Import'a Git" butonu** → DoWaba admin paneli.
- Tüm dış linkler `?ref=opencart` attribution parametresiyle; `target="_blank"` + `rel="noopener"`
  (tabnabbing koruması).
- i18n korundu: 4 dil dosyası + 2 Twig template. İşlevsel/davranışsal değişiklik YOK — sadece
  yönlendirme + bilgilendirme katmanı.

## [0.2.20] - 2026-05-29

### Fixed — KRİTİK: API anahtarı kaydedilemiyor (Kaydet ↔ Yenile birbirini siliyordu)

- **Admin ayar kaydı `editSetting()` replace-all bug'ı (OC3 + OC4)** — OpenCart `editSetting()` gruptaki
  TÜM ayarları silip yeniden yazar. İki metod bunu "kısmi güncelleme" sanıp kullanıyordu:
  - **`index()` save (Kaydet):** form'da `api_key_hash`/`prefix`/`last_used` input'u yok (gizli) →
    her Kaydet'te **API anahtarı hash'i siliniyordu** → "API key not yet generated" → tüm çağrılar 401/503.
  - **`regenerateKey()` (Yenile):** sadece key alanlarını yazıyordu → **status/scope/ip_whitelist/retention
    siliniyordu** → modül pasifleşiyordu.
  - Sonuç: "Yenile → key al → Kaydet" akışında ikisi birbirini eziyordu; anahtar hiçbir zaman kalıcı olmuyordu.
  - **Fix:** her iki metod artık `getSetting('module_dowaba_ai')` ile mevcut ayarları okuyup MERGE ediyor —
    Kaydet form'da olmayan key alanlarını korur, Yenile diğer ayarları korur. Tek `editSetting` tam set yazar.
  - İlk gerçek müşteri kurulumunda (kirtasiyeistoc.com) yakalandı: yeni anahtar defalarca üretildi ama tutmadı.

### Notes

- Bu fix olmadan `.htaccess` düzeltmesi + doğru API anahtarı bile yetmiyordu (plugin anahtarı saklayamıyordu).
- v0.2.19 (Authorization header strip / query-token fallback + admin `.htaccess` uyarısı) bu sürümde korunuyor.

## [0.2.19] - 2026-05-29

### Fixed — KRİTİK: Authorization header strip (gerçek-dünya shared hosting)

- **Bearer token query-param fallback (OC3 + OC4)** — Apache/LiteSpeed/FastCGI ortamlarında
  `HTTP_AUTHORIZATION` PHP'ye geçmiyor (kök `.htaccess`'te `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`
  yoksa) → `Authorization: Bearer` header `auth.php`'ye ulaşmıyor → tüm API çağrıları
  `401 {"error":"Bearer token required"}`. Docker + Cloudflare tunnel test ortamı (v0.2.1) Authorization'ı
  geçirdiği için bu yakalanamamıştı; **ilk gerçek müşteri kurulumunda (kirtasiyeistoc.com)** ortaya çıktı.
  - `auth.php::authHeader()` (OC3+OC4): header bulunamazsa `?token=` / `?api_key=` query param
    fallback okur, `Bearer ` ile wrap ederek `verify()` regex'ine geçirir (verify() değişmedi).
  - `manifest.php` (OC3+OC4): her function `query_template`'ine `token: {{connection.credentials.token}}`
    eklendi → Dowaba HttpHandler token'ı query string'de de gönderir.
  - **Güvenlik korunur**: `opc_` format check + `sha256` `hash_equals` query'den gelen token'a da
    aynen uygulanır. `.htaccess` RewriteRule mevcutsa `auth.php` header'ı önce dener (öncelik: header → query).

### Added

- **Admin panelde `.htaccess` yapılandırma uyarısı (OC3 + OC4)** — Module ayar sayfasında Adım 1 (API Key)
  altına "Önerilen: Authorization Header Yapılandırması" bilgi kutusu eklendi. RewriteRule iki satırını
  kopyalanabilir (`user-select:all`) gösterir + "eklenmezse query-token fallback ile yine çalışır" notu.
  3 yeni dil anahtarı (`text_htaccess_title` / `_desc` / `_note`) tr-tr + en-gb. **Plugin `.htaccess`'e
  DOKUNMAZ / otomatik değiştirmez — sadece kullanıcıya talimat gösterir** (müşteri SEO/rewrite kuralları korunur).

### Operasyon notu

- Mevcut kurulumlar (eski manifest'ten import edilmiş, token query'siz) için 2 seçenek:
  - **(a)** Mağaza kök `.htaccess`'ine RewriteRule ekle → header PHP'ye geçer, re-install/re-import GEREKMEZ.
    En güvenli — token query'e/access.log'a gitmez.
  - **(b)** Plugin'i v0.2.19'a güncelle + Dowaba panelden bundle'ı "Var olanı güncelle" ile re-import →
    query token fallback devreye girer (.htaccess erişimi olmayan müşteriler için).

## [0.2.3] - 2026-05-26

### Fixed

- **OC3 `opc_order_preview` Gemini JSON Schema strict fix** — `items: {type: array}` ve `customer: {type: object}` boş tanımlıydı → Gemini "function declaration invalid" 400 reject → AI tüm tool listesini reddediyor → kullanıcıya silent "Şu an yanıt veremiyoruz" fallback. Fix: `items.items.{product_id, quantity}` + `customer.properties.{phone, email, name, address, city}` (OC4 zaten doğruydu — sadece OC3 etkilendi). Canlı e2e doğrulama: site_id=75, 10 fn aktif, opc_product_search 5ms.
- **Audit log lazy retention cleanup (OC3 + OC4)** — `purgeOld()` metodu vardı ama hiç çağrılmıyordu → audit tablosu sınırsız büyüme bug'ı. Şimdi `write()` her çağrıda 1/500 ihtimalle `audit_retention_days` (default 30) eski log'ları siler. Production disk-fill önlenir.
- **OC3 version tek-otorite** — install.xml 0.2.2 / manifest.php 0.2.0 / api.php user_agent 0.1.0 uyumsuzluğu → `getPluginVersion()` artık install.xml'i runtime parse ediyor. Tek değişen yer: `install.xml` `<version>`. SemVer disiplini garanti.
- **TR/EN dil dosyaları "9 → 10 function"** — `text_step_2_desc` yanlış sayı veriyordu (OC3 + OC4 her ikisi de). OC4 install.json comment'inde de düzeltildi.

### Notes

- Backend (Dowaba ana repo) `BundleImportController::validateManifest` recursive Gemini JSON Schema check eklendi (2026-05-26). Yeni invalid manifest gönderildiğinde 422 ile import-time reject edilir — runtime silent fallback yerine.
- `UnifiedAIService::callGeminiWithRetry` 400 body parse → `lastGeminiError = 'schema_invalid'` + `gemini.plugin_schema_invalid` UserErrorRecorder banner. Site sahibine "Plugin X parameter schema invalid: items missing" görünür.

## [0.2.2] - 2026-05-23

### Added — OpenCart Cloud Marketplace compatibility
- **OC4 paketine `install.xml` eklendi** — OpenCart Cloud Marketplace submission gereksinimi.
  Cloud zorunlulukları: (1) OC 3.0+, (2) `install.xml` + `upload/` folder, (3) zip format.
  Mevcut OC4 paketi sadece `install.json` içeriyordu → Cloud reddediyordu.
  Çözüm: Aynı zip içine `install.xml` (no-op OCMOD wrapper) eklendi. `install.json`
  OC4 native install için korundu — OC4 installer ikisini de görür, sadece JSON'u kullanır.
  Cloud Marketplace XML format compliance kontrolünü artık geçer.
- **build.sh OC4 build**: zip'e `install.xml` de eklendi (`zip ... install.json install.xml upload/`)

### Notes
- OC4 native install hala `install.json` üzerinden (Cloud sadece format check yapıyor)
- `install.xml` aktif modifier'ı: admin column_left'e "Dowaba AI" menü item (UX bonus)
- OC3 paketi zaten Cloud-uyumlu (install.xml + upload/ vardı)
- Hem standart Marketplace hem Cloud Marketplace tek zip ile çalışır

## [0.2.1] - 2026-05-23

### Fixed — OC3 order create regression (canlı test'te yakalandı)
- **OC3 schema farkı: `payment_method` STRING** (OC4'te array `{code, name}`)
  - OC4: `'payment_method' => ['code' => 'cod', 'name' => 'Cash on Delivery']`
  - OC3: `'payment_method' => 'Cash On Delivery'` + ayrı `'payment_code' => 'cod'`
  - `mysqli::real_escape_string(): Argument #1 ($string) must be of type string, array given` hatası fix
- **OC3 schema farkı: `shipping_method` STRING** (aynı OC4 array → OC3 string fix)
  - `'shipping_method' => 'Flat Shipping Rate'` + `'shipping_code' => 'flat.flat'`
- **OC3 model method ismi**: `addHistory()` → `addOrderHistory()` — OC3'te status history method'u farklı isim
- **OC3 schema'da yok**: `payment_address_id`, `shipping_address_id` field'ları kaldırıldı (OC3'te bu kolonlar yok, undefined index warning'i atıyordu)

### Canlı doğrulama (Dowaba prod → Cloudflare tunnel → docker OC3 8081)
- ✅ OC3 site_id=75 ("OpenCart 3 Test") bundle import + 10 fn auto_activate
- ✅ `opc_product_search` → iPhone döndü
- ✅ `opc_product_compare` → 3 ürün karşılaştırması
- ✅ `opc_order_preview` → preview_id + 5dk TTL
- ✅ `opc_order_confirm` → DB'de **order #1 (150 USD) + order #2 (249 USD COD)** yaratıldı

### Paralel OC4 doğrulama (regression check)
- ✅ OC4 site_id=57 `opc_product_search` hala çalışıyor (bozulmadı)

## [0.2.0] - 2026-05-23

### Added — OpenCart 3.x dual support
- **Yeni `src/oc3/` ağacı** — OC 3.0.3.x için tam plugin port:
  - `install.xml` (OCMOD 3 modifier — admin column_left'e Dowaba AI menü item ekler)
  - `admin/controller/extension/module/dowaba.php` (global namespace, `ControllerExtensionModuleDowaba`)
  - `admin/model/extension/module/dowaba.php` (dowaba_audit table install/uninstall)
  - `admin/view/template/extension/module/dowaba.twig` (Bootstrap 3, OC3 UI conventions)
  - `admin/language/{en-gb,tr-tr}/extension/module/dowaba.php` (OC4 ile aynı)
  - `catalog/controller/extension/dowaba_ai/{manifest,api}.php` (global namespace)
  - `system/library/dowaba_ai/{auth,scope_guard,audit_logger,order_preview}.php` (`Dowaba*` prefix, global)
- **`src/oc4/` ağacı** — eski `src/upload/` buraya taşındı (yapısal değişiklik yok)
- **`build.sh` dual paketleme** — `dowaba-opencart-oc3-X.Y.Z.ocmod.zip` + `dowaba-opencart-oc4-X.Y.Z.ocmod.zip` ayrı çıkarır
- **`docker-compose.yml` ikinci servis** — `dwb-opencart3` port 8081 (OC 3.0.3.9 source mount)
- **GH Actions release** — her tag push'ta her iki .ocmod.zip da release artifact olarak yüklenir

### Test edildi (lokal docker)
- OC 3.0.3.9 + MariaDB 11 + PHP 8.2 ortamında plugin install + manifest endpoint + Bearer auth + product_search + categories → tüm akışlar PASS
- Routing OC3 Action class davranışına uyarlandı (`extension/dowaba_ai/manifest` + `extension/dowaba_ai/api?action=...`)

### Pazar etkisi
- Türkiye OpenCart pazarının yaklaşık %60'ına ulaşır (OC3 yaygınlığı). v0.1.x sadece %30-35'lik OC4 kesimine erişiyordu.

## [0.1.0] - 2026-05-23 — ilk sürüm

### Added — v0.1.0 hedefi
- OpenCart 4.x OCMOD paket yapısı (`install.json` + `upload/`)
- 5-adımlı admin setup wizard (API key üret + manifest URL + IP whitelist + scope toggle + bağlantı testi)
- `.well-known/dowaba-bundle.json` manifest endpoint
- 8 read-only function: `product_search`, `product_detail`, `product_compare`, `stock_check`, `category_list`, `order_status`, `customer_lookup`, `cart_recover`
- 2-adımlı sipariş oluşturma: `order_preview` (preview cache 5dk TTL) → `order_confirm` (müşteri onayı sonrası)
- Bearer auth (sha256 hash karşılaştırma, plain DB'de yok)
- IP whitelist (opsiyonel, virgüllü liste)
- Scope guard (`read` default ON, `write` default OFF)
- `dowaba_audit` tablo + admin "Audit Log" sekmesi (son 100 + filtreleme, 30 gün retention)
- Docker test ortamı (`docker/docker-compose.yml`: OpenCart 4.x + MariaDB 11 + Mailpit)
- e2e smoke test (`test/e2e.sh`)
- PHPUnit unit testler (`test/phpunit/`: AuthTest, ScopeGuardTest, OrderPreviewTest)
- GitHub Actions release pipeline (`tag:opencart-v*` → `.ocmod.zip` artifact)

## [0.1.1] - 2026-05-23

### Fixed
- **KRİTİK** `OrderPreview::peek()` OpenCart File cache `get()` cache miss'te `[]` (boş array) döndürür, `null` değil. Replay protection bozuktu — aynı preview_id ile birden fazla order yaratılabiliyordu. Fix: `empty($value) || !isset($value['_preview_id'])` ek check.
- **KRİTİK** `Api::respond()` HTTP status code OC4 Response sınıfı tarafından yutuluyordu. Fix: 3 mekanizma birlikte (http_response_code + header() + response->addHeader).
- `Manifest` endpoint `config('config_url')` statik kurulum URL'i döndürüyordu — Cloudflare tunnel / ngrok / reverse proxy ortamlarında Dowaba'nın manifest'i okuduğu URL ile API çağırdığı URL farklı oluyordu (fail). Fix: `resolveBaseUrl()` — X-Forwarded-Host + HTTP_HOST + admin override fallback'leri.
- `orderConfirm()` order create transaction wrap yoktu, exception durumunda partial order DB'de kalabilirdi. Fix: START TRANSACTION / COMMIT / ROLLBACK.
- OC4 4.0.2.3 `model_checkout_order::addOrder()` PHP Warning'leri (master_id, subscription, payment_address_id, shipping_address_id) — bunlar product-level field. Fix: `orderProducts[]` her item'a `master_id` + `subscription` eklendi; order-level'a `payment_address_id` + `shipping_address_id`.
- OC4 totals modeli `'extension' => 'total'` yerine `'extension' => 'opencart'` — opencart extension'ı altındaki Total\SubTotal model'i load edilebilsin.
- `AuditLogger::ensureTable()` defensive — admin install hook tetiklenmediği durumlarda ilk write'ta tablo otomatik oluşur (idempotent CREATE IF NOT EXISTS).

### Changed
- GitHub Actions workflow `opencart/.github/workflows/` → repo root `.github/workflows/release-opencart.yml` (umbrella repo'da workflow tetiklenebilir hale geldi).
- Manifest'e `module_dowaba_ai_manifest_base_url` admin setting override eklendi (rakip CDN ardındaki kurulumlar için).

## [0.1.2] - 2026-05-23

### Fixed
- **KRİTİK** OC4 routing `.` ve `|` notation Dowaba HttpHandler tarafından URL-encode ediliyor (`%2E` / `%7C`) → route resolve fail. Çözüm: `api.php` tek `index()` + `?action=<method>` query dispatch. Whitelisted action map prompt injection koruması.
- **KRİTİK** Manifest `base_url` `?route=...` query string'i Dowaba HttpHandler tarafından DROP ediliyor (Guzzle URL parse + query overwrite). Çözüm: `base_url` SADECE schema+host+path; `route` query_template'e ayrı param olarak konur.
- `Api::readJsonBody()` content-type bağımsız body parse — JSON → $_POST → raw form-encoded fallback chain.

### Added
- Canlı Dowaba prod entegrasyon doğrulandı (Cloudflare tunnel + site_id=57): manifest fetch + auto_activate ✓, opc_product_search canlı çağrı 3 ürün döndü ✓.

### Known issues — ÇÖZÜLDÜ (Dowaba HttpHandler d2829ad, 2026-05-23 17:52)
- ~~Dowaba HttpHandler POST `body_template` empty~~ ✅
- ~~`opc_product_compare` array parameter boş~~ ✅

**Kök sebep:** Dowaba `HttpHandler::buildSubstitutes()` `Arr::dot()` ile nested array'leri leaf-key'lere flatten ediyor; top-level array referansları (`arg.items`, `arg.product_ids`) kayboluyordu. Template `'{{arg.items}}'` single-token resolve null dönüyor → `pruneEmpties` drop → boş body.

**Fix:** Raw `$args`'ı `'arg.{key}'` formatında ÖNCE substitutes'a ekle (top-level array refs korunur), `Arr::dot` SONRA leaf-key'leri append (geriye-uyumlu).

**Canlı doğrulama (2026-05-23):**
- `opc_product_compare` 3 ürün karşılaştırması Dowaba prod'tan ✓
- `opc_order_preview` → preview_id + summary ✓
- `opc_order_confirm` → DB'de order #9 yaratıldı, COD payment, comment'te preview_id ✓
- Replay attack → 410 Gone ✓

Plugin tarafında değişiklik yok — v0.1.2 stabil ve prod kullanılabilir.
