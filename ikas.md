# İkas Entegrasyonu — Tek Otorite Doküman

> **Durum (2026-07-01): CANLI (Beta).** İki bağlanma yolu: **Private App "Hızlı Bağlan"** (canlı, önerilen)
> ve **Public App OAuth** (kod hazır, İkas Partner/App Store onayı sürüyor).
> Diğer plugin'lerden farklı: İkas SaaS olduğu için mağazaya **dosya yüklenmez** — tüm kod
> DoWaba Laravel backend'inde yaşar (bu repoda İkas klasörü yok; bu doküman mimari referansıdır).

## 1. İkas'ın iki uygulama modeli (araştırma-doğrulanmış)

| | **Private App (Özel Uygulama)** | **Public/Admin App (App Store)** |
|---|---|---|
| Grant | `client_credentials` | `authorization_code` (+ refresh, rotate edilir) |
| Kim oluşturur | Mağaza sahibi kendi panelinden (Uygulamalar → Özel Uygulamalar) | Geliştirici, Partner hesabıyla |
| Partner onayı | **Gerekmez** | Gerekir |
| refresh_token | Yok (süre bitince yeniden mint) | Var |
| Token ömrü | 14400 sn (4 saat) | Belirsiz (defensive 1 saat + refresh) |

- **Token endpoint (store-specific):** `https://{store}.myikas.com/api/admin/oauth/token`
- **GraphQL endpoint (merkezi):** `https://api.myikas.com/api/v1/admin/graphql` (Bearer) — İkas yalnız GraphQL sunar, REST yok.
- Rate limit: 10 istek/10 sn + **hata-oranı bazlı otomatik bloklama** (kalıcıya kadar gidebilir) — hatalı query akıtma.

## 2. DoWaba implementasyonu (dowaba repo, backend/)

| Katman | Dosya |
|---|---|
| Migration | `2026_11_24_000001_create_ikas_connections_table.php` + `2027_01_23_000001_add_private_app_to_ikas_connections.php` (`app_type`, `client_id`, `client_secret` — encrypted) |
| Model | `app/Models/IkasConnection.php` (`isPrivate()`, tokenUrl(), encrypted cast'ler, site_id UNIQUE) |
| OAuth service | `app/Services/IkasOAuthService.php` — `authorizeUrl/exchangeCode/refresh` (Public) + `acquireClientCredentialsToken/remintClientCredentials` (Private); `ensureFresh()` polymorphic |
| GraphQL service | `app/Services/IkasGraphQLService.php` — 10 fonksiyon (aşağıda) |
| Controllers | `IkasOAuthController` (start/callback/quickConnect/index/destroy) + `IkasManifestController` + `IkasProxyController` |
| Routes | sanctum: `POST ikas/oauth/start`, `POST ikas/connections/quick-connect`, `GET/DELETE ikas/connections*` · public: `GET ikas/oauth/callback`, throttle:120 `ikas/manifest/{token}` + `ikas/proxy/{token}/{action}` |
| Frontend | `admin/src/views/integrations/SiteIkasIntegrationView.vue` — 2 sekme: "API Anahtarı (Hızlı)" (default) + "İkas Onayı" (OAuth popup) |

**Akış (Hızlı Bağlan):** kullanıcı İkas'ta Özel Uygulama açar (izinler: ürün/sipariş/stok/müşteri) →
client_id+secret'i DoWaba paneline girer → token doğrulanır → `ikas_connections` satırı (`app_type='private'`,
anahtarlar encrypted) → `BundleImporter::importFromManifest` ile 10 AI fonksiyonu **otomatik aktif**.
Token süresi dolunca saklı anahtarla otomatik yeniden üretilir (kullanıcı hiç uğraşmaz).

## 3. 10 AI fonksiyonu

`ikas_product_search · product_detail · product_compare · stock_check · category_list ·
order_status (KVKK email eşleşmesi) · customer_lookup · cart_recover (storefront addtocart linki) ·
order_preview (5dk cache) · order_confirm`

⚠️ **`order_confirm` = SKELETON (v0.1):** gerçek `createOrderWithTransactions` mutation'ı finalize edilmedi —
müşteri sepet linkiyle mağazaya yönlendirilir. Finalize için canlı İkas mağazası + GraphQL introspection gerekir.

## 4. Açık işler

1. `order_confirm` finalize (canlı mağaza gerekli)
2. `order_preview` kargo/vergi hard-coded (≥500₺ ücretsiz / 49₺) → gerçek API'ye bağlanmalı
3. Public App yolu: İkas Partner onayı bekleniyor (2026-05-24 başvuru) → onaylanınca App Store listing (Faz 6)
4. Canlı credential ile GraphQL alan-eşleşmesi doğrulaması (Beta etiketi bu yüzden)
5. v1 → v2 GraphQL geçişi (İkas v2 öneriyor; introspection diff sonrası)

## 5. İlgili kaynaklar

- Memory: `project_ikas_plugin.md` + `project_tr_ecommerce_integrations_2026_07_01.md` (dowaba memory dizini)
- İkas resmi: `ikas.dev/docs/api/getting-started/authentication` · `builders.ikas.com/docs/app-development`
- Aynı deseni izleyen kardeş entegrasyonlar (hepsi dowaba backend'inde): Shopier (PAT), İdeaSoft (OAuth2),
  T-Soft (rest1), N11/Hepsiburada/Pazarama/Çiçeksepeti (satıcı anahtarı), BigCommerce/Wix (token)
