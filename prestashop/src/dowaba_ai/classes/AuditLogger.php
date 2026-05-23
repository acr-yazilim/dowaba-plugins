<?php
class DowabaAuditLogger
{
    public static function write(string $function_slug, string $request_ip, int $status_code, int $duration_ms, ?string $error_message = null): void
    {
        try {
            $db = Db::getInstance();
            $error_safe = $error_message !== null ? mb_substr($error_message, 0, 500, 'UTF-8') : null;
            $sql = "INSERT INTO `" . _DB_PREFIX_ . "dowaba_audit`
                    (function_slug, request_ip, status_code, duration_ms, error_message, created_at)
                    VALUES (
                        '" . pSQL(mb_substr($function_slug, 0, 64, 'UTF-8')) . "',
                        '" . pSQL(mb_substr($request_ip, 0, 45, 'UTF-8')) . "',
                        " . (int) $status_code . ",
                        " . (int) $duration_ms . ",
                        " . ($error_safe === null ? 'NULL' : "'" . pSQL($error_safe) . "'") . ",
                        NOW()
                    )";
            $db->execute($sql);
        } catch (\Throwable $e) {
            // Silent
        }
    }

    public static function getLogs(int $limit = 100, ?string $function_slug = null, ?int $status_code = null): array
    {
        $where = ['1=1'];
        if ($function_slug !== null && $function_slug !== '') {
            $where[] = "function_slug = '" . pSQL($function_slug) . "'";
        }
        if ($status_code !== null) {
            $where[] = "status_code = " . (int) $status_code;
        }
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "dowaba_audit` WHERE " . implode(' AND ', $where)
             . " ORDER BY created_at DESC LIMIT " . max(1, min(500, $limit));

        return Db::getInstance()->executeS($sql) ?: [];
    }
}
