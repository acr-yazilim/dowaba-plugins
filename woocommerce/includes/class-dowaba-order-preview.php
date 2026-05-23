<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

/**
 * Order preview cache — WP Transients API (5 dakika TTL).
 * Transient key: dwb_preview_<id>
 */
final class OrderPreview {

    public const CACHE_PREFIX = 'dwb_preview_';

    public static function generate_id(): string {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    public static function store(string $preview_id, array $payload): void {
        $payload['_preview_id'] = $preview_id;
        $payload['_stored_at']  = time();
        $payload['_expires_at'] = time() + DOWABA_AI_PREVIEW_TTL;
        set_transient(self::CACHE_PREFIX . $preview_id, $payload, DOWABA_AI_PREVIEW_TTL);
    }

    public static function peek(string $preview_id): ?array {
        $value = get_transient(self::CACHE_PREFIX . $preview_id);
        if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) {
            return null;
        }
        // Manuel expire check (Transients API default doğru zaten ama defansif)
        if (isset($value['_expires_at']) && time() > (int) $value['_expires_at']) {
            delete_transient(self::CACHE_PREFIX . $preview_id);
            return null;
        }
        return $value;
    }

    public static function consume(string $preview_id): ?array {
        $payload = self::peek($preview_id);
        if ($payload === null) return null;
        delete_transient(self::CACHE_PREFIX . $preview_id);
        return $payload;
    }

    public static function is_valid_id(string $id): bool {
        return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }
}
