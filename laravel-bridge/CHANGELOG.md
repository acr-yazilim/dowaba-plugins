# Changelog

Tüm önemli değişiklikler bu dosyada tutulur.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · Sürümlendirme: [SemVer](https://semver.org/lang/tr/).

## [Unreleased]

### Planlanan
- OAuth 2.0 PKCE flow (`OauthFlow` + `PkceHelper` + `TokenStore`)
- Trusted Partner Sanctum PAT istemci-side cache (`TrustedPartnerToken`)
- DowabaClient (Guzzle wrapper + retry + scope-aware)
- 5 Blade component (chat-window, conversation-list, contact-create-form, widget-script, login-button)
- HmacSigner — widget_user_token üreteci (HMAC-SHA256)
- Artisan komutları: `dowaba:install`, `dowaba:test-connection`, `dowaba:rotate-client-secret`
- Pest test suite (Unit + Feature + Integration)
- Inertia/Vue/Livewire bridge örnekleri

## [0.0.1] — 2026-05-25

### Eklendi (İskelet)
- Composer paketi `dowaba/laravel-bridge` (PSR-4: `Dowaba\LaravelBridge\`)
- `DowabaBridgeServiceProvider` — config publish + migrations + routes + Blade namespace + console commands
- `DowabaManager` ana entry + `Dowaba` Facade
- Resource sınıfları stub: WhatsApp, Channels, Conversations, Contacts, Sites, AiFunctions
- `config/dowaba.php` yapılandırması (url, client_id, scopes, widget, http, token_store, jwks_cache_ttl)
- Artisan komut iskeletleri (henüz "iskelet" uyarısı dönüyor)
- Klasör hierarchy: Auth/, Resources/, Http/Controllers/, Http/Middleware/, Blade/, Console/, Support/, Webhooks/, Inertia/
- `routes/web.php` placeholder + `/dowaba/auth/_skeleton` debug endpoint
- README + LICENSE (MIT) + CHANGELOG

### Notlar
- Bu sürüm **üretime hazır değil** — gerçek HTTP çağrıları, OAuth flow, Blade component'ler eksik.
- Repo iskeletinin amacı: paket yapısının doğru çalıştığını composer/Laravel autodiscovery testi ile kanıtlamak.
