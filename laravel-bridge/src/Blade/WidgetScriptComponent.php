<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Blade;

use Dowaba\LaravelBridge\Support\HmacSigner;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * <x-dowaba::widget-script :site-id="$siteId" :user="auth()->user()" />
 *
 * Dowaba widget'ı kendi sitenize gömün. Login'li kullanıcı için HMAC-imzalı
 * `data-user-token` üretilir → Dowaba `/api/widget/auth` endpoint'i token'ı
 * doğrular, Contact upsert eder, 5dk TTL session token döner.
 *
 * Guest/null user durumunda token üretilmez, widget anonymous mode'da açılır.
 */
class WidgetScriptComponent extends Component
{
    public ?string $widgetToken = null;

    public function __construct(
        public ?int $siteId = null,
        public mixed $user = null,
        public ?string $widgetJsUrl = null,
    ) {
        $this->siteId ??= (int) (config('dowaba.widget.site_id') ?? 0);
        $this->widgetJsUrl ??= rtrim(config('dowaba.url'), '/').'/widget.js';

        if ($user !== null && config('dowaba.widget.secret')) {
            $payload = $this->buildPayload($user);

            if ($payload) {
                $signer = app(HmacSigner::class);
                $this->widgetToken = $signer->sign(
                    payload: $payload,
                    secret: (string) config('dowaba.widget.secret'),
                    ttl: (int) config('dowaba.widget.token_ttl', 300),
                );
            }
        }
    }

    public function siteApiKey(): ?string
    {
        return config('dowaba.widget.api_key')
            ?? (string) config('dowaba.widget.site_id'); // fallback
    }

    public function render(): View
    {
        return view('dowaba::components.widget-script');
    }

    private function buildPayload(mixed $user): ?array
    {
        $userId = is_object($user) ? ($user->id ?? $user->getKey() ?? null) : ($user['id'] ?? null);

        if ($userId === null) {
            return null;
        }

        $email = is_object($user) ? ($user->email ?? null) : ($user['email'] ?? null);
        $name = is_object($user) ? ($user->name ?? null) : ($user['name'] ?? null);
        $phone = is_object($user) ? ($user->phone ?? null) : ($user['phone'] ?? null);

        return array_filter([
            'user_id' => (string) $userId,
            'email' => $email,
            'site_id' => $this->siteId,
            'name' => $name,
            'phone' => $phone,
        ], static fn ($v) => $v !== null);
    }
}
