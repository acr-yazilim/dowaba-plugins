<?php
namespace Opencart\System\Library\Extension\DowabaAi;

/**
 * Dowaba AI — Audit Logger.
 *
 * Her gelen API isteğini `dowaba_audit` tablosuna yazar:
 *   function_slug, request_ip, status_code, duration_ms, error_message, created_at
 *
 * 30 gün retention (admin settings'te ayarlanabilir). Cron task purgeOld() çağırır.
 *
 * Best-effort: log yazma fail olursa request fail olmaz (try/catch silent).
 */
final class AuditLogger {

    /**
     * Per-request flag — ensureTable bir kez çağrılır.
     * (v0.1.1: defensive — admin install hook tetiklenmediyse bile tablo otomatik oluşur.)
     */
    private static bool $tableEnsured = false;

    /**
     * Tablo varlığını garanti et. Admin install hook tetiklenmediği durumlarda
     * (manuel DB manipulation, custom install scenarios) ilk write çağrısında
     * otomatik CREATE. Idempotent: IF NOT EXISTS, request başına 1 kez.
     */
    public static function ensureTable($registry): void {
        if (self::$tableEnsured) return;

        try {
            $db = $registry->get('db');
            $db->query("
                CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "dowaba_audit` (
                    `audit_id`       INT(11) NOT NULL AUTO_INCREMENT,
                    `function_slug`  VARCHAR(64) NOT NULL,
                    `request_ip`     VARCHAR(45) NOT NULL,
                    `status_code`    SMALLINT(3) NOT NULL,
                    `duration_ms`    INT(11) NOT NULL DEFAULT 0,
                    `error_message`  TEXT NULL,
                    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`audit_id`),
                    INDEX `idx_created_at`   (`created_at`),
                    INDEX `idx_function_slug`(`function_slug`),
                    INDEX `idx_status_code`  (`status_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            self::$tableEnsured = true;
        } catch (\Throwable $e) {
            // Silent — write() catch'i de zaten var
        }
    }

    /**
     * Audit kaydı yaz.
     *
     * @param \Opencart\System\Engine\Registry $registry
     * @param string      $functionSlug   Manifest'teki function slug (örn opc_product_search)
     * @param string      $requestIp      İstek atan client IP
     * @param int         $statusCode     HTTP response status
     * @param int         $durationMs     Endpoint execution süresi (ms)
     * @param string|null $errorMessage   Hata varsa kısa açıklama (max 500 char)
     */
    public static function write(
        $registry,
        string $functionSlug,
        string $requestIp,
        int $statusCode,
        int $durationMs,
        ?string $errorMessage = null
    ): void {
        try {
            self::ensureTable($registry);

            $db = $registry->get('db');
            $errorSafe = $errorMessage !== null
                ? mb_substr($errorMessage, 0, 500, 'UTF-8')
                : null;

            $sql = "INSERT INTO `" . DB_PREFIX . "dowaba_audit`
                    (`function_slug`, `request_ip`, `status_code`, `duration_ms`, `error_message`, `created_at`)
                    VALUES (
                        '" . $db->escape(mb_substr($functionSlug, 0, 64, 'UTF-8')) . "',
                        '" . $db->escape(mb_substr($requestIp, 0, 45, 'UTF-8')) . "',
                        " . (int) $statusCode . ",
                        " . (int) $durationMs . ",
                        " . ($errorSafe === null ? 'NULL' : "'" . $db->escape($errorSafe) . "'") . ",
                        NOW()
                    )";

            $db->query($sql);
        } catch (\Throwable $e) {
            // Silent — audit log fail-safe, request'i bozma
            // Faz 2.1: optional error_log() if WARN level
        }
    }

    /**
     * N günden eski audit kayıtlarını sil (retention).
     * OpenCart cron task'ından veya manuel admin button'dan tetiklenir.
     */
    public static function purgeOld($registry, int $retentionDays = 30): int {
        try {
            $db = $registry->get('db');
            $db->query("
                DELETE FROM `" . DB_PREFIX . "dowaba_audit`
                WHERE created_at < DATE_SUB(NOW(), INTERVAL " . max(1, (int) $retentionDays) . " DAY)
            ");
            return $db->countAffected();
        } catch (\Throwable $e) {
            return -1;
        }
    }

    /**
     * Stats — admin "Audit Log" sekmesi widget'ı için.
     * Son 24 saatte function başına çağrı sayısı + ortalama duration + error oranı.
     */
    public static function getStats24h($registry): array {
        try {
            $db = $registry->get('db');
            $rows = $db->query("
                SELECT
                    function_slug,
                    COUNT(*) AS total,
                    AVG(duration_ms) AS avg_duration_ms,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_count
                FROM `" . DB_PREFIX . "dowaba_audit`
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY function_slug
                ORDER BY total DESC
            ")->rows;

            return array_map(fn($r) => [
                'function_slug'    => $r['function_slug'],
                'total'            => (int) $r['total'],
                'avg_duration_ms'  => (int) round((float) $r['avg_duration_ms']),
                'error_count'      => (int) $r['error_count'],
                'error_rate'       => (int) $r['total'] > 0
                    ? round((float) $r['error_count'] / (float) $r['total'] * 100, 2)
                    : 0,
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
