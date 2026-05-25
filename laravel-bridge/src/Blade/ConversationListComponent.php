<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Blade;

use Dowaba\LaravelBridge\DowabaManager;
use Dowaba\LaravelBridge\Support\DowabaException;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Throwable;

/**
 * <x-dowaba::conversation-list :site-id="$siteId" :limit="10" channel="wa" />
 *
 * Server-side render: GET /api/conversations?site_id=...
 *
 * Token yoksa veya 401 dönerse "Henüz Dowaba'ya bağlanmadın" mesajı basar.
 */
class ConversationListComponent extends Component
{
    public array $conversations = [];
    public ?string $error = null;
    public bool $needsConnect = false;

    public function __construct(
        public int $siteId,
        public int $limit = 20,
        public ?string $channel = null,
        public ?string $status = null,
        public string $class = 'dowaba-conversation-list',
    ) {
        try {
            $filters = array_filter([
                'limit' => $this->limit,
                'channel' => $this->channel,
                'status' => $this->status,
            ], static fn ($v) => $v !== null);

            $response = app(DowabaManager::class)->conversations()->list($siteId, $filters);

            $this->conversations = $response['data'] ?? $response['conversations'] ?? [];
        } catch (DowabaException $e) {
            if ($e->errorCode() === 'no_token') {
                $this->needsConnect = true;
            } else {
                $this->error = $e->getMessage();
            }
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('dowaba::components.conversation-list');
    }
}
