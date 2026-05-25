# Otel Rezervasyon — Dowaba Bridge Demo

> Laravel 12 + `dowaba/laravel-bridge` ile **tamamen çalışan** otel rezervasyon sistemi. Yeni rezervasyon oluşturulunca misafir WhatsApp onay alır, 1 gün öncesi check-in hatırlatma + otel adresi/telefonu otomatik gider.

🌐 Daha fazla örnek: https://dowaba.com/api-docs-ornekler

---

> ## ⚠️ GÜVENLİK UYARISI — DEMO KOD
>
> Bu demo'da route'lara **authentication middleware EKLENMEMİŞTİR.** Herhangi biri `/acr/bookings` URL'ini çağırıp rezervasyon oluşturabilir, check-in/iptal yapabilir.
>
> **Production'a almadan ÖNCE:**
> 1. `routes/web.php`'deki `Route::prefix('acr')` group'una `->middleware('auth')` ekle
> 2. `checkin`/`cancel` action'larına policy (sadece resepsiyon/admin)
> 3. `.env`'de `APP_DEBUG=false`
> 4. Public form için rate limit (`throttle:10,1`)

---

## Özellikler

- 🏨 **Dashboard** — toplam oda / aktif rezervasyon / gelir / doluluk oranı + bugün check-in/out edenler
- 📋 **Rezervasyon listesi** — pagination, durum badge, hatırlatma izi, check-in/iptal butonları
- ➕ **Yeni rezervasyon** — oda müsaitlik kontrolü + otomatik **WhatsApp onay**
- 🔔 **Check-in hatırlatma cron** — `php artisan bookings:send-checkin-reminders` her gün 11:00, ertesi gün check-in yapacak misafirlere otel adresi+telefonu içeren WhatsApp template
- 💬 **Widget** — sağ alt köşede Dowaba destek butonu (ziyaretçi rezervasyon yapmadan önce soru sorabilsin)

---

## 30 Saniyede Ayağa Kaldır (Docker)

```bash
# 1. Klonla
git clone https://github.com/acr-yazilim/dowaba-plugins.git
cd dowaba-plugins/examples/otel-rezervasyon

# 2. .env oluştur (Docker hazır default'larla çalışır)
cp .env.example .env

# 3. Ayaklan
docker compose up -d

# 4. Hazırlanması ~60sn sürer (composer install + migrate + seed):
docker compose logs -f app

# 5. Demo hazır:
open http://localhost:8091
```

Demo otomatik seed: **1 otel + 8 oda (4 tip: single/double/suite/family) + 12 rezervasyon spread** (geçmiş check-out olmuş + bugün check-in/out + yarın + 1 ay sonrası).

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
php artisan serve --port=8091
```

---

## Dowaba ile gerçek WhatsApp send

`.env`'de Dowaba env'lerini doldur (`.env.example`'de detaylı yorumlar):

```env
DOWABA_URL=https://dowaba.com
DOWABA_CLIENT_ID=dosc_xxx
DOWABA_CLIENT_SECRET=dosec_xxx
DOWABA_REDIRECT_URI="${APP_URL}/dowaba/auth/callback"
DOWABA_SCOPES="openid profile email dowaba.whatsapp.send offline_access"
DOWABA_WIDGET_SITE_ID=42
DOWABA_WIDGET_SECRET=...
```

WhatsApp template'leri Dowaba admin panelinden tanımla:

| Template | Params |
|---|---|
| `hotel_booking_confirmation` | name, hotel, reservation_code, check_in, check_out, room_type, total |
| `hotel_checkin_reminder` | name, hotel, reservation_code, check_in, check_in_time, address, phone |

---

## Kritik Dosyalar

| Dosya | Ne yapıyor |
|---|---|
| `app/Models/{Hotel,Room,Booking}.php` | Eloquent modeller + `Room::isAvailable()` müsaitlik kontrolü |
| `app/Http/Controllers/BookingController.php` | `store()` → müsaitlik check + total calc + `Dowaba::whatsapp()->template()` |
| `app/Console/Commands/SendCheckInReminders.php` | 1 gün öncesi check-in için hatırlatma — `--days=N` opsiyonu |
| `routes/console.php` | `Schedule::command(SendCheckInReminders)->dailyAt('11:00')` |
| `resources/views/layouts/app.blade.php` | `<x-dowaba::widget-script>` ile widget gömme |
| `database/seeders/DemoSeeder.php` | İlk seed: 1 otel + 8 oda + 12 rezervasyon |

---

## Müsaitlik Algoritması

`Room::isAvailable(checkIn, checkOut)`:

```php
// 3 senaryo aynı oda için ÇAKIŞMA sayar:
// 1. Yeni check_in mevcut bir rezervasyonun aralığına düşer
// 2. Yeni check_out mevcut bir rezervasyonun aralığına düşer
// 3. Mevcut rezervasyon tamamen yeni aralığın içinde
return ! $room->bookings()
    ->whereIn('status', ['confirmed', 'checked_in'])
    ->where(function ($q) use ($checkIn, $checkOut) { ... })
    ->exists();
```

---

## Lisans

[MIT](LICENSE) © Aydın Acar / Dowaba

İstediğin gibi kopyala, fork'la, kendi otel/pansiyon yazılımına uyarla.

---

## İletişim

- 🌐 https://dowaba.com
- 📖 [API Docs](https://dowaba.com/api-docs) · [Örnek Projeler](https://dowaba.com/api-docs-ornekler)
- 🐛 [Issue açın](https://github.com/acr-yazilim/dowaba-plugins/issues)
