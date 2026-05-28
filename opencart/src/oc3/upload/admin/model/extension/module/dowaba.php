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

        // OC3 admin user_group (id=1, Administrator) permission'ına modül route'unu ekle.
        // OC3 marketplace installer modülü extract eder ama user_group permission'ını
        // OTOMATİK eklemiyor. Sonuç:
        // - admin Edit'e tıklayınca permission yok → access denied → login redirect
        // - AJAX'lar (regenerateKey vs.) HTML login sayfası dönüyor → "not valid JSON"
        // Direkt DB query (model context'inde $this->user erişilemez).
        // 2026-05-27 KRITIK FIX (v0.2.14): Önceki versiyonlar unserialize fail edince
        // permission'ı SIFIRLIYORDU (boş array ile UPDATE). Müşterilerin admin
        // user_group permission'larının tümü siliniyordu. Bu versiyonda:
        //  - Mevcut permission tanınmıyorsa (unserialize fail veya format farklı)
        //    HİÇ DOKUNMA, skip et.
        //  - Sadece access[]+modify[] array'leri TAM DOLU ise route ekle.
        //  - JSON saklanmış olabilir (custom temalar) → JSON parse fallback.
        $route = 'extension/module/dowaba';
        $rows = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group` WHERE user_group_id = 1")->rows;
        foreach ($rows as $row) {
            $raw = (string) $row['permission'];

            // Önce serialize dene (OC3 default)
            $perm = @unserialize($raw);

            // serialize fail ederse JSON dene (bazı customization'lar JSON saklar)
            $usesJson = false;
            if (!is_array($perm)) {
                $perm = @json_decode($raw, true);
                $usesJson = is_array($perm);
            }

            // Format hâlâ tanımsız → DOKUNMA, müşterinin permission'larını koru
            if (!is_array($perm) || !isset($perm['access']) || !is_array($perm['access']) || !isset($perm['modify']) || !is_array($perm['modify'])) {
                continue;
            }

            $changed = false;
            foreach (array('access', 'modify') as $key) {
                if (!in_array($route, $perm[$key], true)) {
                    $perm[$key][] = $route;
                    $changed = true;
                }
            }
            if (!$changed) continue; // zaten var, gereksiz UPDATE atma

            $newValue = $usesJson ? json_encode($perm) : serialize($perm);
            $this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission = '" . $this->db->escape($newValue) . "' WHERE user_group_id = " . (int)$row['user_group_id']);
        }
    }

    public function uninstall() {
        // No-op — data preservation
    }

    public function getAuditLog($limit = 100, $functionSlug = null, $statusCode = null) {
        // 2026-05-27 KRITIK FIX (v0.2.14): Önceden $this->install() çağrılıyordu — bu
        // her admin AJAX call'ında permission update'i tetikliyordu (felaket pattern).
        // Şimdi sadece tablo varsa SELECT yap; yoksa try/catch ile sessizce başarısız ol.
        // Tablo create işi install hook'unda bir kez (modül kurulum).
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
            return array();
        }

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
