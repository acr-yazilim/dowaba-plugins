@extends('layouts.app')
@section('title', 'Sipariş '.$order->order_number)

@section('content')
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <div style="flex: 1;">
            <h1 style="margin: 0 0 4px; font-size: 24px;">{{ $order->order_number }}</h1>
            <span class="badge badge-{{ $order->status }}">{{ strtoupper($order->status) }}</span>
        </div>
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">← Siparişler</a>
    </div>

    <div class="card">
        <h2>Sipariş Akışı</h2>
        <div class="status-flow" style="flex-wrap: wrap;">
            @php($flow = ['pending' => 1, 'paid' => 2, 'shipped' => 3, 'delivered' => 4])
            @php($current = $flow[$order->status] ?? 0)
            <span class="step {{ $current >= 1 ? 'completed' : '' }} {{ $order->status === 'pending' ? 'active' : '' }}">1. Sipariş Alındı</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step {{ $current >= 2 ? 'completed' : '' }} {{ $order->status === 'paid' ? 'active' : '' }}">2. Ödeme Alındı</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step {{ $current >= 3 ? 'completed' : '' }} {{ $order->status === 'shipped' ? 'active' : '' }}">3. Kargoya Verildi</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step {{ $current >= 4 ? 'completed' : '' }} {{ $order->status === 'delivered' ? 'active' : '' }}">4. Teslim Edildi</span>
            @if($order->status === 'cancelled')
                <span class="step" style="background: #fee2e2; color: #991b1b;">İPTAL</span>
            @endif
        </div>

        @if($order->status !== 'cancelled')
        <div style="margin-top: 18px; display: flex; gap: 8px; flex-wrap: wrap;">
            @if($order->status === 'pending')
                <form method="POST" action="{{ route('orders.mark-paid', $order) }}">@csrf<button class="btn btn-success" type="submit">Ödeme Alındı</button></form>
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('İptal etmek istediğinden emin misin?')">@csrf<button class="btn btn-danger" type="submit">İptal</button></form>
            @elseif($order->status === 'paid')
                <form method="POST" action="{{ route('orders.mark-shipped', $order) }}" style="display: flex; gap: 8px;">
                    @csrf
                    <input type="text" name="tracking_code" placeholder="Kargo takip kodu" required style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                    <button class="btn btn-warning" type="submit">Kargoya Ver</button>
                </form>
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('İptal etmek istediğinden emin misin?')">@csrf<button class="btn btn-danger" type="submit">İptal</button></form>
            @elseif($order->status === 'shipped')
                <form method="POST" action="{{ route('orders.mark-delivered', $order) }}">@csrf<button class="btn btn-success" type="submit">Teslim Edildi</button></form>
            @endif
        </div>
        @endif

        @if($errors->any())
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-top: 14px;">
                @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <h2>Müşteri Bilgileri</h2>
        <p style="margin: 0; line-height: 1.7;">
            <strong>{{ $order->customer->name }}</strong><br>
            📞 {{ $order->customer->phone }}
            @if($order->customer->email)<br>📧 {{ $order->customer->email }}@endif
        </p>
        <p style="margin: 12px 0 0; color: #4b5563; line-height: 1.5;">
            <strong>Adres:</strong> {{ $order->shipping_address }}
        </p>
        @if($order->tracking_code)
            <p style="margin: 12px 0 0;"><strong>Kargo Takip:</strong> <code>{{ $order->tracking_code }}</code></p>
        @endif
    </div>

    <div class="card">
        <h2>Sipariş Kalemleri</h2>
        <table>
            <thead><tr><th>Ürün</th><th>SKU</th><th>Adet</th><th>Birim</th><th>Toplam</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td style="color: #9ca3af;">{{ $item->product_sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2, ',', '.') }} ₺</td>
                    <td><strong>{{ number_format((float) $item->subtotal, 2, ',', '.') }} ₺</strong></td>
                </tr>
            @endforeach
            <tr style="background: #fef2f2;">
                <td colspan="4" style="text-align: right;"><strong>GENEL TOPLAM</strong></td>
                <td><strong>{{ number_format((float) $order->total_amount, 2, ',', '.') }} ₺</strong></td>
            </tr>
            </tbody>
        </table>
    </div>

    @if($order->notes)
    <div class="card">
        <h2>Notlar</h2>
        <p style="margin: 0;">{{ $order->notes }}</p>
    </div>
    @endif
@endsection
