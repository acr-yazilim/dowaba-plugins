<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

/**
 * Admin settings page — wp-admin/admin.php?page=dowaba-ai
 */
final class Admin {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('wp_ajax_dowaba_regenerate_key', [self::class, 'ajax_regenerate_key']);
        add_action('wp_ajax_dowaba_test_connection', [self::class, 'ajax_test_connection']);
        add_action('wp_ajax_dowaba_audit_log', [self::class, 'ajax_audit_log']);
    }

    public static function add_menu(): void {
        add_menu_page(
            'DoWaba AI',
            'DoWaba AI',
            'manage_options',
            'dowaba-ai',
            [self::class, 'render_page'],
            'dashicons-format-chat',
            58
        );
    }

    public static function register_settings(): void {
        $settings = [
            'dowaba_ai_status', 'dowaba_ai_scope_read', 'dowaba_ai_scope_write',
            'dowaba_ai_audit_retention_days', 'dowaba_ai_ip_whitelist',
            'dowaba_ai_manifest_base_url',
        ];
        foreach ($settings as $s) {
            register_setting('dowaba_ai_settings', $s);
        }
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'dowaba-ai'));
        }

        // POST → save
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && check_admin_referer('dowaba_ai_settings_save')) {
            update_option('dowaba_ai_status',                isset($_POST['dowaba_ai_status']) ? 1 : 0);
            update_option('dowaba_ai_scope_read',            isset($_POST['dowaba_ai_scope_read']) ? 1 : 0);
            update_option('dowaba_ai_scope_write',           isset($_POST['dowaba_ai_scope_write']) ? 1 : 0);
            update_option('dowaba_ai_audit_retention_days', (int) ($_POST['dowaba_ai_audit_retention_days'] ?? 30));
            update_option('dowaba_ai_ip_whitelist',          sanitize_text_field($_POST['dowaba_ai_ip_whitelist'] ?? ''));
            update_option('dowaba_ai_manifest_base_url',     esc_url_raw($_POST['dowaba_ai_manifest_base_url'] ?? ''));
            echo '<div class="notice notice-success is-dismissible"><p>✅ Settings saved.</p></div>';
        }

        $manifest_url = rest_url(DOWABA_AI_REST_NAMESPACE . '/manifest');
        $api_key_prefix = (string) get_option('dowaba_ai_api_key_prefix', '');

        include DOWABA_AI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public static function ajax_regenerate_key(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Permission denied'], 403);
        }
        check_ajax_referer('dowaba_ai_ajax', 'nonce');

        $plain_key = Auth::generate_key();
        wp_send_json_success([
            'plain_key' => $plain_key,
            'prefix'    => substr($plain_key, 0, 12),
        ]);
    }

    public static function ajax_test_connection(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Permission denied'], 403);
        }
        check_ajax_referer('dowaba_ai_ajax', 'nonce');

        $manifest_url = rest_url(DOWABA_AI_REST_NAMESPACE . '/manifest');
        $response = wp_remote_get($manifest_url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            wp_send_json_error(['error' => $response->get_error_message(), 'url' => $manifest_url]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $manifest = json_decode($body, true);

        if ($code !== 200 || !is_array($manifest) || !isset($manifest['schema_version'])) {
            wp_send_json_error(['error' => 'HTTP ' . $code . ' or invalid JSON', 'url' => $manifest_url]);
        }

        wp_send_json_success([
            'url' => $manifest_url,
            'schema_version' => $manifest['schema_version'],
            'function_count' => count($manifest['functions'] ?? []),
        ]);
    }

    public static function ajax_audit_log(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Permission denied'], 403);
        }
        check_ajax_referer('dowaba_ai_ajax', 'nonce');

        $limit = max(10, min(500, (int) ($_GET['limit'] ?? 100)));
        $function_slug = trim((string) ($_GET['function_slug'] ?? '')) ?: null;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : null;

        $rows = AuditLogger::get_logs($limit, $function_slug, $status);
        wp_send_json_success(['count' => count($rows), 'rows' => $rows]);
    }
}
