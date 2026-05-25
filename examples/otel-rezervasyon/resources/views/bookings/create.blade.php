@extends('layouts.app')
@section('title', 'Yeni Rezervasyon')

@section('content')
    <h1 style="margin: 0 0 20px; font-size: 24px;">Yeni Rezervasyon</h1>

    <div class="card" style="max-width: 720px;">
        <form method="POST" action="{{ route('bookings.store') }}">
            @csrf

            <div class="form-row">
                <label for="hotel_id">Otel</label>
                <select name="hotel_id" id="hotel_id" required>
                    @foreach($hotels as $h)
                        <option value="{{ $h->id }}">{{ $h->name }} — {{ $h->city }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <label for="room_id">Oda</label>
                <select name="room_id" id="room_id" required>
                    <option value="">— Oda seç —</option>
                    @foreach($rooms as $r)
                        <option value="{{ $r->id }}">{{ $r->room_number }} ({{ ucfirst($r->type) }}, {{ $r->capacity }} kişi) — {{ number_format((float) $r->price_per_night, 0, ',', '.') }} ₺/gece</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-row">
                    <label for="check_in">Giriş tarihi</label>
                    <input type="date" name="check_in" id="check_in" required min="{{ today()->toDateString() }}">
                </div>
                <div class="form-row">
                    <label for="check_out">Çıkış tarihi</label>
                    <input type="date" name="check_out" id="check_out" required>
                </div>
            </div>

            <div class="form-row">
                <label for="guests_count">Kişi sayısı</label>
                <input type="number" name="guests_count" id="guests_count" min="1" max="8" value="2" required>
            </div>

            <h3 style="margin: 20px 0 10px; font-size: 16px; color: #0c4a6e;">Misafir Bilgileri</h3>

            <div class="form-row">
                <label for="guest_name">Ad Soyad</label>
                <input type="text" name="guest_name" id="guest_name" required>
            </div>

            <div class="form-row">
                <label for="guest_phone">Telefon</label>
                <input type="tel" name="guest_phone" id="guest_phone" placeholder="+90555..." required>
            </div>

            <div class="form-row">
                <label for="guest_email">E-posta (opsiyonel)</label>
                <input type="email" name="guest_email" id="guest_email">
            </div>

            <div class="form-row">
                <label for="notes">Notlar (opsiyonel)</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Erken check-in, özel diyet, vb."></textarea>
            </div>

            @if($errors->any())
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 14px;">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn">Rezervasyonu Tamamla (+ WhatsApp Onay)</button>
                <a class="btn btn-ghost" href="{{ route('bookings.index') }}">İptal</a>
            </div>

            <p style="margin-top: 14px; color: #64748b; font-size: 13px;">
                ℹ️ Rezervasyon kaydedildiğinde misafire <strong>WhatsApp onay mesajı</strong> otomatik gider.
                Check-in tarihinden 1 gün önce <strong>hatırlatma</strong> mesajı planlanır.
            </p>
        </form>
    </div>
@endsection
