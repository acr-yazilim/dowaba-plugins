# Dowaba Laravel Bridge

Kendi Laravel projenize **Dowaba SaaS** (WhatsApp / Mail / Instagram / Telegram / SIP AI iletişim platformu) entegrasyonunu **bir composer komutuyla** ekleyin.

> **Durum:** 🚧 İskelet (v0.0.1) — Repo yapısı + ServiceProvider + Facade hazır. Gerçek OAuth flow + HTTP client + Blade component'ler sonraki release'lerde. Yol haritası için [CHANGELOG.md](CHANGELOG.md).

---

## Neden?

Kendi e-ticaret / hastane / randevu / otel yazılımınız var. Müşterilerinize WhatsApp, Mail veya AI çağrı kanallarını eklemek istiyorsunuz. Dowaba'nın hazır altyapısını **kendi panelinizde, kendi tasarımınızla** kullanın — kullanıcılarınız Dowaba'ya bile uğramak zorunda kalmasın.

```php
// Sipariş kargoya verildi → otomatik WhatsApp template
use Dowaba\LaravelBridge\Facades\Dowaba;

Dowaba::whatsapp()->template(
    phone: $order->customer->phone,
    template: 'order_shipped',
    params: ['name' => $order->customer->name, 'code' => $order->tracking_code]
);
```

```blade
{{-- Kendi paneline gömülü chat penceresi --}}
<x-dowaba::conversation-list :site-id="config('dowaba.widget.site_id')" />
<x-dowaba::chat-window :conversation-id="$selectedId" height="600" />
```

---

## 5 Dakikada Kurulum

```bash
# 1. Paketi kur
composer require dowaba/laravel-bridge

# 2. Config + migration publish
php artisan vendor:publish --tag=dowaba-config
php artisan vendor:publish --tag=dowaba-migrations
php artisan migrate

# 3. Dowaba admin → /admin/oauth/clients → Yeni Client
#    Redirect URI: https://senin-domain.com/dowaba/auth/callback
#    Allowed scopes: openid profile email offline_access (MVP)

# 4. .env doldur
echo "DOWABA_URL=https://dowaba.com" >> .env
echo "DOWABA_CLIENT_ID=dosc_xxx" >> .env
echo "DOWABA_CLIENT_SECRET=dosec_xxx" >> .env
echo "DOWABA_REDIRECT_URI=\${APP_URL}/dowaba/auth/callback" >> .env
echo "DOWABA_WIDGET_SITE_ID=site_xxx" >> .env
echo "DOWABA_WIDGET_SECRET=<sites.widget_secret>" >> .env

# 5. Bağlantı testi
php artisan dowaba:test-connection
```

---

## Facade Method'ları (planlı)

```php
use Dowaba\LaravelBridge\Facades\Dowaba;

// Kanal gönderim
Dowaba::whatsapp()->send($contactId, 'Merhaba');
Dowaba::whatsapp()->template($phone, 'order_shipped', ['code' => '1234']);
Dowaba::channels()->mail()->send($contactId, $subject, $html);

// Konuşma yönetimi
Dowaba::conversations()->list($siteId, ['channel' => 'wa', 'status' => 'open']);
Dowaba::conversations()->get($conversationId);
Dowaba::conversations()->sendMessage($conversationId, 'Cevap');
Dowaba::conversations()->close($conversationId);

// Kişi yönetimi
Dowaba::contacts()->create($siteId, ['phone' => '+90...', 'name' => 'Ali']);
Dowaba::contacts()->upsertByPhone($siteId, '+90...', ['name' => 'Ali']);

// Site bilgisi
Dowaba::sites()->all();
Dowaba::sites()->get($siteId);

// AI function gateway
Dowaba::aiFunctions()->execute($siteId, 'order_status', ['order_id' => 42]);
```

---

## Blade Component'leri (planlı)

```blade
{{-- Dowaba ile giriş butonu --}}
<x-dowaba::login-button>Dowaba ile bağlan</x-dowaba::login-button>

{{-- HMAC-imzalı widget script (kullanıcıya özel) --}}
<x-dowaba::widget-script :site-id="$siteId" :user="auth()->user()" />

{{-- Konuşma listesi (server-side render) --}}
<x-dowaba::conversation-list :site-id="$siteId" />

{{-- Tek konuşma chat penceresi (iframe) --}}
<x-dowaba::chat-window :conversation-id="$id" height="500" />

{{-- Kişi oluşturma formu --}}
<x-dowaba::contact-create-form :site-id="$siteId" action="/my/contacts/save" />
```

---

## Yol Haritası

| Versiyon | Kapsam | ETA |
|---|---|---|
| **v0.0.1** | İskelet — composer + ServiceProvider + Facade + stub Resource sınıfları | ✅ 2026-05-25 |
| **v0.1.0** | OAuth flow (PKCE + state) + DowabaClient (Guzzle) + token refresh | 2026-06 |
| **v0.2.0** | Blade component'ler (5 adet) + Inertia/Livewire bridge | 2026-06 |
| **v0.3.0** | Artisan commands (`dowaba:install`, `dowaba:test-connection`, `dowaba:rotate-client-secret`) | 2026-06 |
| **v0.4.0** | HMAC widget token üreteci + widget-script component | 2026-07 |
| **v0.5.0** | Webhook receiver + signature verifier | 2026-07 |
| **v1.0.0** | Pest test suite ≥95% + Packagist publish + dökümantasyon sitesi | 2026-08 |

---

## Çalışma Mimarisi

```
┌───────────────────────────────────────────────────────────────┐
│   Yazılımcı'nın Laravel projesi (örn. hastane.com)            │
│                                                                │
│   composer require dowaba/laravel-bridge                       │
│                                                                │
│   ┌─────────────────────────────────────────────────────────┐ │
│   │  Dowaba Laravel Bridge                                  │ │
│   │  ─────────────────────                                  │ │
│   │  Facade:  Dowaba::whatsapp()->send(...)                 │ │
│   │  Auth:    OAuth 2.0 PKCE + Trusted Partner Sanctum PAT  │ │
│   │  HTTP:    Guzzle → dowaba.com/api/* (scope-aware)       │ │
│   │  UI:      Blade components (Inertia/Livewire/SSR)       │ │
│   │  Widget:  HMAC-signed user token + 5dk session          │ │
│   └─────────────────────────────────────────────────────────┘ │
│                          ↕ (HTTPS, OAuth Bearer)              │
└──────────────────────────────────────────────────────────────┘
                              ↕
                  ┌────────────────────────┐
                  │  dowaba.com (SaaS)     │
                  │  • WhatsApp Cloud API  │
                  │  • Mail / SMTP+IMAP    │
                  │  • Instagram Graph     │
                  │  • Telegram Bot API    │
                  │  • SIP (Kamailio+AST)  │
                  │  • AI (UnifiedAI)      │
                  └────────────────────────┘
```

---

## Lisans

[MIT](LICENSE) © [Aydın Acar](https://aydinacar.net) / Dowaba

---

## İletişim / Destek

- 🌐 https://dowaba.com
- 📧 destek@dowaba.com
- 📖 [Dökümantasyon](https://dowaba.com/api-docs) · [Örnek Projeler](https://dowaba.com/api-docs-ornekler)
- 🐛 [Issue açın](https://github.com/rdtvaacar/dowaba-plugins/issues)
