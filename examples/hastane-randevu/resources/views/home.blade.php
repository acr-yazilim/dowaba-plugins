@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h1 style="margin: 0 0 20px; font-size: 26px;">Hastane Yönetim Paneli</h1>

    <div class="stat-grid" style="margin-bottom: 24px;">
        <div class="stat">
            <div class="stat-num">{{ $totalAppointments }}</div>
            <div class="stat-lbl">Toplam Randevu</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $upcomingAppointments }}</div>
            <div class="stat-lbl">Yaklaşan</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $totalPatients }}</div>
            <div class="stat-lbl">Hasta</div>
        </div>
        <div class="stat">
            <div class="stat-num">{{ $totalDoctors }}</div>
            <div class="stat-lbl">Doktor</div>
        </div>
    </div>

    <div class="card">
        <h2>Bugünün Randevuları</h2>
        @if($todayAppointments->isEmpty())
            <p style="color: #6b7280; margin: 0;">Bugün için randevu yok.</p>
        @else
            <table>
                <thead><tr><th>Saat</th><th>Hasta</th><th>Doktor</th><th>Durum</th></tr></thead>
                <tbody>
                @foreach($todayAppointments as $appt)
                    <tr>
                        <td><strong>{{ $appt->scheduled_at->format('H:i') }}</strong></td>
                        <td>{{ $appt->patient->name }} <span style="color: #9ca3af;">{{ $appt->patient->phone }}</span></td>
                        <td>{{ $appt->doctor->name }} <span style="color: #9ca3af;">{{ $appt->doctor->specialty }}</span></td>
                        <td><span class="badge badge-{{ $appt->status }}">{{ ucfirst($appt->status) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>Dowaba Bridge ile entegre</h2>
        <p style="color: #6b7280; line-height: 1.7;">
            Bu demo, <strong>Laravel 12 + dowaba/laravel-bridge</strong> paketi ile Dowaba SaaS'a bağlı.
            Yeni randevu eklendiğinde hasta otomatik <strong>WhatsApp template</strong> alır,
            1 gün öncesi <code>SendAppointmentReminders</code> komutu cron ile hatırlatma gönderir.
            Sağ alt köşedeki widget destek isteyen hastalar için.
        </p>
        <p style="color: #6b7280; line-height: 1.7;">
            Detay için: <a href="https://dowaba.com/api-docs-ornekler">dowaba.com/api-docs-ornekler</a>
        </p>
    </div>
@endsection
