@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h1 style="margin: 0 0 8px; font-size: 26px;">{{ $hotel?->name ?? 'Otel Yönetim Paneli' }}</h1>
    <p style="color: #64748b; margin: 0 0 24px;">
        @if($hotel)
            {{ $hotel->city }} · ⭐ {{ $hotel->star_rating }} yıldız · Check-in {{ $hotel->check_in_time }} / Check-out {{ $hotel->check_out_time }}
        @endif
    </p>

    <div class="stat-grid" style="margin-bottom: 24px;">
        <div class="stat">
            <div class="stat-num">{{ $totalRooms }}</div>
            <div class="stat-lbl">Toplam Oda</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $activeBookings }}</div>
            <div class="stat-lbl">Aktif Rezervasyon</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ number_format((float) $totalRevenue, 0, ',', '.') }} ₺</div>
            <div class="stat-lbl">Toplam Gelir</div>
        </div>
        <div class="stat">
            <div class="stat-num">%{{ $occupancy }}</div>
            <div class="stat-lbl">Doluluk</div>
        </div>
    </div>

    <div class="card">
        <h2>Doluluk Oranı</h2>
        <div class="occupancy-bar">
            <div class="occupancy-fill" style="width: {{ $occupancy }}%;"></div>
            <div class="occupancy-label">%{{ $occupancy }} dolu</div>
        </div>
    </div>

    <div class="card">
        <h2>Bugün Check-In Yapacaklar</h2>
        @if($todayCheckIns->isEmpty())
            <p style="color: #64748b; margin: 0;">Bugün için bekleyen check-in yok.</p>
        @else
            <table>
                <thead><tr><th>Rezervasyon</th><th>Misafir</th><th>Oda</th><th>Kişi</th><th>Çıkış</th></tr></thead>
                <tbody>
                @foreach($todayCheckIns as $b)
                    <tr>
                        <td><strong>{{ $b->reservation_code }}</strong></td>
                        <td>{{ $b->guest_name }}<br><span style="color: #94a3b8; font-size: 12px;">{{ $b->guest_phone }}</span></td>
                        <td>{{ $b->room->room_number }} <span class="room-type">{{ $b->room->type }}</span></td>
                        <td>{{ $b->guests_count }}</td>
                        <td>{{ $b->check_out->format('d M') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>Bugün Check-Out Yapacaklar</h2>
        @if($todayCheckOuts->isEmpty())
            <p style="color: #64748b; margin: 0;">Bugün için bekleyen check-out yok.</p>
        @else
            <table>
                <thead><tr><th>Rezervasyon</th><th>Misafir</th><th>Oda</th></tr></thead>
                <tbody>
                @foreach($todayCheckOuts as $b)
                    <tr>
                        <td><strong>{{ $b->reservation_code }}</strong></td>
                        <td>{{ $b->guest_name }}<br><span style="color: #94a3b8; font-size: 12px;">{{ $b->guest_phone }}</span></td>
                        <td>{{ $b->room->room_number }} <span class="room-type">{{ $b->room->type }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>🤖 Dowaba Bridge Entegrasyonu</h2>
        <p style="color: #475569; line-height: 1.7;">
            Bu demo, <strong>Laravel 12 + dowaba/laravel-bridge</strong> paketi ile Dowaba SaaS'a bağlı.
            Yeni rezervasyon oluşturulduğunda misafire <strong>WhatsApp onay mesajı</strong> otomatik gider.
            Check-in tarihinden 1 gün öncesi <code>SendCheckInReminders</code> komutu cron ile
            <strong>hatırlatma + otel adresi + telefon</strong> içeren mesaj yollar. Sağ alt köşedeki widget
            misafir veya potansiyel müşteri için destek hattı.
        </p>
        <p style="color: #475569; line-height: 1.7;">
            Daha fazla örnek: <a href="https://dowaba.com/api-docs-ornekler">dowaba.com/api-docs-ornekler</a>
        </p>
    </div>
@endsection
