<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

use Dowaba\LaravelBridge\DowabaClient;

class WhatsApp
{
    public function __construct(protected DowabaClient $client) {}

    public function send(int|string $contactId, string $message, ?int $siteId = null): array
    {
        return $this->client->post('/api/wa/send', array_filter([
            'contact_id' => $contactId,
            'message' => $message,
            'site_id' => $siteId,
        ], static fn ($v) => $v !== null));
    }

    public function template(string $phone, string $template, array $params = [], ?int $siteId = null): array
    {
        return $this->client->post('/api/wa/template', array_filter([
            'phone' => $phone,
            'template' => $template,
            'params' => $params,
            'site_id' => $siteId,
        ], static fn ($v) => $v !== null && $v !== []));
    }

    public function bulkSend(array $recipients, string $message, ?int $siteId = null): array
    {
        return $this->client->post('/api/wa/bulk', array_filter([
            'recipients' => $recipients,
            'message' => $message,
            'site_id' => $siteId,
        ], static fn ($v) => $v !== null));
    }
}
