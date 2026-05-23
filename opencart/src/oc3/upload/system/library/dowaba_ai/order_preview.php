<?php
/**
 * Dowaba AI — Order Preview Cache (OC3, global).
 */
final class DowabaOrderPreview {

    const TTL_SECONDS = 300;
    const CACHE_PREFIX = 'dwb_preview_';

    public static function generateId() {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    public static function store($registry, $previewId, array $payload) {
        $cache = $registry->get('cache');
        $key = self::CACHE_PREFIX . $previewId;
        $payload['_preview_id'] = $previewId;
        $payload['_stored_at']  = time();
        $payload['_expires_at'] = time() + self::TTL_SECONDS;
        $cache->set($key, $payload, self::TTL_SECONDS);
    }

    public static function peek($registry, $previewId) {
        $cache = $registry->get('cache');
        $key = self::CACHE_PREFIX . $previewId;
        $value = $cache->get($key);

        // OC File cache miss → [] (boş array), null değil!
        if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) return null;

        if (isset($value['_expires_at']) && time() > (int)$value['_expires_at']) {
            $cache->delete($key);
            return null;
        }
        return $value;
    }

    public static function consume($registry, $previewId) {
        $payload = self::peek($registry, $previewId);
        if ($payload === null) return null;
        $registry->get('cache')->delete(self::CACHE_PREFIX . $previewId);
        return $payload;
    }

    public static function isValidId($id) {
        return (bool)preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }
}
