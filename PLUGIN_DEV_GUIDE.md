# Dowaba Plugin Geliştirme Rehberi

Bu doküman bugüne kadar (2026-05-26) **OpenCart 3 + OpenCart 4 + WooCommerce + PrestaShop + Shopify + İkas** plugin geliştirme sürecinden çıkardığımız tüm dersleri derler. Yeni bir e-ticaret platformuna entegrasyon yazarken **buraya bak**.

> **Tek otorite kuralı:** Bir plugin yazarken bu dokümandan tek bir bölüm bile atlamak production'da sessiz fallback'e yol açar (canlı vaka: [opc_order_preview Gemini reject](#5-gemini-json-schema-strict-kritik)).

---

## 0. Mimari Özet — Bir Plugin Nedir?

```
[Müşteri WhatsApp/IG/Widget]
        │
        ▼
[Dowaba AI (UnifiedAIService)]   ← Gemini / Claude
        │ tool_call: opc_product_search(q: "iPhone")
        ▼
[Dowaba HttpHandler]             ← Function dispatcher
        │ GET https://store.com/index.php?route=extension/dowaba_ai/api&action=products&q=iPhone
        │ Authorization: Bearer opc_xxxxx
        ▼
[Plugin (mağaza içinde)]
        │ 1) Authority check (sha256 hash)
        │ 2) Scope guard (read/write)
        │ 3) Audit log
        │ 4) Native query (Product::searchByName...)
        ▼
[Mağaza DB → JSON dön]
        │
        ▼
[Dowaba AI yanıtı oluştur → müşteriye gönder]
```

Plugin **iki şey yapar:**
1. **Manifest endpoint** (`/manifest`) — Bundle Import için 10 function tanımını JSON döner. Public, auth yok.
2. **API endpoint** (`/api?action=...`) — AI'ın çağırdığı 10 function'ı çalıştırır. Bearer auth + scope guard + audit log.

---

## 1. Manifest — Bundle Import için JSON

### URL pattern (platform bazlı)

| Platform | Manifest URL pattern |
|---|---|
| OpenCart 3 | `https://store.com/index.php?route=extension/dowaba_ai/manifest` |
| OpenCart 4 | `https://store.com/index.php?route=extension/dowaba_ai/manifest` |
| WooCommerce | `https://store.com/wp-json/dowaba/v1/manifest` |
| PrestaShop | `https://store.com/index.php?fc=module&module=dowaba_ai&controller=manifest` |
| Shopify | OAuth proxy — Dowaba `/api/shopify/manifest/{connection_token}` üzerinden |
| İkas | OAuth proxy — Dowaba `/api/ikas/manifest/{connection_token}` üzerinden |

> **Self-hosted plugins (OC/Woo/Presta)**: manifest mağazada serve edilir, Dowaba dış IP'den fetch eder.
> **SaaS connectors (Shopify/İkas)**: Dowaba kendisi OAuth proxy — manifest Dowaba'da üretilir.

### Minimal manifest JSON

```json
{
  "schema_version": "1.0",
  "name": "OpenCart 3 — Your Store",
  "plugin_version": "0.2.3",
  "platform": "opencart3",
  "connection": {
    "type": "http_api",
    "base_url": "https://store.com/index.php",
    "auth_type": "bearer",
    "allowed_hosts": ["store.com"]
  },
  "functions": [ /* 10 function tanımı */ ]
}
```

### URL template + query template ayrımı

OpenCart 3 manifest dinamik URL üretemiyor (tek route, query param ile aksiyona ayrılır). Bu yüzden:

```json
{
  "slug": "opc_product_search",
  "http_config": {
    "method": "GET",
    "url_template": "{{connection.base_url}}",
    "query_template": {
      "route": "extension/dowaba_ai/api",
      "action": "products",
      "q": "{{arg.query}}",
      "limit": "{{arg.limit}}"
    },
    "timeout_ms": 5000
  }
}
```

- `url_template` sabit, `{{connection.base_url}}` template — Dowaba `connection.base_url`'yi inject eder.
- `query_template` her function için farklı (action + arg).
- `body_template` POST için.

### Dinamik `base_url` resolve

Manifest controller asla statik URL hardcode etmez — `HTTP_HOST` veya `X-Forwarded-Host` üzerinden runtime resolve eder. Sebep: aynı plugin Cloudflare tunnel, ngrok, reverse proxy ardında olabilir.

```php
private function resolveBaseUrl(): string {
    $override = trim((string) Configuration::get('DOWABA_AI_MANIFEST_BASE_URL'));
    if ($override !== '') return rtrim($override, '/');

    $fwdHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
    if ($fwdHost !== '') {
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https') . '://' . $fwdHost;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return ($_SERVER['HTTPS'] ?? '') === 'on' ? "https://$host" : "http://$host";
}
```

---

## 2. 10 Standart Function

Her plugin **bu 10 fonksiyonu** sağlar (slug prefix farklı: `opc_*` OpenCart, `wcm_*` WooCommerce, `psm_*` PrestaShop, `shp_*` Shopify, `iks_*` İkas):

| # | Slug | Scope | Ne yapar |
|---|---|---|---|
| 1 | `{prefix}_product_search` | read | Ad/SKU/kategori ile ürün listele |
| 2 | `{prefix}_product_detail` | read | Tek ürün tam detay |
| 3 | `{prefix}_product_compare` | read | 2-3 ürünü yan yana |
| 4 | `{prefix}_stock_check` | read | Stok adedi sorgula |
| 5 | `{prefix}_category_list` | read | Kategori ağacı |
| 6 | `{prefix}_order_status` | read | order_id + email ile durum (KVKK email match) |
| 7 | `{prefix}_customer_lookup` | read | Phone/email ile müşteri profili |
| 8 | `{prefix}_cart_recover` | read | Sepet hatırlatma link'i |
| 9 | `{prefix}_order_preview` | write | Sipariş öncesi özet (preview_id 5dk TTL) |
| 10 | `{prefix}_order_confirm` | write | Müşteri "Evet" sonrası gerçek sipariş |

> Sayıyı 10'da tut — daha fazla function eklemek Gemini context'i şişirir ve **tool description boilerplate**'i AI'ı yorar.
> İki write function (preview + confirm) **müşteri onayı zorunlu** flow için kritik. Ayırma sebebi: prompt injection korumas (AI tek başına sipariş AÇAMAZ, sadece preview yaratır; confirm için müşteri "evet" demek zorunda).

---

## 3. Function Parameters — Gemini JSON Schema Strict ⚠️

**🔴 EN KRİTİK KURAL** — Bunu kaçırırsan AI sessiz fallback'e düşer.

### Kural

- `"type": "array"` → **`items` field ZORUNLU** (her item için tip tanımı)
- `"type": "object"` → **`properties` field ZORUNLU** (alanlar)
- Boş object/array tanımı **YASAK** — schema invalid sayılır

### Canlı vaka: `opc_order_preview` regression (2026-05-26)

```diff
- 'items':    {type: 'array'}                    // ❌ Gemini reject
- 'customer': {type: 'object'}                   // ❌ Gemini reject

+ 'items': {
+   'type': 'array',
+   'items': {                                   // ✅ item tipi tanımlı
+     'type': 'object',
+     'properties': {
+       'product_id': {'type': 'integer'},
+       'quantity':   {'type': 'integer', 'default': 1}
+     },
+     'required': ['product_id']
+   }
+ }
+ 'customer': {
+   'type': 'object',
+   'properties': {                              // ✅ object alanları tanımlı
+     'phone':   {'type': 'string'},
+     'email':   {'type': 'string'},
+     'name':    {'type': 'string'},
+     'address': {'type': 'string'},
+     'city':    {'type': 'string'}
+   }
+ }
```

**Sonuç:**
- INVALID 10 fn → Gemini API 400 "function declaration invalid" → UnifiedAIService **tüm function listesini reddediyor** → AI hiç tool call yapmıyor → kullanıcıya "Şu an size yanıt veremiyoruz" generic fallback gönderiliyor.
- VALID 10 fn → AI çalışıyor, gerçek mağaza verisi geliyor.

### Test: Binary search ile bul

Plugin yazarken hep şu test'i koş (canlı bir Dowaba site'ında):

```bash
# 1) Tüm function'ları aktive et, "iPhone var mı?" sor
# 2) Eğer fallback geliyorsa → kademe ile yarısını pasif et, tekrar test
# 3) Binary search → suçlu function'ı bul
# 4) Parameters JSON Schema'sını detaylı yaz
```

---

## 4. HttpHandler Config Format ⚠️

DB'deki `functions.config` JSON kolonu **şu format** olmalı:

```json
{
  "http": {
    "method": "GET" | "POST" | "PATCH",
    "url_template": "{{connection.base_url}}/path",
    "query_template": { ... },
    "body_template": { ... },
    "timeout_ms": 5000
  }
}
```

**Yanlış**: `config.{method, url_template}` düz (handler `http_config_missing` döndürür)

**Doğru**: `config.http.{method, url_template}` nested

### Bundle Import nasıl wrap ediyor

`BundleImporter.php`:
```php
'config' => isset($fn['http_config']['url_template']) || isset($fn['http_config']['method'])
    ? ['http' => $fn['http_config']]   // ✓ wrap
    : $fn['http_config'],              // legacy fallback
```

Yani manifest `"http_config": {...}` döndürür → BundleImporter `"config": {"http": {...}}` yapar. Eğer manifest farklı format döndürürse handler `http_config_missing` ile fail eder.

### Template variables

| Variable | Anlamı |
|---|---|
| `{{connection.base_url}}` | Bundle Import'ta verilen URL |
| `{{arg.field_name}}` | AI'ın doldurduğu parameter |
| `{{site.api_key}}` | Site'ın Dowaba widget key'i (genelde gerekmez, Bearer auth ayrı) |

---

## 5. API Endpoint — Bearer Auth + Scope Guard + Audit

Plugin'in API controller'ı şu sırayla guard'lar:

### 5.1 Bearer Auth (sha256 hash)

```php
public static function verify(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
        return ['success' => false, 'status' => 401, 'error' => 'Bearer token required'];
    }
    $providedKey = $m[1];

    // Format check (her plugin kendi prefix'iyle: opc_*, wcm_*, psm_*)
    if (!preg_match('/^opc_[a-f0-9]{32,128}$/i', $providedKey)) {
        return ['success' => false, 'status' => 401, 'error' => 'Invalid bearer token'];
    }

    $storedHash = Configuration::get('DOWABA_AI_API_KEY_HASH');
    if ($storedHash === '') {
        return ['success' => false, 'status' => 503, 'error' => 'API key not yet generated'];
    }
    if (!hash_equals($storedHash, hash('sha256', $providedKey))) {
        return ['success' => false, 'status' => 401, 'error' => 'Invalid bearer token'];
    }

    // Optional IP whitelist
    $whitelist = trim(Configuration::get('DOWABA_AI_IP_WHITELIST'));
    if ($whitelist !== '' && !in_array($_SERVER['REMOTE_ADDR'], explode(',', $whitelist), true)) {
        return ['success' => false, 'status' => 403, 'error' => 'IP not whitelisted'];
    }

    return ['success' => true, 'status' => 200];
}
```

**Key generation:**
```php
public static function generateKey(): string {
    $plainKey = 'opc_' . bin2hex(random_bytes(32));
    Configuration::updateValue('DOWABA_AI_API_KEY_HASH',   hash('sha256', $plainKey));
    Configuration::updateValue('DOWABA_AI_API_KEY_PREFIX', substr($plainKey, 0, 12));
    return $plainKey;  // ⚠️ sadece bir kez gösterilir, sonra hash kalır
}
```

> **NEDEN sha256 hash**: Plugin DB compromise olursa plain key sızmaz. Dowaba kendi tarafında plain key tutar (encrypted at-rest), her çağrıda Bearer header'a koyar.

### 5.2 Scope Guard (read/write)

```php
class DowabaScopeGuard {
    public static $FUNCTION_SCOPES = [
        'opc_product_search'  => 'read',
        'opc_product_detail'  => 'read',
        // ...
        'opc_order_preview'   => 'write',
        'opc_order_confirm'   => 'write',
    ];

    public static function check(string $scope): array {
        $enabled = (bool) Configuration::get('DOWABA_AI_SCOPE_' . strtoupper($scope));
        if (!$enabled) {
            return ['allowed' => false, 'status' => 403, 'error' => "Scope $scope disabled"];
        }
        return ['allowed' => true, 'status' => 200];
    }
}
```

**Default değerler:**
- `read = 1` (açık) — ürün/sipariş okuma güvenli
- `write = 0` (kapalı) — sipariş oluşturma admin tarafından **bilinçli açılmalı**

> **NEDEN write default kapalı**: AI prompt injection riski. Kullanıcı "ürüne ekle ve onayla" diye yazarsa AI kendi başına sipariş açabilir. 2-adım flow (preview → customer confirm) + admin'in scope toggle'ı iki katmanlı koruma.

### 5.3 Audit Log

Her API call'u DB'ye yaz:

```sql
CREATE TABLE oc3_dowaba_audit (
    audit_id      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    function_slug VARCHAR(64) NOT NULL,
    request_ip    VARCHAR(45) NOT NULL,
    status_code   SMALLINT(3) NOT NULL,
    duration_ms   INT(11) NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_id),
    INDEX idx_created_at (created_at),
    INDEX idx_function_slug (function_slug),
    INDEX idx_status_code (status_code)
);
```

**Retention:** 30 gün (configurable). Admin panel'inde son N satır gösterilmeli.

> **Audit log NEDEN kritik**: Production'da AI generic fallback verirse plugin tarafında call gelmemiş bile demek olabilir. Audit log yoksa Dowaba/plugin/AI hangisinin suçlu olduğunu anlayamazsın. Aktif debug tool'u.

---

## 6. write Function — 2-Adım Confirmation Flow

`order_preview` + `order_confirm` çiftinin kuralı:

```
1. AI → POST {plugin}/order_preview {items, customer}
       → Plugin stok check + total hesapla + preview_id üret
       → Cache::store(preview_id, payload, 5min)
       → Dön: {preview_id, summary: {items, subtotal, shipping, total}}

2. AI → Müşteriye özet göster
       "📦 Sipariş özetin: iPhone 15 Pro $3199 + Kargo $48 = $3247. Onaylıyor musun?"

3. Müşteri "Evet"

4. AI → POST {plugin}/order_confirm {preview_id, confirmed: true}
       → Plugin Cache::peek(preview_id) → varsa Cache::clean (one-shot)
       → Native order create (Cart + Order + Customer + Address)
       → Dön: {order_id, status: 'pending', payment_url}
```

### Replay Protection

```php
public static function consume(string $previewId): ?array {
    $payload = self::peek($previewId);
    if ($payload === null) return null;
    Cache::clean(self::CACHE_PREFIX . $previewId);  // ⚠️ one-shot
    return $payload;
}

public static function isValidId(string $id): bool {
    return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
}
```

> **NEDEN one-shot**: AI aynı preview_id ile iki kez confirm çağırırsa, müşteri tek "Evet" demiş olur ama iki sipariş açılır. Cache::clean ilk consume sonrası garanti tek sipariş.

---

## 7. Dosya Yapısı — Platform Bazlı

### OpenCart 3.x

```
upload/
├── admin/
│   ├── controller/extension/module/dowaba.php        # Admin config + AJAX endpoints
│   ├── model/extension/module/dowaba.php
│   ├── view/template/extension/module/dowaba.twig   # 5-step setup wizard UI
│   └── language/{tr-tr,en-gb}/extension/module/dowaba.php
├── catalog/controller/extension/dowaba_ai/
│   ├── manifest.php                                  # Public manifest endpoint
│   └── api.php                                       # API dispatch (10 actions)
└── system/library/dowaba_ai/
    ├── auth.php
    ├── scope_guard.php
    ├── audit_logger.php
    └── order_preview.php
install.xml                                           # OCMOD modifier (admin menu shortcut)
```

### OpenCart 4.x

```
upload/
├── admin/controller/extension/module/dowaba.php
├── catalog/controller/manifest.php                   # PSR-4 short namespacing
├── catalog/controller/api.php
└── system/library/{auth, scope_guard, audit_logger, order_preview}.php
install.json                                          # OC4 native install
install.xml                                           # OC Cloud Marketplace compat
```

### PrestaShop 1.7 + 8.x

```
dowaba_ai/                                             # Module folder (zip root)
├── dowaba_ai.php                                      # Main module class
├── logo.png                                           # 32×32 module icon
├── index.php                                          # ⚠️ Validator security: every folder
├── .htaccess                                          # ⚠️ Root: prevent directory listing
├── classes/
│   ├── index.php                                      # ⚠️ Yine zorunlu
│   ├── Auth.php
│   ├── ScopeGuard.php
│   ├── OrderPreview.php
│   └── AuditLogger.php
├── controllers/
│   ├── index.php
│   ├── admin/index.php
│   └── front/
│       ├── index.php
│       ├── manifest.php
│       └── api.php
├── views/
│   ├── index.php
│   └── templates/
│       ├── index.php
│       └── hook/index.php
├── translations/index.php
└── sql/index.php
```

### WooCommerce

```
dowaba-ai/                                             # Plugin folder (wp-content/plugins/)
├── dowaba-ai.php                                      # Plugin header + bootstrap
├── readme.txt                                         # wp.org standard
├── uninstall.php
├── admin/views/settings.php                           # WP Admin settings page
└── includes/
    ├── class-dowaba-admin.php
    ├── class-dowaba-api.php                           # REST namespace registration
    ├── class-dowaba-auth.php
    ├── class-dowaba-scope-guard.php
    ├── class-dowaba-audit-logger.php
    ├── class-dowaba-order-preview.php
    └── class-dowaba-manifest.php
```

REST route registration:
```php
add_action('rest_api_init', function () {
    register_rest_route('dowaba/v1', '/manifest', [
        'methods' => 'GET',
        'callback' => ['DowabaManifest', 'show'],
        'permission_callback' => '__return_true',  // public
    ]);
    register_rest_route('dowaba/v1', '/api/(?P<action>[a-z_]+)', [
        'methods' => ['GET', 'POST'],
        'callback' => ['DowabaApi', 'dispatch'],
        'permission_callback' => ['DowabaAuth', 'restPermission'],  // Bearer check
    ]);
});
```

### Shopify / İkas (OAuth SaaS)

Bu ikisi mağazaya **eklenti yüklenmez**. Dowaba kendisi OAuth proxy:
- Müşteri Dowaba panel'den "Shopify Mağaza Bağla" → OAuth → token DB'ye yazılır
- Manifest Dowaba'da üretilir: `dowaba.com/api/shopify/manifest/{connection_token}`
- API call'lar Dowaba proxy üzerinden Shopify GraphQL'e gider

Bkz: `app/Http/Controllers/Api/ShopifyOAuthController.php`, `IkasOAuthController.php`

---

## 8. PrestaShop Validator Compliance (zorunlu)

> 📦 **Detaylar taşındı → [prestashop/MARKETPLACE.md § 5](prestashop/MARKETPLACE.md)** (PrestaShop tek otoritesi).
> Tam kod örnekleri (her klasörde `index.php`, root `.htaccess`, license header, structure exit, PSR-12, SQL_MODE
> tuzağı) + Marketplace submission/validation/fee süreci orada.

Özet: [validator.prestashop.com](https://validator.prestashop.com/) → tüm kategoriler **0 error** olmalı, yoksa
Addons reddeder. PrestaShop-özel zorunluluklar: her klasörde `index.php`, root `.htaccess`, her PHP dosyasında
`if (!defined('_PS_VERSION_')) { exit; }` + `@author/@copyright/@license` file doc comment, PSR-12 (tek-satır if yasak).

---

## 9. Bundle Import Flow (Dowaba paneli tarafı)

```
1. Plugin admin → "Regenerate API Key" → opc_xxxxx kopyala (BİR KEZ gösterilir)
2. Plugin admin → "Manifest URL" kopyala
3. Dowaba panel → Site → Entegrasyonlar → Bundle Import
4. Form: manifest_url + api_key (Bearer token alanına)
5. POST /api/sites/{id}/bundles/import
6. Dowaba:
   - Manifest URL'e GET (public, auth yok)
   - JSON parse
   - BundleImporter: her function için `config: {http: {http_config}}` wrap
   - SiteConnection insert (base_url + auth_token encrypted)
   - FunctionDefinition insert (owner_site_id, slug, parameters, config)
   - Response: {functions: {created: [...], updated: [...]}}
7. ⚠️ DEFAULT PASİF — `note: "Aşağıdaki listeden tek tek aktive et"`
8. Aydın UI'den "Aktive Et" toggle → is_active=true
9. AI Gemini'ye tool definitions ile gider, müşteri sorusunda tetikler
```

### Default pasif neden?

Audit + güvenlik. Bayi, mağaza sahibinin auth verisi olmadan function'ları aktif etmemeli. Manuel aktive Adım = "bu key benim, bu URL benim sunucum" onayı.

---

## 10. AI Exception Suppression (debug rehberi)

UnifiedAIService Gemini exception'larını **swallow ediyor** → kullanıcıya generic "Şu an size yanıt veremiyoruz" gönderir → debug zor.

### Tanılama akışı (canlı vaka)

```bash
# 1) Baseline test: function YOK iken AI çalışıyor mu?
#    → Çalışıyor: AI servisi OK, sorun function'larda
#    → Çalışmıyor: AI provider / Gemini key / system_prompt sorunu

# 2) Binary search: 1 function → 5 → 8 → 10 → hangi sayıda kırılıyor?

# 3) Suçlu function'ı izole et: 9 aktif AMA suçlu HARİÇ → çalışıyorsa kanıt

# 4) Suçlu function'ın parameters JSON Schema'sını incele:
#    - type:array → items var mı?
#    - type:object → properties var mı?
#    - Boş object/array YASAK
```

### AI cevabını DB'den oku

```php
$sess = App\Models\ChatSession::find($sessionId);
foreach ($sess->messages()->latest('id')->take(3)->get() as $m) {
    echo $m->created_at . ': ' . preg_replace('/<[^>]+>/', '', $m->content);
}
```

### OC audit log'undan call var mı kontrol

```sql
SELECT function_slug, status_code, duration_ms, error_message, created_at
FROM oc3_dowaba_audit ORDER BY audit_id DESC LIMIT 10;
```

- Call gelmiyorsa → AI Gemini'ye gitmedi (schema invalid) veya başka exception
- Call geliyor ama status >= 400 → plugin tarafında bug

---

## 11. End-to-End Test Senaryoları

Her plugin için **bu 3 senaryoyu canlı koş**:

### Senaryo 1: Ürün listesi
> "Mağazanızda iPhone var mı? Hangi modeller, fiyatları nedir?"

Beklenen: `{prefix}_product_search` tetiklenir, AI 5-10 ürün listeler markdown link ile.

### Senaryo 2: Karşılaştırma
> "iPhone 13 Pro ile iPhone 15 Pro Max arasındaki fark nedir?"

Beklenen: AI ya `{prefix}_product_compare` ya 2× `{prefix}_product_detail` çağırır, markdown table ile teknik karşılaştırma yapar.

### Senaryo 3: Sipariş durumu
> "Sipariş #1 ne durumda? Email: aydin@dowaba.local"

Beklenen: `{prefix}_order_status` tetiklenir (email match → KVKK), durum + tarih + tutar döner.

### Test data seed örneği

OpenCart 3:
```bash
docker exec dwb-opencart3 php /tmp/seed-iphones.php
# 10 iPhone (11→15 Pro Max) + 6 attribute (RAM, Storage, CPU, Camera, Battery, Display)
# + 1 test order (Processing, $4546, aydin@dowaba.local)
```

---

## 12. Cloudflare Tunnel (lokal test için)

Mağaza localhost'ta — Dowaba prod'un erişebilmesi için tunnel:

```bash
cloudflared tunnel --url http://localhost:8081
# → https://south-enhance-armed-air.trycloudflare.com
```

Manifest controller `HTTP_HOST`'tan resolve ettiği için tunnel domain'ine otomatik adapt olur.

> Quick tunnel ephemeral — her açışta URL değişir. Persistent için `cloudflared tunnel create + DNS`.

---

## 13. Yeni Plugin Yazarken Checklist

- [ ] **Manifest endpoint** public, auth yok, dinamik `base_url` resolve
- [ ] **10 function** standardı (`{prefix}_*` slug pattern)
- [ ] **Parameters JSON Schema strict** — array → items, object → properties
- [ ] **`http_config`** her function'da: method, url_template, query_template/body_template, timeout_ms
- [ ] **API controller** — Bearer auth (sha256 hash) + scope guard + audit log
- [ ] **2-step write flow** — preview (5dk TTL, one-shot) + confirm
- [ ] **Admin UI** — 5-step setup wizard (API key, manifest URL, IP whitelist, scope toggle, test connection)
- [ ] **AJAX endpoints** — `regenerateKey`, `testConnection`
- [ ] **Audit table** — `{prefix}_dowaba_audit` (function_slug, ip, status, duration, error, created_at)
- [ ] **Dosya yapısı** platform native pattern
- [ ] **License header** her PHP dosyasında (@author, @copyright, @license)
- [ ] **Bootstrap exit** — `if (!defined('_<PLATFORM>_VERSION_')) { exit; }`
- [ ] **index.php + .htaccess** (PrestaShop için validator zorunlu)
- [ ] **Marketing assets** — banner.svg/png, thumbnail, MARKETPLACE_LISTING.md (TR + EN), PRIVACY.md, SCREENSHOTS.md, SUBMISSION_CHECKLIST.md
- [ ] **build.sh** — PHP syntax check + ZIP package
- [ ] **docker-compose.yml** — lokal test environment
- [ ] **README.md** — kurulum + dokümantasyon
- [ ] **CHANGELOG.md** — Keep a Changelog formatı, SemVer
- [ ] **e2e test** — 3 senaryo canlı doğrulanmış (product_search + compare + order_status)

---

## 14. Versiyon Yönetimi

- `v0.1.x` — initial release, beta
- `v0.2.x` — production-ready, major feature complete
- `v0.X.Y` — bug fix re-release (aynı major.minor.patch içinde silent re-upload kabul edilir, breaking değilse)
- `v1.0.0` — marketplace approved + 100+ download

### Schema regression nasıl önlenir

1. Manifest controller'da inline parameter schema yerine **per-function method** kullan (OC4 pattern):
   ```php
   private function fnOrderPreview(): array { return [...]; }
   ```
2. Her function method PHP unit testi ile `assertHasItems()`, `assertHasProperties()` doğrula
3. CI: validator.prestashop.com manuel adımı otomatize et

---

## 15. Bilinen Bug'lar / Tuzaklar

| # | Bug | Çözüm |
|---|---|---|
| 1 | OC3 `oc3_orders` (plural) → `oc3_order` (singular) | OC schema farkı, hep singular kullan |
| 2 | OC3 `payment_method` STRING vs OC4 array | OC3 INSERT'lerde string ver, OC4'te array |
| 3 | OC3 `addOrderHistory()` vs OC4 `addHistory()` | OC3-specific method ismi |
| 4 | Gemini `type:array` `items` eksik → fallback | İlgili bölüm §3 |
| 5 | HttpHandler `config.http.url_template` bekler | İlgili bölüm §4 |
| 6 | Bundle Import default pasif | UI'den manuel aktive |
| 7 | Cloudflare quick tunnel her açışta URL değişir | Manifest re-import gerekir veya persistent tunnel kur |
| 8 | OpenCart `editSetting()` aynı code grupta DELETE+INSERT — sıralı çağrılar önceki key'leri siler | Tek call'da tüm key'leri ver |
| 9 | UnifiedAIService Gemini exception swallow | DB'den AI cevabını + audit log'unu oku, binary search |
| 10 | PrestaShop validator strict SQL_MODE — INSERT'lerde tüm NOT NULL kolonlar dolu | `SET SESSION sql_mode=''` — detay [prestashop/MARKETPLACE.md § 5.5](prestashop/MARKETPLACE.md) |

---

## 16. İlgili Dokümanlar (cross-reference)

- **Bu repo:** `opencart/`, `woocommerce/`, `prestashop/`, `shopify/`, `ikas/` — her plugin kendi README + CHANGELOG
- **PrestaShop Marketplace/validation tek otoritesi:** [prestashop/MARKETPLACE.md](prestashop/MARKETPLACE.md) — submission, validator compliance (§8 buraya taşındı), 99 € fee, 9.0 uyumluluk, panel izleme
- **Dowaba ana proje:** `../dowaba/PROJE_HARITASI.md` — Dowaba backend mimarisi
- **Function gateway:** `../dowaba/.../UnifiedAIService.php`, `BundleImporter.php`, `HttpHandler.php`
- **OpenCart memory:** `~/.claude/projects/-Users-aydinacar-Documents-dowaba/memory/feature_brand_resolver_service.md` benzeri
- **Validator:** https://validator.prestashop.com/, https://wordpress.org/plugins/about/svn/, OpenCart Marketplace

---

## 17. Değişiklik Geçmişi

| Tarih | Değişiklik |
|---|---|
| 2026-05-26 | İlk versiyon — OpenCart 3 e2e demo + Gemini schema regression fix |
| 2026-05-30 | Magento 2 (`Dowaba_AiConnector`) port — §18 Magento-spesifik notlar eklendi |

---

**Bir plugin yazarken bu dokümana eklemek istediğin ders varsa: `## 18. Senin notların` bölümü aç ve yaz.** Sonraki geliştirici sana teşekkür edecek.

---

## 18. Magento 2 — platform-spesifik notlar (2026-05-30)

`magento/src/Dowaba/AiConnector/` — OpenCart paritesinde 10 function, ama Magento'nun enterprise framework yapısı her katmanı değiştirir. Slug prefix **`mgm_`** (opc/wcm/psm konvansiyonu). Yeni Magento işine başlamadan oku.

### 18.1 Mimari eşleme (OpenCart → Magento)

| Konu | OpenCart | Magento 2 |
|---|---|---|
| Config storage | `oc_setting` (model_setting_setting) | `core_config_data` (ScopeConfigInterface read + WriterInterface write) |
| Routing | `?route=ext/dowaba_ai/api&action=` | `frontName` → `/dowaba_ai/api?action=` (clean URL, **dot/pipe bug YOK**) |
| DB schema | install.xml runtime CREATE | **declarative `etc/db_schema.xml`** + `db_schema_whitelist.json` (setup:upgrade auto) |
| Audit/SQL | `$this->db->query()` | `ResourceConnection::getConnection()` select builder |
| Cache | OC File cache | `CacheInterface` + `SerializerInterface` (Json) |
| Sipariş | `model_checkout_order->addOrder()` | **Quote API** (QuoteFactory + QuoteManagement::submit) |
| Admin UI | Twig + AJAX | adminhtml controller + Block + phtml + layout XML + ACL + menu.xml |
| Logging | yok | dedicated `di.xml` virtualType → `var/log/dowaba_ai.log` |

### 18.2 Magento-spesifik 6 tuzak (hepsi koda işlendi)

1. **CSRF — frontend POST API 403.** `Controller\Api\Index implements CsrfAwareActionInterface` → `validateForCsrf()=true`, `createCsrfValidationException()=null`. write function'lar (order_preview/confirm POST) bu olmadan 403 alır. **Kritik.**
2. **Config cache staleness.** WriterInterface DB'ye yazar ama ScopeConfig merged cache'i eski kalır → yeni API key auth ETMEZ. Çözüm: `Save`/`RegenerateKey` controller'da `WriterInterface::save` SONRASI `TypeListInterface::cleanType('config')`. (`Model\Config::flushConfigCache`)
3. **`api_key_last_used` her istekte yazılır → config cache flush ETME.** Her API call'da Writer+flush = felaket (her request config rebuild). Çözüm: `Config::saveValueDirect()` raw `core_config_data` insertOnDuplicate (flush yok) + okuma da `getValueDirect()`.
4. **Order create = Quote flow.** Guest order: `quoteFactory->create()` → setCustomerIsGuest + setCustomerEmail → addProduct → billing/shipping addData → `collectShippingRates()->setShippingMethod('flatrate_flatrate')` → `setPaymentMethod('checkmo')` → save → `payment->importData(['method'=>'checkmo'])` → collectTotals → `quoteManagement->submit($quote)`. **flatrate + checkmo default-enabled** (sample data). v0.1 sadece simple product (configurable/bundle addProduct options ister).
5. **Authorization header strip (OpenCart'la aynı).** Apache/LiteSpeed HTTP_AUTHORIZATION düşürür → `Auth::authHeader()` `getServer()` + `getallheaders()` + `?token=` query fallback. Manifest her query_template'e `token:{{connection.credentials.token}}` enjekte eder.
6. **`ObjectManager` doğrudan kullanımı = EQP red.** Sadece constructor DI. `_redirect()` deprecated → `resultRedirectFactory`. phtml'de `$escaper->escapeHtml/Url/HtmlAttr` zorunlu.

### 18.3 base_url farkı

OpenCart: `base_url = host/index.php`, route query'de. Magento: clean URL → `connection.base_url = https://store.com` (sadece scheme+host), `url_template = {{connection.base_url}}/dowaba_ai/api`. **base_url'a path/query KOYMA** (HttpHandler query-drop bug'ı path'i etkilemez ama temiz tut).

### 18.4 Lokal test — 2 ENGEL + çözüm (canlı doğrulandı 2026-05-30)

Magento kurulumu OpenCart/Woo'dan zor. İki tuzak:
1. **`bitnami/magento` imajı KALDIRILDI** (Bitnami 2025 ücretsiz katalog deprecation). Çekilemiyor — kullanma.
2. **`composer create-project magento/...` Adobe `repo.magento.com` auth key ister.**

**Çözüm = public GitHub source** (auth GEREKMEZ): `magento/magento2` repo `app/code/Magento`'da 220 modülü vendor'lar, `composer.json` repo.magento.com deklare etmez → packagist yeter.
```bash
git clone --depth1 -b 2.4.7 https://github.com/magento/magento2.git && composer install --no-dev   # auth yok
# DB+search: magento/docker/docker-compose.yml (MySQL 8 :3309 + OpenSearch 2.12 :9201)
# setup:install + modülü app/code'a kopyala + setup:upgrade → tam runbook: magento/docker/README.md
php -S 127.0.0.1:8087 -t pub phpserver/router.php   # localhost kullan, 127.0.0.1 base_url'a 302 yapar
```
Host PHP 8.1–8.3 + standart extension'lar yeter (ayrı container şart değil). Manifest strict-schema testi Magento'suz da çalışır (Manifest model bağımsız). **Canlı sonuç:** e2e 7/0 PASS, gerçek sipariş `#000000001` (Quote API), write flow + replay 410 + IDOR 404 + audit log hepsi DOĞRULANDI. Tuzaklar: MySQL 8 trigger uyarısı (`log_bin_trust_function_creators=1` ile sus), `show_out_of_stock` ayarı product_search'i etkiler (saygı duyar — bug değil), developer mode ilk istek ~2s (DI üretimi).

### 18.5 Marketplace farkı (OpenCart'tan zor)

Adobe Commerce Marketplace **EQP otomatik kod analizi** yapar (OpenCart manuel form, hızlı approval). PHPCS `Magento2` ruleset 0 error + malware scan + manuel QA (1-4 hafta). Package zip = module root (composer.json top-level). `bash build.sh` iki zip üretir: app/code (manuel install) + Marketplace package. Detay: [magento/marketing/SUBMISSION_CHECKLIST.md](magento/marketing/SUBMISSION_CHECKLIST.md).
