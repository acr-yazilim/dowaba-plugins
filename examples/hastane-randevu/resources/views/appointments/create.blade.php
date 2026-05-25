@extends('layouts.app')
@section('title', 'Yeni Randevu')

@section('content')
    <h1 style="margin: 0 0 20px; font-size: 24px;">Yeni Randevu</h1>

    <div class="card" style="max-width: 600px;">
        <form method="POST" action="{{ route('appointments.store') }}">
            @csrf

            <div class="form-row">
                <label for="patient_id">Hasta</label>
                <select name="patient_id" id="patient_id" required>
                    <option value="">— Seçin —</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->phone }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <label for="doctor_id">Doktor</label>
                <select name="doctor_id" id="doctor_id" required>
                    <option value="">— Seçin —</option>
                    @foreach($doctors as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} — {{ $d->specialty }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <label for="scheduled_at">Tarih + Saat</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" required>
            </div>

            <div class="form-row">
                <label for="notes">Notlar (opsiyonel)</label>
                <textarea name="notes" id="notes" rows="3"></textarea>
            </div>

            @if($errors->any())
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 14px;">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn">Randevu Oluştur (+ WhatsApp Bildirim)</button>
                <a class="btn btn-ghost" href="{{ route('appointments.index') }}">İptal</a>
            </div>

            <p style="margin-top: 14px; color: #6b7280; font-size: 13px;">
                ℹ️ Kaydedildikten sonra hastaya <strong>WhatsApp onay mesajı</strong> otomatik gider.
                Randevu tarihinden 1 gün önce <strong>hatırlatma</strong> mesajı planlanır.
            </p>
        </form>
    </div>
@endsection
