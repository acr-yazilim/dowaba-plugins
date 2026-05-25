<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

use Dowaba\LaravelBridge\DowabaClient;

class Sites
{
    public function __construct(protected DowabaClient $client) {}

    public function all(): array
    {
        return $this->client->get('/api/sites');
    }

    public function get(int|string $siteId): array
    {
        return $this->client->get("/api/sites/{$siteId}");
    }
}
