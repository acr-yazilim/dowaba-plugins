<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

class Conversations
{
    public function list(int $siteId, array $filters = []): array
    {
        return ['skeleton' => true, 'site_id' => $siteId, 'filters' => $filters];
    }

    public function get(int|string $conversationId): array
    {
        return ['skeleton' => true, 'conversation_id' => $conversationId];
    }

    public function close(int|string $conversationId): array
    {
        return ['skeleton' => true, 'closed' => $conversationId];
    }

    public function sendMessage(int|string $conversationId, string $message): array
    {
        return ['skeleton' => true, 'conversation_id' => $conversationId, 'message' => $message];
    }
}
