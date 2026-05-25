@extends('layouts.app')
@section('title', 'Randevular')

@section('content')
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; flex: 1;">Randevular</h1>
        <a class="btn" href="{{ route('appointments.create') }}">+ Yeni Randevu</a>
    </div>

    <div class="card">
        @if($appointments->isEmpty())
            <p style="color: #6b7280; text-align: center; padding: 30px;">Henüz randevu yok.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Hasta</th>
                        <th>Doktor</th>
                        <th>Durum</th>
                        <th>WhatsApp</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($appointments as $appt)
                    <tr>
                        <td><strong>{{ $appt->scheduled_at->format('d M Y H:i') }}</strong></td>
                        <td>{{ $appt->patient->name }}<br><span style="color: #9ca3af; font-size: 12px;">{{ $appt->patient->phone }}</span></td>
                        <td>{{ $appt->doctor->name }}<br><span style="color: #9ca3af; font-size: 12px;">{{ $appt->doctor->specialty }}</span></td>
                        <td><span class="badge badge-{{ $appt->status }}">{{ ucfirst($appt->status) }}</span></td>
                        <td>
                            @if($appt->reminded_at)
                                <span style="color: #16a34a;">✓ Hatırlatma gönderildi</span><br>
                                <span style="color: #9ca3af; font-size: 11px;">{{ $appt->reminded_at->diffForHumans() }}</span>
                            @elseif($appt->confirmed_at)
                                <span style="color: #6b7280;">Onay mesajı gönderildi</span>
                            @endif
                        </td>
                        <td>
                            @if($appt->status === 'confirmed')
                                <form method="POST" action="{{ route('appointments.cancel', $appt) }}" style="display:inline" onsubmit="return confirm('İptal etmek istediğinden emin misin?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 12px;">İptal</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div style="margin-top: 16px;">{{ $appointments->links() }}</div>
        @endif
    </div>
@endsection
