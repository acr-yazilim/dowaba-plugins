# Hastane Randevu — Dowaba Bridge Demo

> Laravel 12 + `dowaba/laravel-bridge` ile **tamamen çalışan** hastane randevu sistemi. Yeni randevu eklendiğinde hastaya WhatsApp template otomatik gider, 1 gün öncesi cron hatırlatma yollar.

🌐 Daha fazla örnek: https://dowaba.com/api-docs-ornekler

---

> ## ⚠️ GÜVENLİK UYARISI — DEMO KOD
>
> Bu demo'da route'lara **authentication middleware EKLENMEMİŞTİR.** Herhangi biri `/acr/appointments` URL'ini çağırıp randevu oluşturabilir, iptal edebilir.
>
> **Production'a almadan ÖNCE:**
> 1. `routes/web.php`'deki `Route::prefix('acr')` group'una `->middleware('auth')` ekle
> 2. Hassas action'lara `Gate`/`Policy` ekle (`cancel` sadece doktor/admin)
> 3. `.env`'de `APP_DEBUG=false`
> 4. Public form için rate limit (`throttle:10,1`)

---

## Özellikler

- 🏥 **Dashboard** — toplam randevu / yaklaşan / hasta / doktor istatistikleri + bugünün randevuları
- 📋 **Randevu listesi** — pagination, durum badge, hatırlatma izi, iptal butonu
- ➕ **Yeni randevu** — hasta/doktor seçim + tarih + otomatik **WhatsApp onay mesajı**
- 🔔 **Hatırlatma cron** — `php artisan appointments:send-reminders` her gün 09:00, 24 saat öncesi randevulara WhatsApp template
- 💬 **Widget** — sağ alt köşede Dowaba destek butonu (ziyaretçi için)

---

## 30 Saniyede Ayağa Kaldır (Docker)

```bash
# 1. Klonla
git clone https://github.com/acr-yazilim/dowaba-plugins.git
cd dowaba-plugins/examples/hastane-randevu

# 2. .env oluştur (Docker hazır default'larla çalışır)
cp .env.example .env

# 3. Ayaklan
docker compose up -d

# 4. Hazırlanması ~60sn sürer (composer install + migrate + seed), logları izle:
docker compose logs -f app

# 5. Demo hazır:
open http://localhost:8090
```

Demo zaten **3 doktor + 5 hasta + 10 randevu** ile seed edildi. Yeni randevu eklediğinde hasta WhatsApp template alır (Dowaba `DOWABA_*` env'leri dolu ise; aksi takdirde sadece log'a düşer).

---

## Lokal (Docker'sız)

PHP 8.2+ + MariaDB lokal kurulumun varsa:

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env'de DB ayarlarını lokal MariaDB'ye göre düzelt
php artisan migrate
php artisan db:seed
php artisan serve --port=8090
```

---

## Dowaba ile gerçek WhatsApp send

`.env`'de Dowaba env'lerini doldur:

```env
DOWABA_URL=https://dowaba.com
DOWABA_CLIENT_ID=dosc_xxx        # https://dowaba.com/admin/oauth/clients → Yeni Client
DOWABA_CLIENT_SECRET=dosec_xxx
DOWABA_REDIRECT_URI="${APP_URL}/dowaba/auth/callback"
DOWABA_SCOPES="openid profile email dowaba.whatsapp.send offline_access"
DOWABA_WIDGET_SITE_ID=42         # Dowaba admin → site detayı → site id
DOWABA_WIDGET_SECRET=...         # Dowaba admin → site detayı → widget secret
```

WhatsApp template'leri Dowaba admin panelinden tanımla:
- `appointment_confirmation` — params: `name, doctor, date`
- `appointment_reminder` — params: `name, doctor, date, specialty`

---

## Kritik Dosyalar

| Dosya | Ne yapıyor |
|---|---|
| `app/Models/{Doctor,Patient,Appointment}.php` | Eloquent modeller — BelongsTo + HasMany |
| `app/Http/Controllers/AppointmentController.php` | `store()` içinde `Dowaba::whatsapp()->template()` çağrısı |
| `app/Console/Commands/SendAppointmentReminders.php` | 24h öncesi randevulara hatırlatma — `--hours=24` opsiyonu |
| `routes/console.php` | `Schedule::command(SendAppointmentReminders::class)->dailyAt('09:00')` |
| `resources/views/layouts/app.blade.php` | `<x-dowaba::widget-script>` ile sayfaya widget gömme |
| `database/seeders/DemoSeeder.php` | İlk seed: 3 doktor / 5 hasta / 10 randevu |

---

## Test

```bash
php artisan test
```

(Feature test eklenmedi — TODO: `AppointmentNotificationTest` mock Dowaba ile)

---

## Lisans

[MIT](LICENSE) © Aydın Acar / Dowaba

İstediğin gibi kopyala, fork'la, kendi hastane yazılımına uyarla.

---

## İletişim

- 🌐 https://dowaba.com
- 📖 [API Docs](https://dowaba.com/api-docs) · [Örnek Projeler](https://dowaba.com/api-docs-ornekler)
- 🐛 [Issue açın](https://github.com/acr-yazilim/dowaba-plugins/issues)
