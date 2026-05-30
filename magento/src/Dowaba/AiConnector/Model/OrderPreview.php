<?php
/**
 * Dowaba AI Connector — Order Preview cache.
 *
 * 2-step confirmation flow:
 *   1. AI → mgm_order_preview {items, customer} → total computed, cached, preview_id returned
 *   2. AI shows the summary to the customer → "Confirm?"
 *   3. Customer "Yes" → AI → mgm_order_confirm {preview_id} → cache consumed (one-shot), real order created
 *
 * TTL 300s. One-shot consume → replay protection (the same preview_id cannot create
 * two orders). preview_id has 96 bits of entropy.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class OrderPreview
{
    public const TTL_SECONDS = 300;
    public const CACHE_PREFIX = 'dwb_preview_';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    /** Format: prv_ + 24 hex chars (96 bits). */
    public function generateId(): string
    {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    public function store(string $previewId, array $payload): void
    {
        $payload['_preview_id'] = $previewId;
        $payload['_stored_at']  = time();
        $payload['_expires_at'] = time() + self::TTL_SECONDS;

        $this->cache->save(
            $this->serializer->serialize($payload),
            self::CACHE_PREFIX . $previewId,
            ['dowaba_ai_preview'],
            self::TTL_SECONDS
        );
    }

    /**
     * Read without deleting. Returns null on miss / expired / malformed.
     * (Defensive _preview_id check mirrors LESSONS_LEARNED bug #1 — never trust a
     * truthy-but-empty cache payload for replay protection.)
     */
    public function peek(string $previewId): ?array
    {
        $raw = $this->cache->load(self::CACHE_PREFIX . $previewId);
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }

        try {
            $value = $this->serializer->unserialize($raw);
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) {
            return null;
        }

        if (isset($value['_expires_at']) && time() > (int) $value['_expires_at']) {
            $this->cache->remove(self::CACHE_PREFIX . $previewId);
            return null;
        }

        return $value;
    }

    /** Read AND delete (one-shot consume). */
    public function consume(string $previewId): ?array
    {
        $payload = $this->peek($previewId);
        if ($payload === null) {
            return null;
        }
        $this->cache->remove(self::CACHE_PREFIX . $previewId);
        return $payload;
    }

    public function isValidId(string $id): bool
    {
        return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }
}
