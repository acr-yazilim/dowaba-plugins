<?php
/**
 * Dowaba AI Integration for PrestaShop — Order Preview Cache (DB-backed).
 *
 * 2-step order confirmation: preview generated → customer "yes" → confirm.
 * Backend: ps_dowaba_preview DB table (5-min TTL).
 * One-shot consume — replay protection for order creation.
 *
 * NOT: v0.2.4'e kadar PrestaShop Cache::store kullanılıyordu, ama default
 * install'da `ps_cache_enable=false` olduğu için preview kaybolup
 * "expired or already consumed" hatası veriyordu. v0.2.5'te DB-backed yapıldı.
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
    public const TABLE = 'dowaba_preview';
    public const TTL_SECONDS = 300;

    public static function generateId(): string
    {
        return 'prv_' . bin2hex(random_bytes(12));
    }

    public static function store(string $preview_id, array $payload): void
    {
        self::ensureTable();
        $db = Db::getInstance();
        $payload['_preview_id'] = $preview_id;
        $payload['_stored_at'] = time();
        $payload['_expires_at'] = time() + self::TTL_SECONDS;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db->execute(
            'REPLACE INTO `' . _DB_PREFIX_ . self::TABLE . '` (preview_id, payload, expires_at) VALUES ('
            . "'" . pSQL($preview_id) . "',"
            . "'" . pSQL($json, true) . "',"
            . 'DATE_ADD(NOW(), INTERVAL ' . (int) self::TTL_SECONDS . ' SECOND))'
        );
    }

    public static function peek(string $preview_id): ?array
    {
        if (!self::isValidId($preview_id)) {
            return null;
        }
        self::ensureTable();
        $db = Db::getInstance();
        $row = $db->getRow(
            'SELECT payload FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE preview_id = '
            . "'" . pSQL($preview_id) . "' AND expires_at > NOW()"
        );
        if (!$row || empty($row['payload'])) {
            return null;
        }
        $value = json_decode($row['payload'], true);

        return is_array($value) ? $value : null;
    }

    public static function consume(string $preview_id): ?array
    {
        $payload = self::peek($preview_id);
        if (null === $payload) {
            return null;
        }
        Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE preview_id = ' . "'" . pSQL($preview_id) . "'");

        return $payload;
    }

    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('/^prv_[a-f0-9]{24}$/', $id);
    }

    /**
     * Defansif tablo create — install hook tetiklenmediyse veya
     * mevcut kurulum upgrade ediliyorsa ilk peek/store'da otomatik oluştur.
     */
    private static function ensureTable(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` ('
            . 'preview_id VARCHAR(40) NOT NULL,'
            . 'payload    LONGTEXT NOT NULL,'
            . 'expires_at DATETIME NOT NULL,'
            . 'PRIMARY KEY (preview_id),'
            . 'INDEX idx_expires (expires_at)'
            . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
        $checked = true;
    }
}
