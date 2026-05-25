@props([])
<div class="{{ $class }}" data-dowaba="conversation-list" data-site-id="{{ $siteId }}">
    @if($needsConnect)
        <div class="dowaba-empty" style="padding: 20px; border: 1px dashed #d1d5db; border-radius: 10px; color: #6b7280;">
            <p>Konuşmaları görmek için Dowaba'ya bağlan.</p>
            <x-dowaba::login-button :intended="url()->current()">Dowaba ile Giriş</x-dowaba::login-button>
        </div>
    @elseif($error)
        <div class="dowaba-error" style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px;">
            Dowaba konuşmaları alınamadı: {{ $error }}
        </div>
    @elseif(empty($conversations))
        <div class="dowaba-empty" style="padding: 20px; color: #6b7280;">
            Henüz konuşma yok.
        </div>
    @else
        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach($conversations as $c)
                <li style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6;" data-conversation-id="{{ $c['id'] ?? '' }}">
                    <strong>{{ $c['contact']['name'] ?? ($c['contact_name'] ?? 'Bilinmeyen') }}</strong>
                    <span style="color: #9ca3af; margin-left: 8px;">{{ $c['channel'] ?? '' }}</span>
                    <p style="margin: 4px 0 0; color: #4b5563; font-size: 14px;">
                        {{ \Illuminate\Support\Str::limit($c['last_message'] ?? '', 80) }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</div>
