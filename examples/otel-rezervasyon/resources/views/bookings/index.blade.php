@extends('layouts.app')
@section('title', 'Rezervasyonlar')

@section('content')
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; flex: 1;">Rezervasyonlar</h1>
        <a class="btn" href="{{ route('bookings.create') }}">+ Yeni Rezervasyon</a>
    </div>

    <div class="card">
        @if($bookings->isEmpty())
            <p style="color: #64748b; text-align: center; padding: 30px;">Henüz rezervasyon yok.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Misafir</th>
                        <th>Oda</th>
                        <th>Check-in → out</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>WhatsApp</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($bookings as $b)
                    <tr>
                        <td><strong>{{ $b->reservation_code }}</strong></td>
                        <td>{{ $b->guest_name }}<br><span style="color: #94a3b8; font-size: 12px;">{{ $b->guest_phone }}</span></td>
                        <td>{{ $b->room->room_number }} <span class="room-type">{{ $b->room->type }}</span></td>
                        <td>{{ $b->check_in->format('d M') }} → {{ $b->check_out->format('d M Y') }}<br><span style="color: #94a3b8; font-size: 12px;">{{ $b->nights() }} gece</span></td>
                        <td>{{ number_format((float) $b->total_amount, 0, ',', '.') }} ₺</td>
                        <td><span class="badge badge-{{ $b->status }}">{{ ucfirst(str_replace('_', ' ', $b->status)) }}</span></td>
                        <td>
                            @if($b->reminded_at)
                                <span style="color: #16a34a;">✓ Hatırlatma</span><br>
                                <span style="color: #94a3b8; font-size: 11px;">{{ $b->reminded_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td style="display: flex; gap: 4px;">
                            @if($b->status === 'confirmed')
                                <form method="POST" action="{{ route('bookings.checkin', $b) }}" style="display:inline">@csrf
                                    <button type="submit" class="btn btn-success" style="padding: 4px 10px; font-size: 12px;">Check-in</button>
                                </form>
                                <form method="POST" action="{{ route('bookings.cancel', $b) }}" style="display:inline" onsubmit="return confirm('İptal etmek istediğinden emin misin?')">@csrf
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;">İptal</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div style="margin-top: 16px;">{{ $bookings->links() }}</div>
        @endif
    </div>
@endsection
