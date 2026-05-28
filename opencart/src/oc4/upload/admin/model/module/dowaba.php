<?php
namespace Opencart\Admin\Model\Extension\DowabaAi\Module;

/**
 * Dowaba AI — Admin Model
 *
 * Install/uninstall hook'ları + dowaba_audit tablosu yönetimi.
 * Faz 2'de AuditLogger bu modeli kullanır.
 */
class Dowaba extends \Opencart\System\Engine\Model {

    /**
     * Extension install'da çağrılır. dowaba_audit tablosunu oluşturur +
     * admin user_group'a modül route'unu (access + modify) ekler.
     * IF NOT EXISTS / JSON_CONTAINS guard — re-install güvenli.
     */
    public function install(): void {
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

        // 2026-05-27 KRITIK FIX (v0.2.14): Önceki versiyonlar JSON decode fail edince
        // permission'ı SIFIRLIYORDU (boş array ile UPDATE). Müşterilerin admin
        // user_group permission'larının tümü silinebiliyordu.
        // Şimdi: mevcut permission tanımsızsa HİÇ DOKUNMA, skip et.
        $route = 'extension/dowaba_ai/module/dowaba';
        $rows = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group` WHERE user_group_id = 1")->rows;
        foreach ($rows as $row) {
            $perm = json_decode((string)($row['permission'] ?? ''), true);
            // Format tanımsız → DOKUNMA, müşterinin permission'larını koru
            if (!is_array($perm) || !isset($perm['access']) || !is_array($perm['access']) || !isset($perm['modify']) || !is_array($perm['modify'])) {
                continue;
            }
            $changed = false;
            foreach (['access', 'modify'] as $key) {
                if (!in_array($route, $perm[$key], true)) {
                    $perm[$key][] = $route;
                    $changed = true;
                }
            }
            if (!$changed) continue;
            $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission = '" . $this->db->escape(json_encode($perm)) . "' WHERE user_group_id = " . (int)$row['user_group_id']);
        }
    }

    /**
     * Extension uninstall'da çağrılır. Şu an tablo DROP EDİLMEZ — audit data preservation.
     * (Aydın isterse Faz 4'te opsiyonel "data wipe" flag eklenir.)
     */
    public function uninstall(): void {
        // No-op. Data preservation policy.
    }

    /**
     * Son N kayıt — admin "Audit Log" sekmesi için.
     * Faz 2'de doldurulacak. Şimdilik placeholder.
     */
    public function getAuditLog(int $limit = 100, ?string $functionSlug = null, ?int $statusCode = null): array {
        // 2026-05-27 KRITIK FIX (v0.2.14): Önceden $this->install() çağrılıyordu — bu
        // her admin AJAX call'ında permission update'i tetikliyordu (felaket pattern).
        // Şimdi sadece tablo varsa SELECT yap; yoksa try/catch ile sessizce başarısız ol.
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "dowaba_audit` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            return [];
        }

        $sql = "SELECT * FROM `" . DB_PREFIX . "dowaba_audit` WHERE 1=1";
        $params = [];

        if ($functionSlug !== null) {
            $sql .= " AND function_slug = '" . $this->db->escape($functionSlug) . "'";
        }
        if ($statusCode !== null) {
            $sql .= " AND status_code = " . (int)$statusCode;
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . max(1, min(500, $limit));

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Retention cleanup — N günden eski audit kayıtlarını sil.
     * OpenCart cron task'ı veya install'da scheduler'a register edilir (Faz 2).
     */
    public function purgeOldAuditLogs(int $retentionDays = 30): int {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "dowaba_audit`
            WHERE created_at < DATE_SUB(NOW(), INTERVAL " . (int)$retentionDays . " DAY)
        ");
        return $this->db->countAffected();
    }
}
