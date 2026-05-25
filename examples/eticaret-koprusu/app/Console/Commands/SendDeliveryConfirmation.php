<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Console\Command;
use Throwable;

class SendDeliveryConfirmation extends Command
{
    protected $signature = 'orders:send-delivery-confirmation {--days=3 : Kaç gün shipped sonrası soru}';

    protected $description = 'Kargoya verilmiş ancak hala teslim edilmemiş siparişlere "ürün ulaştı mı?" mesajı';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);

        $orders = Order::query()
            ->with('customer')
            ->where('status', 'shipped')
            ->where('shipped_at', '<=', $threshold)
            ->get();

        $this->info("Teslim onayı sorulacak sipariş sayısı: {$orders->count()}");

        $sent = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                Dowaba::whatsapp()->template(
                    phone: $order->customer->phone,
                    template: 'order_delivery_check',
                    params: [
                        'name' => $order->customer->name,
                        'order_number' => $order->order_number,
                        'tracking_code' => $order->tracking_code ?? '',
                        'days_shipped' => $order->shipped_at->diffInDays(now()),
                    ],
                    siteId: (int) config('dowaba.widget.site_id'),
                );

                $sent++;
                $this->line("  ✓ {$order->customer->name} — {$order->order_number}");
            } catch (DowabaException $e) {
                $failed++;
                $this->error("  ✗ {$order->order_number}: {$e->getMessage()}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$order->order_number}: ".$e::class.' - '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Gönderilen: {$sent} / Başarısız: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
