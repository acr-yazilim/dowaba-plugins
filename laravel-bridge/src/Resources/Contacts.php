<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

class Contacts
{
    public function list(int $siteId, array $filters = []): array
    {
        return ['skeleton' => true, 'site_id' => $siteId, 'filters' => $filters];
    }

    public function create(int $siteId, array $data): array
    {
        return ['skeleton' => true, 'site_id' => $siteId, 'data' => $data];
    }

    public function upsertByPhone(int $siteId, string $phone, array $attrs = []): array
    {
        return ['skeleton' => true, 'site_id' => $siteId, 'phone' => $phone, 'attrs' => $attrs];
    }

    public function get(int|string $contactId): array
    {
        return ['skeleton' => true, 'contact_id' => $contactId];
    }

    public function update(int|string $contactId, array $data): array
    {
        return ['skeleton' => true, 'contact_id' => $contactId, 'data' => $data];
    }

    public function delete(int|string $contactId): array
    {
        return ['skeleton' => true, 'deleted' => $contactId];
    }
}
