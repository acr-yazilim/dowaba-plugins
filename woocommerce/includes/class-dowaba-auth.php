<?php
/**
 * DoWaba AI — Bearer Auth + IP whitelist (WooCommerce).
 */
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

final class Auth {

    /**
     * REST API request verify et.
     *
     * @return array{success: bool, status: int, error: string|null, client_ip: string}
     */
    public static function verify(\WP_REST_Request $request): array {
        $client_ip = self::client_ip();

        if (!get_option('dowaba_ai_status')) {
            return self::fail(503, 'DoWaba AI module is disabled', $client_ip);
        }

        $auth_header = $request->get_header('Authorization');
        if (!$auth_header || !preg_match('/^Bearer\s+(\S+)$/i', $auth_header, $m)) {
            return self::fail(401, 'Bearer token required', $client_ip);
        }
        $provided_key = $m[1];

        // Format sanity check
        if (!preg_match('/^wcm_[a-f0-9]{32,128}$/i', $provided_key)) {
            return self::fail(401, 'Invalid bearer token', $client_ip);
        }

        $stored_hash = (string) get_option('dowaba_ai_api_key_hash', '');
        if ($stored_hash === '') {
            return self::fail(503, 'API key not yet generated', $client_ip);
        }
        if (!hash_equals($stored_hash, hash('sha256', $provided_key))) {
            return self::fail(401, 'Invalid bearer token', $client_ip);
        }

        // IP whitelist
        $ip_whitelist = trim((string) get_option('dowaba_ai_ip_whitelist', ''));
        if ($ip_whitelist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $ip_whitelist)));
            if (!in_array($client_ip, $allowed, true)) {
                return self::fail(403, 'IP not whitelisted', $client_ip);
            }
        }

        // last_used_at update (best-effort)
        update_option('dowaba_ai_api_key_last_used', current_time('mysql'));

        return ['success' => true, 'status' => 200, 'error' => null, 'client_ip' => $client_ip];
    }

    private static function fail(int $status, string $error, string $client_ip): array {
        return ['success' => false, 'status' => $status, 'error' => $error, 'client_ip' => $client_ip];
    }

    public static function client_ip(): string {
        // WordPress'in REMOTE_ADDR alma yolu
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * Yeni API key üret (admin'den çağrılır).
     *
     * @return string Plain key (sadece bir kez gösterilir)
     */
    public static function generate_key(): string {
        $plain_key = 'wcm_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain_key);
        $prefix = substr($plain_key, 0, 12);

        update_option('dowaba_ai_api_key_hash', $hash);
        update_option('dowaba_ai_api_key_prefix', $prefix);
        update_option('dowaba_ai_api_key_last_used', null);

        return $plain_key;
    }
}
