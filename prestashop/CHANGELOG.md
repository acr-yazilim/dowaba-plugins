# Changelog

Tüm önemli değişiklikler bu dosyada listelenir. [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) formatı, [Semantic Versioning](https://semver.org/spec/v2.0.0.html) kuralları.

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
