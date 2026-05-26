<?php
/**
 * Dowaba AI — Audit Logger (OC3, global).
 */
final class DowabaAuditLogger {

    private static $tableEnsured = false;

    public static function ensureTable($registry) {
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
        } catch (\Throwable $e) {}
    }

    public static function write($registry, $functionSlug, $requestIp, $statusCode, $durationMs, $errorMessage = null) {
        try {
            self::ensureTable($registry);
            $db = $registry->get('db');
            $errorSafe = $errorMessage !== null ? mb_substr($errorMessage, 0, 500, 'UTF-8') : null;

            $sql = "INSERT INTO `" . DB_PREFIX . "dowaba_audit`
                    (`function_slug`, `request_ip`, `status_code`, `duration_ms`, `error_message`, `created_at`)
                    VALUES (
                        '" . $db->escape(mb_substr($functionSlug, 0, 64, 'UTF-8')) . "',
                        '" . $db->escape(mb_substr($requestIp, 0, 45, 'UTF-8')) . "',
                        " . (int)$statusCode . ",
                        " . (int)$durationMs . ",
                        " . ($errorSafe === null ? 'NULL' : "'" . $db->escape($errorSafe) . "'") . ",
                        NOW()
                    )";
            $db->query($sql);

            // Lazy cleanup — 1/500 ihtimalle eski log'ları sil (cron yok, OCMOD altyapısı sınırlı).
            // Retention = config'den 30 gün default. Production'da audit tablosu sınırsız büyümesin.
            if (random_int(1, 500) === 1) {
                $config = $registry->get('config');
                $retentionDays = (int) ($config->get('module_dowaba_ai_audit_retention_days') ?: 30);
                $retentionDays = max(1, min(365, $retentionDays));
                $db->query("DELETE FROM `" . DB_PREFIX . "dowaba_audit` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL " . $retentionDays . " DAY)");
            }
        } catch (\Throwable $e) {}
    }
}
