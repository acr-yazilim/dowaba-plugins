<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

class AiFunctions
{
    public function execute(int $siteId, string $functionName, array $params = []): array
    {
        return ['skeleton' => true, 'site_id' => $siteId, 'function' => $functionName, 'params' => $params];
    }

    public function list(int $siteId): array
    {
        return ['skeleton' => true, 'site_id' => $siteId];
    }
}
