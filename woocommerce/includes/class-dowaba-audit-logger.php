<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

final class AuditLogger {

    public static function write(string $function_slug, string $request_ip, int $status_code, int $duration_ms, ?string $error_message = null): void {
        global $wpdb;
        $table = $wpdb->prefix . DOWABA_AI_AUDIT_TABLE;

        try {
            $wpdb->insert(
                $table,
                [
                    'function_slug' => mb_substr($function_slug ?: 'unknown', 0, 64, 'UTF-8'),
                    'request_ip'    => mb_substr($request_ip, 0, 45, 'UTF-8'),
                    'status_code'   => $status_code,
                    'duration_ms'   => $duration_ms,
                    'error_message' => $error_message !== null ? mb_substr($error_message, 0, 500, 'UTF-8') : null,
                    'created_at'    => current_time('mysql'),
                ],
                ['%s', '%s', '%d', '%d', '%s', '%s']
            );
        } catch (\Throwable $e) {
            // Silent — audit fail-safe, request'i bozma
        }
    }

    public static function get_logs(int $limit = 100, ?string $function_slug = null, ?int $status_code = null): array {
        global $wpdb;
        $table = $wpdb->prefix . DOWABA_AI_AUDIT_TABLE;

        $where = ['1=1'];
        $args = [];
        if ($function_slug !== null && $function_slug !== '') {
            $where[] = 'function_slug = %s';
            $args[] = $function_slug;
        }
        if ($status_code !== null) {
            $where[] = 'status_code = %d';
            $args[] = $status_code;
        }
        $args[] = max(1, min(500, $limit));

        $sql = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT %d";
        return $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A) ?: [];
    }

    public static function purge_old(int $retention_days = 30): int {
        global $wpdb;
        $table = $wpdb->prefix . DOWABA_AI_AUDIT_TABLE;
        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            max(1, $retention_days)
        ));
    }
}
