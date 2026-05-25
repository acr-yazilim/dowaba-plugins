@extends('layouts.app')
@section('title', 'Siparişler')

@section('content')
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; flex: 1;">Siparişler</h1>
        <a class="btn" href="{{ route('orders.create') }}">+ Yeni Sipariş</a>
    </div>

    <div class="card">
        @if($orders->isEmpty())
            <p style="color: #6b7280; text-align: center; padding: 30px;">Henüz sipariş yok.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Sipariş No</th>
                        <th>Müşteri</th>
                        <th>Ürünler</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>{{ $order->customer->name }}<br><span style="color: #9ca3af; font-size: 12px;">{{ $order->customer->phone }}</span></td>
                        <td style="color: #6b7280; font-size: 13px;">{{ $order->itemsSummary() }}</td>
                        <td><strong>{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₺</strong></td>
                        <td><span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td style="color: #6b7280; font-size: 12px;">{{ $order->created_at->format('d M H:i') }}</td>
                        <td><a class="btn btn-info" href="{{ route('orders.show', $order) }}" style="padding: 4px 10px; font-size: 12px;">Detay</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div style="margin-top: 16px;">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
