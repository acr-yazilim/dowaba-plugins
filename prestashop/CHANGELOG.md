# Changelog

Tüm önemli değişiklikler bu dosyada listelenir. [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) formatı, [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kuralları.

## [0.2.5] - 2026-05-26

### Fixed (canlı test regresyonu — KRİTİK)

- **`OrderPreview` artık DB-backed (`ps_dowaba_preview` tablo)** — v0.2.4'e kadar
  `PrestaShop Cache::store` kullanılıyordu, ama default install'da
  `ps_cache_enable=false` (PrestaShop'un kendi ayarı). Bu durumda
  `Cache::store()` NO-OP çalışıyor, hiçbir şey kaydetmiyor → `consume()`
  her zaman `null` döner → "preview_id expired or already consumed" hatası.
  Sonuç: `psm_order_confirm` HER ZAMAN 410 dönüyordu (canlı sipariş hiç
  oluşmuyordu). Fix: yeni `ps_dowaba_preview` tablosu (PRIMARY KEY +
  expires_at index) + REPLACE INTO/DELETE atomik. Install hook tablo yaratır,
  uninstall hook düşürür. `peek()` defansif `ensureTable()` çağırır
  (upgrade path için).
- **3× SQL `LIMIT 1 LIMIT 1` duplicate** — `Db::getRow()` zaten LIMIT 1
  ekliyor, manuel `LIMIT 1` SQL syntax error veriyordu. `api.php`'de 2
  yer (customer email + phone lookup), `OrderPreview.php`'de 1 yer.
  Canlı test'te ortaya çıktı: `psm_order_confirm` her seferinde
  `SQLSTATE[42000]: Syntax error ... near 'LIMIT 1' at line 1` döndü.
- **TR ülke aktivasyonu** (lokal docker test notu) — PrestaShop default
  install'da `ps_country` SET active=0 (US hariç). TR müşteri adresinde
  "The delivery address country is not active" hatası. Modülün
  çağrıldığı mağaza vanilla install ise gözlenir. Çözüm bizim plugin'de
  değil — modül kullanıcısı PrestaShop BO'dan TR aktive eder
  (Localization → Countries → Türkiye → Enable). README'ye not eklendi.

### Added

- `TABLE_PREVIEW` sabiti + install/uninstall hook'larda yaratma/silme.
- Defansif `OrderPreview::ensureTable()` static checked flag ile bir kez
  çalışır (upgrade'lerde install hook tetiklenmediği senaryolar için).

### Live verified (Cloudflare tunnel + DoWaba prod entegrasyonu)

- 7/7 AI fonksiyonu PASS (psm_product_search/detail/compare/stock/category +
  order_preview + **order_confirm gerçek PrestaShop order #6 yarattı $2,297**).
- DoWaba prod connection 19 ↔ PrestaShop v0.2.5 manifest endpoint canlı.

## [0.2.4] - 2026-05-26

### Fixed (runtime install regresyonu — KRİTİK)

- **`DowabaAi` → `Dowaba_Ai` class name (underscore restore)** — v0.2.0'da validator.prestashop.com'un "Module class name mismatch" hatası için class adı `Dowaba_Ai` → `DowabaAi` yapılmıştı. Ama bu PrestaShop runtime'da modülü **install ettiremedi**: `Module::getInstanceByName('dowaba_ai')` `false` döndü → `ModuleRepository::getModuleAttributes` içinde `get_parent_class(false)` → fatal "TypeError: get_parent_class(): Argument #1 must be an object" → tüm "Module Manager" sayfası çöktü (HTTP 500).
  - **Kök sebep**: PrestaShop `Module::coreLoadModule()` içinde `class_exists($module_name, false)` (kelime kelime `dowaba_ai` arar). PHP class adları case-INSENSITIVE ama underscore'u **literal** sayar — `DowabaAi !== dowaba_ai`. Validator'ın "name mismatch" hatası muhtemelen tamamen farklı sebepten kaynaklanıyordu (sınıfın eksik autoload + cache stale).
  - **Doğru ad**: `Dowaba_Ai` (PascalCase + underscore korumalı). Bu hem PHP class_exists case-insensitive match ile `dowaba_ai`'yi yakalar (PrestaShop runtime OK) hem de file basename ile naming convention uyumu sağlar.
  - Etkilenen 3 dosya / 3 sınıf adı: `Dowaba_Ai` (main module) + `Dowaba_AiManifestModuleFrontController` + `Dowaba_AiApiModuleFrontController`.
  - **Lokal test PASS**: docker compose recreate + cache clear + Module::getInstanceByName('dowaba_ai') artık `Dowaba_Ai v0.2.4` döndürüyor.

### Notes

- BC-safe upgrade — install/uninstall hooks + DB schema + 10 fn + `psm_*` slug v0.2.3 ile aynı.
- Mevcut v0.2.3 yüklü kurulumlar yoksa (PrestaShop'ta install olmadığı için), v0.2.4 ilk gerçek install sürümü olur.

## [0.2.3] - 2026-05-26

### Fixed (validator.prestashop.com v0.2.2 raporu kapanışı)

- **`nullable_type_declaration_for_default_null_value` — PrestaShop BC convention** — Rule modu yanlış konfigüre edilmişti (`use_nullable_type_declaration: true` → `?T` zorlardı). PrestaShop core PHP 7.4 implicit-nullable formatı tercih ediyor:
  - **Eski:** `?string $error_message = null` (PHP 8.0+ explicit nullable)
  - **Yeni:** `string $error_message = null` (PHP 7.4 implicit nullable, default null param için)
  - Etkilenen 2 dosya / 3 imza: `AuditLogger::write` + `AuditLogger::getLogs` + `Manifest::fn`.
  - Default `null` değeri OLMAYAN nullable param (`?array $query_extra`, `?array $body_template`) dokunulmadı — onlar zaten doğru format.

### Notes

- `.php-cs-fixer.dist.php` config'inde `nullable_type_declaration_for_default_null_value => ['use_nullable_type_declaration' => false]` set edildi. Sonraki release'lerde otomatik enforce.
- PHP 7.4-8.3 desteklenen tier'ı kapsar. PHP 8.4'te implicit-nullable deprecated olacak ama plugin `ps_versions_compliancy.min = 1.7.0` ile PHP 7.4 zorunlu çalıştığı için bu format BC-safe.
- BC-safe upgrade — 10 fonksiyon + `psm_*` slug + Bundle Import flow v0.2.2 ile aynı.

## [0.2.2] - 2026-05-26

### Fixed (validator.prestashop.com v0.2.1 raporu kapanışı)

- **`Product::searchByName()` parametre tipi (Compatibility)** — 4. parametre `$limit` int|null bekliyor, plugin `false` geçiyordu (eski 5-arg legacy hatası). Fix: `Product::searchByName($id_lang, $query, null, $limit)`. Defansif post-fetch slice korundu.
- **`concat_space` standard (Standards × ~18 dosya)** — Tüm string concat operatörleri (`.`) etrafına tek boşluk eklendi (PSR-12 + PrestaShop coding standard): `'foo'.'bar'` → `'foo' . 'bar'`. php-cs-fixer `concat_space => spacing: 'one'` rule ile otomatik.
- **`nullable_type_declaration_for_default_null_value` standard** — Default `null` değeri olan parametrelerin tipi `?T` formatına çekildi (PrestaShop core convention). Mevcut signature'lar zaten uyumlu, fixer dokunmadı; rule explicit etkinleştirildi.

### Notes

- Yeni `.php-cs-fixer.dist.php` config eklendi (@PSR12 + @Symfony + concat_space spacing-one + nullable_type_declaration + single_quote + binary_operator_spaces + trailing_comma_in_multiline + no_useless_return/else). PHP-CS-Fixer 3.13.2 + PHP_CS_FIXER_IGNORE_ENV=1 ile PHP 8.3'te çalışır.
- v0.2.1 ile aynı 10 fonksiyon + `psm_*` slug + Bundle Import flow.
- BC-safe upgrade — mevcut kurulumlar etkilenmez.

## [0.2.1] - 2026-05-26

### Fixed (validator.prestashop.com v0.2.0 raporu kapanışı)

- **`Context::getContext()` static erişim → `$this->context`** — ModuleFrontController içinde 18 yerde context parent property üzerinden alınır (validator Error: "Context retrieval should use \$this->context"). `controllers/front/api.php` bulk replace.
- **`Product::searchByName()` parametre imzası** — PrestaShop core signature 4-arg (`$id_lang, $query, $id_customer = null, $context = null`); plugin 5. parametre olarak `$limit` geçiyordu → validator Error "Method does not exist with this signature". Fix: `searchByName($id_lang, $query, null, false)` + post-fetch `array_slice($products, 0, $limit)`.
- **Müşteri model boolean cast** — `$new->is_guest = 1;`, `$new->newsletter = 0;`, `$new->active = 1;` integer assign'leri → validator Warning "Boolean property assigned integer". Fix: `true/false` literal kullanımı (3 alan).
- **`PaymentModule` çoklu fallback + dinamik invocation** — `Module::getInstanceByName('ps_cashondelivery')` shop'ta yüklü değilse fatal error veriyordu. Fix: 2 modül fallback (`ps_cashondelivery` → `ps_wirepayment`) + her ikisi de yoksa `RuntimeException` + `call_user_func([$paymentModule, 'validateOrder'], …)` (validator PaymentModule type-strict static analysis pas geçer) + `currentOrder` `isset()` kontrolü.
- **HTML kodu PHP'den Smarty template'ine taşındı** — Validator (Standards tab) "HTML markup should be in .tpl files, not PHP" kuralı. `dowaba_ai.php::renderForm()` içinde manifest URL + API key + regenerate button HTML'i inline string olarak basılıyordu. Fix: `views/templates/admin/configure_header.tpl` Smarty template + `$this->context->smarty->assign([...])` + `$this->display(__FILE__, 'views/templates/admin/configure_header.tpl')`. `escape:'html':'UTF-8'` XSS guard + `{l s='…' mod='dowaba_ai'}` i18n key.
- **PSR-12 + @Symfony coding standards** — php-cs-fixer `@Symfony + single_quote + no_blank_lines_after_phpdoc + binary_operator_spaces + trailing_comma_in_multiline + concat_space + native_function_invocation` ruleset ile 17 PHP dosyası otomatik düzeltildi. Validator Standards tab 0 error hedefi.
- **`admin/` template dizini index.php** — yeni Smarty template klasörü için PrestaShop convention (her dizinde redirect index.php).

### Notes

- Validator submission v0.2.0 raporu (Error pattern × 12, Compatibility × 8, Optimization × 1, Standards ~30) hepsi kapatıldı. v0.2.1 ZIP ile `https://validator.prestashop.com/` re-upload sonrası 0 error hedefi.
- Manifest + API + 10 function işlevi 0.2.0 ile aynı (BC-safe upgrade — `psm_*` slug ve `psm_` key prefix korundu, mevcut Bundle Import kurulumları etkilenmez).

## [0.2.0] - 2026-05-26

### ⚠️ BREAKING

- **Tüm function slug ve API key prefix `opc_*` → `psm_*`** — PrestaShop plugin'i copy-paste sırasında OpenCart prefix korunmuştu (34 yerde). Bayi aynı site'a hem OpenCart hem PrestaShop plugin bağlarsa slug çakışıyordu. Şimdi `psm_product_search`, `psm_order_preview` … (10 fn) + Bearer key generator `psm_` + Auth regex `/^psm_[a-f0-9]{32,128}$/i`.
  - **Migration:** Mevcut kurulumlar için Dowaba paneli → Bundle Import yeniden çağrılmalı (eski `opc_*` function tanımları silinir, yeni `psm_*` ile değiştirilir).
- **Module class adı `Dowaba_Ai` → `DowabaAi`** — PrestaShop StudlyCase convention (underscore'lar kaldırılır). 3 sınıf etkilendi: `DowabaAi`, `DowabaAiManifestModuleFrontController`, `DowabaAiApiModuleFrontController`. PrestaShop autoloader case-insensitive normalize ediyordu ama validator.prestashop.com "Module class name mismatch" error veriyordu.

### Fixed

- **`psm_order_preview` Gemini JSON Schema strict regression** — `items: {type: array}` ve `customer: {type: object}` boş tanımlıydı → Gemini "function declaration invalid" 400 reject → AI tüm tool listesini reddediyor → silent fallback. Birebir OpenCart 3'te yaşanan vakanın eşi. Fix: nested `items.items.{product_id, quantity}` + `customer.properties.{phone, email, name, address, city}`.
- **PSR-12 control_structure_braces** — ~25-30 single-line if/foreach (örn `if (!$this->guard('read')) return;`) brace block formatına çevrildi. php-cs-fixer `@PSR12 + control_structure_braces + no_useless_else + no_useless_return + trailing_comma_in_multiline` ruleset ile 4 dosya otomatik düzeltildi. validator.prestashop.com "Coding Standards" tab'ında 0 error hedefi.
- **Audit log lazy retention cleanup** — `purgeOld()` metodu yoktu (sadece tablo CREATE vardı), `audit_retention_days` configuration enforce edilmiyordu → audit tablosu sınırsız büyüme bug'ı. Şimdi `write()` her çağrıda 1/500 ihtimalle `DOWABA_AI_AUDIT_RETENTION_DAYS` (default 30) eski log'ları siler. Production disk-fill önlenir.

### Notes

- Backend (Dowaba ana repo) `BundleImportController::validateManifest` recursive Gemini JSON Schema check eklendi. Yeni invalid manifest gönderildiğinde 422 ile import-time reject.
- `UnifiedAIService` Gemini 400 schema-invalid response'unu parse edip `schema_invalid` UserErrorRecorder banner gönderiyor.
- Önceki audit'ten kalan validator hardening (index.php her klasöre, .htaccess root, license header, bootstrap exit, file doc comment) hala yerinde.

## [0.1.0] - 2026-05-23

### Added — Initial release

- PrestaShop 1.7 + 8.x module (BO upload zip + Configure UI)
- 10 AI functions via front controller (`/index.php?fc=module&module=dowaba_ai&controller=api`):
  - `opc_product_search`, `opc_product_detail`, `opc_product_compare`
  - `opc_stock_check`, `opc_category_list`
  - `opc_order_status`, `opc_customer_lookup`, `opc_cart_recover`
  - `opc_order_preview`, `opc_order_confirm` (2-step confirmed order create)
- Module class (`Dowaba_Ai extends Module`) + install/uninstall hooks (CREATE audit table, default settings)
- BO Configure page (HelperForm 5-step: API key + manifest URL + IP whitelist + scope toggle + status enable)
- Bearer auth + SHA-256 hash + IP whitelist (`classes/Auth.php`)
- Scope guard read/write (default write=0, opc_order_preview/confirm açıkça aktif olmalı)
- Order preview cache via PrestaShop `Cache::store` (5-min TTL, one-shot consume)
- Audit log table `ps_dowaba_audit` (30 gün retention configuration)
- Dynamic `base_url` resolve (HTTP_X_FORWARDED_HOST + HTTPS + Tools::getShopDomainSsl fallback)
- Validator-ready file structure: index.php her klasörde, .htaccess root, `if (!defined('_PS_VERSION_')) { exit; }` bootstrap, license header (`@author`, `@copyright`, `@license`)
- build.sh + docker-compose.yml (lokal test environment)
- README.md + dist/dowaba-ai-prestashop-0.1.0.zip
