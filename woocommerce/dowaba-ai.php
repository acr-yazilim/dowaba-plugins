<?php
/**
 * Plugin Name:       DoWaba AI — Sell on WhatsApp, Instagram & TikTok
 * Plugin URI:        https://dowaba.com
 * Description:       Turn your WooCommerce store into a social commerce machine. AI chatbot sells your products directly on WhatsApp, Instagram DM, and TikTok — 24/7, 30+ languages.
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Aydın Acar (DoWaba)
 * Author URI:        https://dowaba.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       dowaba-ai
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.5
 *
 * @package DowabaAI
 */

if (!defined('ABSPATH')) {
    exit; // Direct access engellendi
}

// ============================================================
// CONSTANTS
// ============================================================

define('DOWABA_AI_VERSION', '0.2.0');
define('DOWABA_AI_PLUGIN_FILE', __FILE__);
define('DOWABA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DOWABA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOWABA_AI_REST_NAMESPACE', 'dowaba/v1');
define('DOWABA_AI_AUDIT_TABLE', 'dowaba_audit');
define('DOWABA_AI_PREVIEW_TTL', 300); // 5 dakika

// ============================================================
// WOOCOMMERCE DEPENDENCY CHECK
// ============================================================

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>DoWaba AI</strong> plugin requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    // Tüm class'ları yükle
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-auth.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-scope-guard.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-audit-logger.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-order-preview.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-manifest.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-api.php';
    require_once DOWABA_AI_PLUGIN_DIR . 'includes/class-dowaba-admin.php';

    // REST routes register
    add_action('rest_api_init', [\DowabaAI\Manifest::class, 'register_routes']);
    add_action('rest_api_init', [\DowabaAI\Api::class, 'register_routes']);

    // Admin menu + settings page (sadece admin)
    if (is_admin()) {
        \DowabaAI\Admin::init();
    }
}, 20); // WC'den sonra

// ============================================================
// ACTIVATION / DEACTIVATION HOOKS
// ============================================================

/**
 * Plugin aktivasyonu — dowaba_audit tablosunu oluştur.
 */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . DOWABA_AI_AUDIT_TABLE;

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        audit_id       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        function_slug  VARCHAR(64) NOT NULL,
        request_ip     VARCHAR(45) NOT NULL,
        status_code    SMALLINT(3) NOT NULL,
        duration_ms    INT(11) NOT NULL DEFAULT 0,
        error_message  TEXT NULL,
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (audit_id),
        INDEX idx_created_at   (created_at),
        INDEX idx_function_slug(function_slug),
        INDEX idx_status_code  (status_code)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Default settings (sadece ilk install)
    add_option('dowaba_ai_status', 0);
    add_option('dowaba_ai_scope_read', 1);
    add_option('dowaba_ai_scope_write', 0);
    add_option('dowaba_ai_audit_retention_days', 30);
    add_option('dowaba_ai_ip_whitelist', '');
    add_option('dowaba_ai_api_key_hash', '');
    add_option('dowaba_ai_api_key_prefix', '');
});

/**
 * Plugin deaktivasyonu — şimdilik no-op (data preservation).
 */
register_deactivation_hook(__FILE__, function () {
    // No-op — uninstall.php DROP TABLE yapıyor
});

// ============================================================
// PLUGIN ACTION LINKS (admin plugin list)
// ============================================================

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=dowaba-ai') . '">' . __('Settings', 'dowaba-ai') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

add_filter('plugin_row_meta', function ($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://dowaba.com/destek" target="_blank">' . __('Support', 'dowaba-ai') . '</a>';
        $links[] = '<a href="https://github.com/rdtvaacar/dowaba-plugins" target="_blank">GitHub</a>';
    }
    return $links;
}, 10, 2);
