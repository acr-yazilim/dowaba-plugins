<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Blade;

use Dowaba\LaravelBridge\Support\HmacSigner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * <x-dowaba::chat-window :conversation-id="$id" height="600" />
 *
 * Dowaba'nın embed conversation view'unu iframe ile sayfaya gömer.
 * URL: {dowaba_url}/embed/conversation/{id}?token={hmac}
 *
 * Token HMAC-imzalı: yazılımcının kendi user'ını Dowaba widget session olarak
 * bağlar (data-user-token pattern paralel). Süre `widget.token_ttl` (5dk default).
 */
class ChatWindowComponent extends Component
{
    public ?string $embedToken = null;
    public string $embedUrl;

    public function __construct(
        public int|string $conversationId,
        public int $height = 500,
        public ?int $siteId = null,
        public string $class = 'dowaba-chat-window',
    ) {
        $this->siteId ??= (int) (config('dowaba.widget.site_id') ?? 0);

        if (Auth::check() && config('dowaba.widget.secret')) {
            $signer = app(HmacSigner::class);
            $this->embedToken = $signer->sign(
                payload: [
                    'user_id' => (string) Auth::id(),
                    'email' => Auth::user()->email ?? null,
                    'site_id' => $this->siteId,
                    'conversation_id' => $this->conversationId,
                ],
                secret: (string) config('dowaba.widget.secret'),
                ttl: (int) config('dowaba.widget.token_ttl', 300),
            );
        }

        $params = $this->embedToken ? '?token='.urlencode($this->embedToken) : '';
        $this->embedUrl = rtrim(config('dowaba.url'), '/')
            ."/embed/conversation/{$this->conversationId}{$params}";
    }

    public function render(): View
    {
        return view('dowaba::components.chat-window');
    }
}
