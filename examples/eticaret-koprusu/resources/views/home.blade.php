@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h1 style="margin: 0 0 20px; font-size: 26px;">Mağaza Yönetim Paneli</h1>

    <div class="stat-grid" style="margin-bottom: 24px;">
        <div class="stat">
            <div class="stat-num">{{ $totalOrders }}</div>
            <div class="stat-lbl">Toplam Sipariş</div>
        </div>
        <div class="stat">
            <div class="stat-num" style="color: #ca8a04;">{{ $pendingOrders }}</div>
            <div class="stat-lbl">Bekleyen</div>
        </div>
        <div class="stat">
            <div class="stat-num" style="color: #5b21b6;">{{ $shippedOrders }}</div>
            <div class="stat-lbl">Kargoda</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ number_format((float) $totalRevenue, 0, ',', '.') }} ₺</div>
            <div class="stat-lbl">Gelir</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $totalCustomers }}</div>
            <div class="stat-lbl">Müşteri</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $totalProducts }}</div>
            <div class="stat-lbl">Aktif Ürün</div>
        </div>
    </div>

    @if($lowStock->isNotEmpty())
    <div class="card" style="border-color: #fed7aa; background: #fff7ed;">
        <h2 style="color: #c2410c;">⚠️ Stok Azalan Ürünler</h2>
        <table>
            <thead><tr><th>Ürün</th><th>SKU</th><th>Stok</th><th>Fiyat</th></tr></thead>
            <tbody>
            @foreach($lowStock as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->sku }}</td>
                    <td style="color: #dc2626; font-weight: 700;">{{ $p->stock }} adet</td>
                    <td>{{ number_format((float) $p->price, 2, ',', '.') }} ₺</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="card">
        <h2>Son Siparişler</h2>
        @if($recentOrders->isEmpty())
            <p style="color: #6b7280; margin: 0;">Henüz sipariş yok.</p>
        @else
            <table>
                <thead><tr><th>Sipariş No</th><th>Müşteri</th><th>Ürünler</th><th>Tutar</th><th>Durum</th><th>Tarih</th></tr></thead>
                <tbody>
                @foreach($recentOrders as $order)
                    <tr>
                        <td><a href="{{ route('orders.show', $order) }}" style="color: #7c2d12; font-weight: 700; text-decoration: none;">{{ $order->order_number }}</a></td>
                        <td>{{ $order->customer->name }}<br><span style="color: #9ca3af; font-size: 12px;">{{ $order->customer->phone }}</span></td>
                        <td style="color: #6b7280; font-size: 13px;">{{ $order->itemsSummary() }}</td>
                        <td>{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₺</td>
                        <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td style="color: #6b7280; font-size: 12px;">{{ $order->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>🤖 Dowaba Bridge — Sipariş Akışı Boyunca WhatsApp</h2>
        <p style="color: #4b5563; line-height: 1.7;">
            Bu demo, sipariş status transition'larında <strong>her aşamada farklı WhatsApp template</strong>
            gönderir. Yazılımcı bayisi olarak Dowaba'yı kendi e-ticaret yazılımına şu pattern'le entegre edersin:
        </p>
        <div class="status-flow" style="margin-top: 16px; flex-wrap: wrap;">
            <span class="step active">pending</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step">paid</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step">shipped</span>
            <span style="color: #9ca3af;">→</span>
            <span class="step">delivered</span>
        </div>
        <p style="color: #6b7280; font-size: 13px; margin-top: 14px;">
            Her geçişte: <code>Dowaba::whatsapp()->template('order_paid', [...])</code> →
            müşteriye anında bildirim. 3 gün shipped sonrası teslim onayı cron'u.
        </p>
        <p style="color: #6b7280;">
            Daha fazla örnek: <a href="https://dowaba.com/api-docs-ornekler">dowaba.com/api-docs-ornekler</a>
        </p>
    </div>
@endsection
