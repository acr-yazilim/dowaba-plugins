<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

use Dowaba\LaravelBridge\DowabaClient;

class Contacts
{
    public function __construct(protected DowabaClient $client) {}

    public function list(int $siteId, array $filters = []): array
    {
        return $this->client->get('/api/contacts', array_merge(['site_id' => $siteId], $filters));
    }

    public function create(int $siteId, array $data): array
    {
        return $this->client->post('/api/contacts', array_merge(['site_id' => $siteId], $data));
    }

    public function upsertByPhone(int $siteId, string $phone, array $attrs = []): array
    {
        return $this->client->post('/api/contacts/upsert', array_merge([
            'site_id' => $siteId,
            'phone' => $phone,
        ], $attrs));
    }

    public function get(int|string $contactId): array
    {
        return $this->client->get("/api/contacts/{$contactId}");
    }

    public function update(int|string $contactId, array $data): array
    {
        return $this->client->put("/api/contacts/{$contactId}", $data);
    }

    public function delete(int|string $contactId): array
    {
        return $this->client->delete("/api/contacts/{$contactId}");
    }
}
