<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

class Sites
{
    public function all(): array
    {
        return ['skeleton' => true, 'sites' => []];
    }

    public function get(int|string $siteId): array
    {
        return ['skeleton' => true, 'site_id' => $siteId];
    }
}
