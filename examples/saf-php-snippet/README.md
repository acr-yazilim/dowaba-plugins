# Dowaba — Saf-PHP Snippet

> **Framework YOK.** Sadece düz PHP + curl + HMAC. WordPress eklentisi yazıyorsan, Symfony küçük servisinde Dowaba'ya bağlanmak istiyorsan, ya da CLI cron job'undan WhatsApp/Mail göndermek istiyorsan bu dizini referans al.

---

## İçerik

```
saf-php-snippet/
├── lib/dowaba.php        ← 3 helper fonksiyon (request / verify_webhook / widget_token)
├── config.example.php    ← config.php olarak kopyala, değerleri doldur
├── send-whatsapp.php     ← CLI veya HTTP — mesaj gönder
├── webhook-receive.php   ← Dowaba'dan gelen webhook'u alır + HMAC verify
├── widget-embed.html     ← Sayfana widget eklemek için HTML örnek
└── tests/smoke-test.php  ← Kurulum sonrası "her şey OK mi" smoke test
```

---

## Kurulum (2 dakika)

```bash
# 1. Klonla veya bu klasörü kopyala
git clone https://github.com/acr-yazilim/dowaba-plugins.git
cd dowaba-plugins/examples/saf-php-snippet

# 2. Config dosyasını oluştur
cp config.example.php config.php

# 3. config.php'yi aç + Dowaba admin panelinden aldığın değerleri yapıştır:
#    - DOWABA_ACCESS_TOKEN (OAuth Bearer token veya Trusted Partner Sanctum PAT)
#    - DOWABA_WEBHOOK_SECRET (Dowaba admin → site → Webhooks → Secret)
#    - DOWABA_WIDGET_SITE_ID / DOWABA_WIDGET_SECRET (Dowaba admin → site detayı)

# 4. Smoke test çalıştır
php tests/smoke-test.php
```

---

## Örnek 1: CLI'dan WhatsApp mesajı gönder

```bash
php send-whatsapp.php "+905551234567" "Merhaba, randevunu hatırlatmak istedik."
```

Çıktı (başarılı):
```json
{
    "sent": true,
    "status": 200,
    "response": { "message_id": "wamid_...", "queued": true }
}
```

Cron job örneği — her gün saat 9'da hatırlatma:
```cron
0 9 * * * cd /path/to/saf-php-snippet && php send-whatsapp.php "+905..." "Günaydın!"
```

---

## Örnek 2: Webhook receiver (Dowaba → sizin sistem)

Dowaba bir event tetiklediğinde (yeni mesaj geldi, sipariş durumu değişti vb.) sizin endpoint'inize POST atar:

```
POST https://siz.com/dowaba-webhook.php
X-Dowaba-Signature: sha256=abc123...
Content-Type: application/json

{ "event": "message.received", "data": { ... } }
```

`webhook-receive.php` HMAC imzasını otomatik doğrular, sonra `switch ($payload['event'])` bloğunda kendi iş mantığınızı yazarsınız.

**Test:**
```bash
SECRET="senin_webhook_secret"
BODY='{"event":"message.received","data":{}}'
SIG="sha256=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "$SECRET" -hex | awk '{print $2}')"

curl -X POST http://localhost:8000/webhook-receive.php \
     -H "Content-Type: application/json" \
     -H "X-Dowaba-Signature: $SIG" \
     -d "$BODY"
```

---

## Örnek 3: Widget embed (sayfana destek butonu)

`widget-embed.html` dosyasına bak — anonim ve login'li kullanıcı için 2 ayrı pattern.

**Kısa versiyon (anonim):**
```html
<script
    src="https://dowaba.com/widget.js"
    data-destek-key="SENIN_SITE_API_KEY"
    async defer></script>
```

**Login'li kullanıcı (HMAC-imzalı token):**
```php
$token = dowaba_widget_token(
    payload: ['user_id' => $_SESSION['uid'], 'email' => $_SESSION['email'], 'site_id' => DOWABA_WIDGET_SITE_ID],
    secret: DOWABA_WIDGET_SECRET,
    ttl: 300
);
```
```html
<script src="https://dowaba.com/widget.js"
        data-destek-key="<?= DOWABA_WIDGET_SITE_ID ?>"
        data-user-token="<?= htmlspecialchars($token) ?>"
        async defer></script>
```

---

## Helper Fonksiyonlar

`require __DIR__.'/lib/dowaba.php'` ile yüklenir:

| Fonksiyon | Amaç |
|---|---|
| `dowaba_request($method, $path, $body, $opts)` | curl + JSON + Bearer auth. Return: `['status', 'data', 'raw']` |
| `dowaba_verify_webhook($body, $signature, $secret)` | HMAC-SHA256 imza doğrula. Return: bool |
| `dowaba_widget_token($payload, $secret, $ttl)` | widget_user_token üret. Return: string `payload.signature` |

---

## Hangi PHP Sürümü?

- **Minimum:** PHP 8.1 (`array_filter` callable type, named args)
- **Tavsiye:** PHP 8.2 veya 8.3
- **Gereksinim:** `curl`, `json`, `hash` extension'ları (Apache/Nginx + PHP-FPM kurulumlarında default açık)

---

## Lisans

[MIT](LICENSE) — istediğin gibi kopyala, değiştir, dağıt.

---

## İletişim / Destek

- 🌐 https://dowaba.com
- 📖 [Dökümantasyon](https://dowaba.com/api-docs) · [Örnek Projeler](https://dowaba.com/api-docs-ornekler)
- 🐛 [Issue açın](https://github.com/acr-yazilim/dowaba-plugins/issues)
