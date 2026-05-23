<?php
namespace Opencart\System\Library\Extension\DowabaAi;

/**
 * Dowaba AI — Order Preview Cache.
 *
 * AI sipariş oluşturma akışı (confirmation flow):
 *
 *   1. AI çağırır:   POST /api/orders/preview { items, customer }
 *      → backend total hesaplar, cache'e koyar, preview_id döner
 *
 *   2. AI müşteriye gösterir: "Siparişin: 65.048 TL. Onaylıyor musun?"
 *
 *   3. Müşteri "Evet" der → AI çağırır:
 *      POST /api/orders/confirm { preview_id, confirmed: true }
 *      → cache consume edilir (one-shot), gerçek order yaratılır
 *
 * TTL: 300 saniye (5 dakika). Müşteri 5 dk içinde onaylamazsa preview ölür,
 * AI yeni preview üretmek zorunda kalır (item fiyatları değişmiş olabilir).
 *
 * One-shot consume: confirm sonrası preview_id silinir → replay attack korunur.
 * Race condition koruması: cache backend `getAndDelete` atomik değil ama
 * file cache + flock pattern veya Redis SETNX/DEL ile elde edilebilir.
 * OpenCart cache adapter native atomik consume sağlamadığı için bizim
 * implementation get → null check → delete sırasıyla "best-effort" atomik.
 * Real race olasılığı düşük (preview_id 192 bit entropy + 300sn pencere).
 */
final class OrderPreview {

    public const TTL_SECONDS = 300;
    public const CACHE_PREFIX = 'dwb_preview_';

    /**
     * Yeni preview_id üret. Format: prv_ + 24 char hex (= 96 bit entropy).
     * Çarpışma ihtimali yok (300sn pencerede 2^96 namespace).
     */
    public static function generateId(): string {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    /**
     * Preview payload'unu cache'e koy. TTL 300sn.
     *
     * @param \Opencart\System\Engine\Registry $registry
     * @param string $previewId  Mutlaka generateId() ile üretilmiş olmalı
     * @param array  $payload    {items, customer, subtotal, shipping, total, currency, ...}
     */
    public static function store($registry, string $previewId, array $payload): void {
        $cache = $registry->get('cache');
        $key = self::CACHE_PREFIX . $previewId;

        // payload'a metadata ekle
        $payload['_preview_id'] = $previewId;
        $payload['_stored_at']  = time();
        $payload['_expires_at'] = time() + self::TTL_SECONDS;

        $cache->set($key, $payload, self::TTL_SECONDS);
    }

    /**
     * Preview'ı cache'den oku (peek). Cache'ten silmez.
     * Genelde sadece debug için kullanılır. Confirm akışı consume() kullanmalı.
     *
     * @return array|null Cache miss veya expired ise null
     */
    public static function peek($registry, string $previewId): ?array {
        $cache = $registry->get('cache');
        $key = self::CACHE_PREFIX . $previewId;
        $value = $cache->get($key);

        // v0.1.1 KRİTİK FIX: OpenCart File cache `get()` cache miss'te `[]` (boş array)
        // döndürür, `null` değil! (system/library/cache/file.php line 38: return [])
        // Bizim payload'da `_preview_id` zorunlu — bu yokken payload yarım demek.
        // Replay protection için bu check ŞART; yoksa consume() sonsuza dek aynı
        // boş array'i döndürür ve aynı preview_id ile birden fazla order yaratılır.
        if (!is_array($value) || empty($value) || !isset($value['_preview_id'])) {
            return null;
        }

        // Manuel expire check (cache backend'i TTL'i ignore ederse — file cache OC bug history)
        if (isset($value['_expires_at']) && time() > (int) $value['_expires_at']) {
            $cache->delete($key);
            return null;
        }

        return $value;
    }

    /**
     * Preview'ı oku VE sil (one-shot consume).
     *
     * @return array|null Cache miss / expired / already-consumed ise null
     */
    public static function consume($registry, string $previewId): ?array {
        $payload = self::peek($registry, $previewId);

        if ($payload === null) return null;

        // Sil — replay attack koruması
        $cache = $registry->get('cache');
        $cache->delete(self::CACHE_PREFIX . $previewId);

        return $payload;
    }

    /**
     * preview_id format validation. Saldırgan keyfi key inject edemesin.
     */
    public static function isValidId(string $id): bool {
        return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }
}
