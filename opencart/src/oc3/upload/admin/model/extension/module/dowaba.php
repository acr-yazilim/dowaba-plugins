<?php
/**
 * Dowaba AI — Admin Model (OpenCart 3.x)
 * Install/uninstall hook + dowaba_audit table.
 */
class ModelExtensionModuleDowaba extends Model {

    public function install() {
        $this->db->query("
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
    }

    public function uninstall() {
        // No-op — data preservation
    }

    public function getAuditLog($limit = 100, $functionSlug = null, $statusCode = null) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "dowaba_audit` WHERE 1=1";

        if ($functionSlug !== null && $functionSlug !== '') {
            $sql .= " AND function_slug = '" . $this->db->escape($functionSlug) . "'";
        }
        if ($statusCode !== null) {
            $sql .= " AND status_code = " . (int)$statusCode;
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . max(1, min(500, (int)$limit));

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function purgeOldAuditLogs($retentionDays = 30) {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "dowaba_audit`
            WHERE created_at < DATE_SUB(NOW(), INTERVAL " . max(1, (int)$retentionDays) . " DAY)
        ");
        return $this->db->countAffected();
    }
}
