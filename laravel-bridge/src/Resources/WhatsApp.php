<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

/**
 * WhatsApp gönderim resource'u.
 *
 * Şu an iskelet — gerçek HTTP çağrıları sonraki seansta DowabaClient ile.
 */
class WhatsApp
{
    public function send(int|string $contactId, string $message, ?int $siteId = null): array
    {
        return ['skeleton' => true, 'contact_id' => $contactId, 'message' => $message, 'site_id' => $siteId];
    }

    public function template(string $phone, string $template, array $params = [], ?int $siteId = null): array
    {
        return ['skeleton' => true, 'phone' => $phone, 'template' => $template, 'params' => $params, 'site_id' => $siteId];
    }

    public function bulkSend(array $recipients, string $message, ?int $siteId = null): array
    {
        return ['skeleton' => true, 'count' => count($recipients), 'message' => $message, 'site_id' => $siteId];
    }
}
