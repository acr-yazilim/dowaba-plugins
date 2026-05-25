<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Dowaba\LaravelBridge\Facades\Dowaba;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::query()
            ->with(['customer', 'items'])
            ->latest()
            ->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product']);

        return view('orders.show', compact('order'));
    }

    public function create(): View
    {
        return view('orders.create', [
            'customers' => Customer::orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('stock', '>', 0)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'shipping_address' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($data) {
            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'shipping_address' => $data['shipping_address'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0, // hesaplanıp güncellenecek
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Ürün stokta yok: {$product->name} ({$product->stock} adet kaldı)");
                }

                $subtotal = (float) $product->price * $item['quantity'];
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            $order->update(['total_amount' => $total]);

            return $order;
        });

        $order->load(['customer', 'items']);

        $this->sendStatusWhatsApp($order, 'order_placed', [
            'name' => $order->customer->name,
            'order_number' => $order->order_number,
            'item_count' => $order->items->count(),
            'total' => number_format((float) $order->total_amount, 2).' TL',
        ]);

        return redirect()->route('orders.show', $order)
            ->with('status', "Sipariş alındı: {$order->order_number}");
    }

    public function markPaid(Order $order): RedirectResponse
    {
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        $order->load(['customer']);

        $this->sendStatusWhatsApp($order, 'order_paid', [
            'name' => $order->customer->name,
            'order_number' => $order->order_number,
            'total' => number_format((float) $order->total_amount, 2).' TL',
        ]);

        return back()->with('status', "Sipariş ödendi: {$order->order_number}");
    }

    public function markShipped(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'tracking_code' => 'required|string|max:64',
        ]);

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_code' => $data['tracking_code'],
        ]);
        $order->load(['customer']);

        $this->sendStatusWhatsApp($order, 'order_shipped', [
            'name' => $order->customer->name,
            'order_number' => $order->order_number,
            'tracking_code' => $order->tracking_code,
        ]);

        return back()->with('status', "Kargoya verildi: {$order->order_number} ({$order->tracking_code})");
    }

    public function markDelivered(Order $order): RedirectResponse
    {
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $order->load(['customer']);

        $this->sendStatusWhatsApp($order, 'order_delivered', [
            'name' => $order->customer->name,
            'order_number' => $order->order_number,
        ]);

        return back()->with('status', "Teslim edildi: {$order->order_number}");
    }

    public function cancel(Order $order): RedirectResponse
    {
        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->withErrors(['status' => 'Kargoya verilmiş veya teslim edilmiş sipariş iptal edilemez.']);
        }

        DB::transaction(function () use ($order) {
            // Stok geri yükle
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        });

        $order->load(['customer']);

        $this->sendStatusWhatsApp($order, 'order_cancelled', [
            'name' => $order->customer->name,
            'order_number' => $order->order_number,
        ]);

        return back()->with('status', "Sipariş iptal edildi: {$order->order_number}");
    }

    private function sendStatusWhatsApp(Order $order, string $template, array $params): void
    {
        try {
            Dowaba::whatsapp()->template(
                phone: $order->customer->phone,
                template: $template,
                params: $params,
                siteId: (int) config('dowaba.widget.site_id'),
            );
        } catch (DowabaException $e) {
            Log::warning("Dowaba {$template} gönderilemedi", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'error_code' => $e->errorCode(),
            ]);
        }
    }
}
