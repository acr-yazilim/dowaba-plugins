<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 10 ürün
        $productDefs = [
            ['name' => 'Bluetooth Kulaklık', 'sku' => 'BT-HDP-001', 'price' => 899.00, 'stock' => 25],
            ['name' => 'USB-C Hızlı Şarj Kablosu', 'sku' => 'USB-C-201', 'price' => 159.00, 'stock' => 60],
            ['name' => 'Termos 500ml', 'sku' => 'TRM-500', 'price' => 249.00, 'stock' => 18],
            ['name' => 'Klavye (Mekanik)', 'sku' => 'KBD-MX-RED', 'price' => 2499.00, 'stock' => 8],
            ['name' => 'Mouse (Kablosuz)', 'sku' => 'MOU-WL-301', 'price' => 449.00, 'stock' => 30],
            ['name' => 'Powerbank 20000mAh', 'sku' => 'PWR-20K', 'price' => 599.00, 'stock' => 4],
            ['name' => 'Laptop Çantası 15"', 'sku' => 'BAG-LP-15', 'price' => 799.00, 'stock' => 12],
            ['name' => 'Webcam HD', 'sku' => 'CAM-HD-1080', 'price' => 1299.00, 'stock' => 6],
            ['name' => 'HDMI Kablo 2m', 'sku' => 'HDMI-2M', 'price' => 89.00, 'stock' => 100],
            ['name' => 'Yazıcı Kartuşu', 'sku' => 'PRT-CRT-BLK', 'price' => 349.00, 'stock' => 0], // stoksuz
        ];

        $products = collect($productDefs)->map(fn ($p) => Product::firstOrCreate(
            ['sku' => $p['sku']],
            array_merge($p, [
                'slug' => Str::slug($p['name']),
                'description' => 'Demo ürün — '.$p['name'],
                'is_active' => true,
            ])
        ));

        // 5 müşteri
        $customerDefs = [
            ['name' => 'Ali Kaya', 'phone' => '+905551110301', 'email' => 'ali@example.com', 'address' => 'İstanbul, Kadıköy, Bahariye Cd. No:42 D:8'],
            ['name' => 'Zeynep Demir', 'phone' => '+905551110302', 'email' => 'zeynep@example.com', 'address' => 'Ankara, Çankaya, Tunalı Hilmi Cd. No:15'],
            ['name' => 'Mehmet Yıldız', 'phone' => '+905551110303', 'email' => 'mehmet@example.com', 'address' => 'İzmir, Karşıyaka, Cemal Gürsel Cd. No:78'],
            ['name' => 'Elif Şahin', 'phone' => '+905551110304', 'email' => 'elif@example.com', 'address' => 'Bursa, Nilüfer, Mudanya Yolu No:33'],
            ['name' => 'Burak Polat', 'phone' => '+905551110305', 'email' => 'burak@example.com', 'address' => 'Antalya, Muratpaşa, Atatürk Cd. No:5'],
        ];

        $customers = collect($customerDefs)->map(fn ($c) => Customer::firstOrCreate(
            ['phone' => $c['phone']],
            $c
        ));

        // 8 sipariş — farklı status'larda
        $orderStatuses = [
            ['status' => 'pending', 'days_ago' => 0],
            ['status' => 'paid', 'days_ago' => 1],
            ['status' => 'shipped', 'days_ago' => 2],
            ['status' => 'shipped', 'days_ago' => 5], // 5 gün öncesi shipped → delivery confirmation cron tetiklenecek
            ['status' => 'delivered', 'days_ago' => 7],
            ['status' => 'delivered', 'days_ago' => 14],
            ['status' => 'cancelled', 'days_ago' => 3],
            ['status' => 'pending', 'days_ago' => 0],
        ];

        foreach ($orderStatuses as $i => $cfg) {
            $customer = $customers[$i % count($customers)];
            $createdAt = now()->subDays($cfg['days_ago']);

            $order = Order::firstOrCreate(
                ['order_number' => sprintf('ORD-DEMO-%03d', $i + 1)],
                [
                    'customer_id' => $customer->id,
                    'status' => $cfg['status'],
                    'shipping_address' => $customer->address,
                    'total_amount' => 0,
                    'paid_at' => in_array($cfg['status'], ['paid', 'shipped', 'delivered']) ? $createdAt : null,
                    'shipped_at' => in_array($cfg['status'], ['shipped', 'delivered']) ? $createdAt->copy()->addDay() : null,
                    'delivered_at' => $cfg['status'] === 'delivered' ? $createdAt->copy()->addDays(3) : null,
                    'cancelled_at' => $cfg['status'] === 'cancelled' ? $createdAt : null,
                    'tracking_code' => in_array($cfg['status'], ['shipped', 'delivered']) ? 'TR'.rand(100000000, 999999999) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            // 2-4 item ekle
            $orderProducts = $products->where('stock', '>=', 0)->random(rand(2, 4));
            $total = 0;
            foreach ($orderProducts as $product) {
                $qty = rand(1, 3);
                $subtotal = (float) $product->price * $qty;
                $total += $subtotal;

                OrderItem::firstOrCreate(
                    ['order_id' => $order->id, 'product_id' => $product->id],
                    [
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ]
                );
            }

            $order->update(['total_amount' => $total]);
        }

        $this->command->info('Demo seed: '.$products->count().' ürün + '.$customers->count().' müşteri + '.Order::count().' sipariş.');
    }
}
