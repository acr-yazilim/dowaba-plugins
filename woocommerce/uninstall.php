<?php
/**
 * DoWaba AI — Uninstall script.
 * Plugin tamamen kaldırıldığında (delete) çalışır. Deactivate'te değil.
 * Tüm settings ve dowaba_audit tablosunu temizler.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Settings sil
$settings = [
    'dowaba_ai_status',
    'dowaba_ai_scope_read',
    'dowaba_ai_scope_write',
    'dowaba_ai_audit_retention_days',
    'dowaba_ai_ip_whitelist',
    'dowaba_ai_api_key_hash',
    'dowaba_ai_api_key_prefix',
    'dowaba_ai_api_key_last_used',
    'dowaba_ai_manifest_base_url',
];
foreach ($settings as $key) {
    delete_option($key);
}

// Audit log tablosunu drop et (data preservation isteyen kullanıcı önce export etmeli)
$table = $wpdb->prefix . 'dowaba_audit';
$wpdb->query("DROP TABLE IF EXISTS $table");

// Transients (order preview cache) temizle
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dwb_preview_%' OR option_name LIKE '_transient_timeout_dwb_preview_%'");
