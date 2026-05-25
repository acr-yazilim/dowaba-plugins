<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

use Dowaba\LaravelBridge\DowabaClient;

class Conversations
{
    public function __construct(protected DowabaClient $client) {}

    public function list(int $siteId, array $filters = []): array
    {
        return $this->client->get('/api/conversations', array_merge(['site_id' => $siteId], $filters));
    }

    public function get(int|string $conversationId): array
    {
        return $this->client->get("/api/conversations/{$conversationId}");
    }

    public function sendMessage(int|string $conversationId, string $message): array
    {
        return $this->client->post("/api/conversations/{$conversationId}/messages", [
            'message' => $message,
        ]);
    }

    public function close(int|string $conversationId): array
    {
        return $this->client->post("/api/conversations/{$conversationId}/close");
    }
}
