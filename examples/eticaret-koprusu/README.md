# E-Ticaret Köprü — Dowaba Bridge Demo

> Laravel 12 + `dowaba/laravel-bridge` ile **status transition pattern**. Sipariş alındığında, ödeme alındığında, kargoya verildiğinde, teslim edildiğinde **her aşamada farklı WhatsApp template** otomatik gider.

🌐 Daha fazla örnek: https://dowaba.com/api-docs-ornekler

---

## Sipariş Akışı

```
pending  →  paid  →  shipped  →  delivered
   ↓         ↓         ↓            ↓
order_   order_   order_       order_
placed   paid     shipped      delivered
```

İptal her aşamada (shipped/delivered hariç) → `order_cancelled` template.
3 gün shipped sonrası hala delivered olmadıysa → `order_delivery_check` (cron).

---

## Özellikler

- 🛒 **Dashboard** — toplam sipariş / bekleyen / kargoda / gelir / müşteri / aktif ürün
- ⚠️ **Stok uyarısı** — 5 adetten az kalmış ürünler dashboard'da
- 📋 **Sipariş listesi** — pagination, durum badge, hızlı detay erişim
- ➕ **Yeni sipariş** — müşteri + adres + çoklu ürün (DB transaction + stok lock)
- 🔄 **Status transition** — her geçişte ilgili WhatsApp template otomatik
- 🔔 **Teslim kontrolü cron** — 3 gün shipped sonrası `order_delivery_check`
- 💬 **Widget** — sağ alt köşe Dowaba destek

---

## 30 Saniyede Ayağa Kaldır (Docker)

```bash
git clone https://github.com/acr-yazilim/dowaba-plugins.git
cd dowaba-plugins/examples/eticaret-koprusu
cp .env.example .env
docker compose up -d
docker compose logs -f app
open http://localhost:8092
```

Demo otomatik seed: **10 ürün + 5 müşteri + 8 sipariş** (farklı status'larda, biri 5 gün önce shipped → cron tetiklenebilir).

---

## Dowaba Template Listesi

| Template | Tetik | Params |
|---|---|---|
| `order_placed` | Yeni sipariş kaydedildi | name, order_number, item_count, total |
| `order_paid` | Ödeme alındı | name, order_number, total |
| `order_shipped` | Kargoya verildi | name, order_number, tracking_code |
| `order_delivered` | Teslim edildi | name, order_number |
| `order_cancelled` | İptal edildi | name, order_number |
| `order_delivery_check` | 3 gün shipped (cron) | name, order_number, tracking_code, days_shipped |

WhatsApp template'leri Dowaba admin panelinde **önceden tanımlanmalı** ve Meta Business Manager'da approve olmalı.

---

## Kritik Dosyalar

| Dosya | Ne yapıyor |
|---|---|
| `app/Models/{Customer,Product,Order,OrderItem}.php` | Eloquent modeller — OrderItem'da `product_name` + `product_sku` snapshot (ürün adı sonradan değişirse korunur) |
| `app/Http/Controllers/OrderController.php` | 7 endpoint: index/show/create/store/markPaid/markShipped/markDelivered/cancel — `sendStatusWhatsApp()` helper |
| `app/Console/Commands/SendDeliveryConfirmation.php` | 3 gün shipped sonrası "ürün ulaştı mı?" mesajı, `--days=N` opsiyonu |
| `routes/console.php` | `Schedule::command(SendDeliveryConfirmation)->dailyAt('14:00')` |
| `database/seeders/DemoSeeder.php` | 10 ürün + 5 müşteri + 8 sipariş spread (1 tane "stok=0" + 1 tane "5 gün shipped") |

---

## Status Transition Pattern

```php
public function markShipped(Request $request, Order $order): RedirectResponse
{
    $data = $request->validate(['tracking_code' => 'required|string|max:64']);
    $order->update(['status' => 'shipped', 'shipped_at' => now(), 'tracking_code' => $data['tracking_code']]);
    $this->sendStatusWhatsApp($order, 'order_shipped', [
        'name' => $order->customer->name,
        'order_number' => $order->order_number,
        'tracking_code' => $order->tracking_code,
    ]);
    return back();
}

private function sendStatusWhatsApp(Order $order, string $template, array $params): void
{
    try {
        Dowaba::whatsapp()->template($order->customer->phone, $template, $params, ...);
    } catch (DowabaException $e) {
        Log::warning("Dowaba {$template} gönderilemedi", [...]);
    }
}
```

**Bridge YOK durumu:** try/catch + Log::warning, sipariş DB'ye yine kayıt olur, sadece WhatsApp atlanır.

---

## Stok ve İptal Mantığı

```php
DB::transaction(function () use ($data) {
    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
    if ($product->stock < $item['quantity']) abort(422, '...');
    $product->decrement('stock', $item['quantity']);
});
```

İptal: pending/paid ise mümkün, stok geri yüklenir. Shipped/delivered iptal edilemez.

---

## Lisans

[MIT](LICENSE) © Aydın Acar / Dowaba

İstediğin gibi kopyala, fork'la, kendi e-ticaret yazılımına uyarla.

---

## İletişim

- 🌐 https://dowaba.com
- 📖 [API Docs](https://dowaba.com/api-docs) · [Örnek Projeler](https://dowaba.com/api-docs-ornekler)
- 🐛 [Issue açın](https://github.com/acr-yazilim/dowaba-plugins/issues)
