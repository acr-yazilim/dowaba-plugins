<?php
/**
 * Dowaba AI Integration for PrestaShop — Order Preview Cache
 *
 * 2-step order confirmation: preview generated → customer "yes" → confirm.
 * Backend: PrestaShop Cache (file / memcached / redis). TTL: 5 minutes.
 * One-shot consume — replay protection for order creation.
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DowabaOrderPreview
{
    public const CACHE_PREFIX = 'dwb_preview_';
    public const TTL_SECONDS = 300;

    public static function generateId(): string
    {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    public static function store(string $preview_id, array $payload): void
    {
        $payload['_preview_id'] = $preview_id;
        $payload['_stored_at'] = time();
        $payload['_expires_at'] = time() + self::TTL_SECONDS;
        Cache::store(self::CACHE_PREFIX . $preview_id, $payload);
    }

    public static function peek(string $preview_id): ?array
    {
        $key = self::CACHE_PREFIX . $preview_id;
        if (!Cache::isStored($key)) {
            return null;
        }

        $value = Cache::retrieve($key);
        if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) {
            return null;
        }

        if (isset($value['_expires_at']) && time() > (int) $value['_expires_at']) {
            Cache::clean($key);
            return null;
        }

        return $value;
    }

    public static function consume(string $preview_id): ?array
    {
        $payload = self::peek($preview_id);
        if ($payload === null) {
            return null;
        }
        Cache::clean(self::CACHE_PREFIX . $preview_id);
        return $payload;
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }
}
