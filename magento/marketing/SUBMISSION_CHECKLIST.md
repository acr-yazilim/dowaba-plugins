# Magento Marketplace — Submission Checklist

Adobe Commerce Marketplace, **Extension Quality Program (EQP)** ile teknik + pazarlama incelemesi yapar. OpenCart/WooCommerce'in aksine **otomatik kod analizi** (PHPCS Magento2 ruleset + Mage compatibility + malware scan) vardır.

## Faz 1 — Teknik hazırlık

- [ ] **composer.json** geçerli — `type: magento2-module`, semver `version`, doğru `require` constraint'leri
- [ ] **registration.php** + **etc/module.xml** `setup_version` composer ile aynı (build.sh sync ediyor)
- [ ] **Magento Coding Standard** 0 error:
      ```bash
      composer require --dev magento/magento-coding-standard
      vendor/bin/phpcs --standard=Magento2 app/code/Dowaba/AiConnector
      ```
- [ ] **PHP Compatibility** — `phpcs --standard=PHPCompatibility --runtime-set testVersion 8.1-8.3`
- [ ] **PHPStan / Magento static** (opsiyonel ama önerilir): `bin/magento dev:tests:run static`
- [ ] **setup:upgrade + setup:di:compile** temiz mağazada hatasız (production mode)
- [ ] **db_schema_whitelist.json** güncel (`bin/magento setup:db-declaration:generate-whitelist --module-name=Dowaba_AiConnector`)
- [ ] **Tek vendor namespace** (`Dowaba\AiConnector\`), PSR-4 doğru
- [ ] **Hardcoded SQL yok** — ResourceConnection select builder kullanıldı (audit + phone lookup parametreli)
- [ ] **Çıktı escaping** — phtml'de `$escaper->escapeHtml/Url/HtmlAttr` (XSS guard)
- [ ] **CSRF** — frontend POST API `CsrfAwareActionInterface` ile bilinçli bypass (Bearer auth gerekçeli)
- [ ] **ACL** — admin controller'lar `ADMIN_RESOURCE = Dowaba_AiConnector::settings`
- [ ] **Malware/obfuscation yok** — tüm kod açık, MIT lisanslı

## Faz 2 — Paketleme

- [ ] `bash build.sh` → `dist/Dowaba_AiConnector-X.Y.Z.zip` (module root zip — Marketplace formatı)
- [ ] Zip içinde `composer.json` kök dizinde
- [ ] `LICENSE` (MIT) dahil
- [ ] Gereksiz dosya yok (`.git`, `.DS_Store`, `node_modules`, `dist/` — build excludes)

## Faz 3 — Marketplace portal

- [ ] [developer.adobe.com/commerce / Marketplace EQP](https://developer.adobe.com/commerce/marketplace/) hesabı
- [ ] Yeni extension submission → composer package zip yükle
- [ ] **Listing:** başlık + kısa/uzun açıklama ([MARKETPLACE_LISTING_EN.md](./MARKETPLACE_LISTING_EN.md))
- [ ] **Görseller:** logo + en az 3 ekran görüntüsü ([SCREENSHOTS.md](./SCREENSHOTS.md))
- [ ] **Privacy Policy** linki ([PRIVACY.md](./PRIVACY.md) → dowaba.com'da yayınla)
- [ ] **Kategori** + anahtar kelimeler
- [ ] **Compatibility:** Magento 2.4.4–2.4.7, PHP 8.1–8.3
- [ ] **External dependency** açıkça belirt: Dowaba SaaS (dowaba.com) — review red sebebi olmasın
- [ ] **Pricing:** Free (platform ayrı abonelik)

## Faz 4 — İnceleme süreci

- [ ] EQP otomatik test (PHPCS/malware) — birkaç saat
- [ ] Manuel QA (Adobe ekibi kurar + test eder) — 1-4 hafta
- [ ] Red gelirse: rapor + düzelt + re-submit
- [ ] Onay → listing canlı

## Bilinen Magento-spesifik tuzaklar

| # | Tuzak | Çözüm |
|---|---|---|
| 1 | `ObjectManager` doğrudan kullanımı → EQP red | Sadece constructor DI (bu modülde uyuldu) |
| 2 | Declarative schema whitelist eksik → setup:upgrade warning | `db_schema_whitelist.json` commit edildi |
| 3 | Frontend POST CSRF → 403 | `CsrfAwareActionInterface` (Api\Index'te var) |
| 4 | Config cache stale (yeni API key görünmüyor) | Writer save sonrası `cleanType('config')` (Save/RegenerateKey'de var) |
| 5 | `_redirect()` deprecated | `resultRedirectFactory` kullanıldı |
| 6 | Raw `$_SERVER` → static analysis uyarısı | `$request->getServer()` kullanıldı (Auth) |
