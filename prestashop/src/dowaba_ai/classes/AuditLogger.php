<?php
/**
 * Dowaba AI Integration for PrestaShop — Audit Logger.
 *
 * Records every Dowaba AI API call into ps_dowaba_audit table.
 * 30-day retention (configurable). Stores function_slug, IP, status_code, duration_ms.
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DowabaAuditLogger
{
    public static function write(string $function_slug, string $request_ip, int $status_code, int $duration_ms, ?string $error_message = null): void
    {
        try {
            $db = Db::getInstance();
            $error_safe = null !== $error_message ? mb_substr($error_message, 0, 500, 'UTF-8') : null;
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . "dowaba_audit`
                    (function_slug, request_ip, status_code, duration_ms, error_message, created_at)
                    VALUES (
                        '" . pSQL(mb_substr($function_slug, 0, 64, 'UTF-8')) . "',
                        '" . pSQL(mb_substr($request_ip, 0, 45, 'UTF-8')) . "',
                        " . (int) $status_code . ',
                        ' . (int) $duration_ms . ',
                        ' . (null === $error_safe ? 'NULL' : "'" . pSQL($error_safe) . "'") . ',
                        NOW()
                    )';
            $db->execute($sql);

            // Lazy retention cleanup — 1/500 ihtimalle eski log'ları sil (cron yok).
            // Disk-fill bug'ı (audit tablosu sınırsız büyüme) önlenir.
            if (1 === random_int(1, 500)) {
                $retention = (int) (Configuration::get('DOWABA_AI_AUDIT_RETENTION_DAYS') ?: 30);
                $retention = max(1, min(365, $retention));
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'dowaba_audit` WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $retention . ' DAY)');
            }
        } catch (\Throwable $e) {
            // Silent
        }
    }

    public static function getLogs(int $limit = 100, ?string $function_slug = null, ?int $status_code = null): array
    {
        $where = ['1=1'];
        if (null !== $function_slug && '' !== $function_slug) {
            $where[] = "function_slug = '" . pSQL($function_slug) . "'";
        }
        if (null !== $status_code) {
            $where[] = 'status_code = ' . (int) $status_code;
        }
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dowaba_audit` WHERE ' . implode(' AND ', $where)
             . ' ORDER BY created_at DESC LIMIT ' . max(1, min(500, $limit));

        return Db::getInstance()->executeS($sql) ?: [];
    }
}
