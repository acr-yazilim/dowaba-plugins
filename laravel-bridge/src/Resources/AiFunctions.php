<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

use Dowaba\LaravelBridge\DowabaClient;

class AiFunctions
{
    public function __construct(protected DowabaClient $client) {}

    public function execute(int $siteId, string $functionName, array $params = []): array
    {
        return $this->client->post("/api/ai/functions/{$functionName}", array_merge([
            'site_id' => $siteId,
        ], $params));
    }

    public function list(int $siteId): array
    {
        return $this->client->get('/api/ai/functions', ['site_id' => $siteId]);
    }
}
