<?php
/**
 * Dowaba AI Integration for PrestaShop — Bearer Auth + IP whitelist.
 *
 * Validates incoming requests:
 *   - Authorization: Bearer <api_key> header (sha256 hash comparison)
 *   - Optional IP whitelist (Dowaba production IPs)
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DowabaAuth
{
    public static function verify(): array
    {
        $client_ip = self::clientIp();

        if (!(int) Configuration::get('DOWABA_AI_STATUS')) {
            return self::fail(503, 'DoWaba AI module is disabled', $client_ip);
        }

        $auth_header = self::authHeader();
        if (!$auth_header || !preg_match('/^Bearer\s+(\S+)$/i', $auth_header, $m)) {
            return self::fail(401, 'Bearer token required', $client_ip);
        }
        $provided_key = $m[1];

        if (!preg_match('/^psm_[a-f0-9]{32,128}$/i', $provided_key)) {
            return self::fail(401, 'Invalid bearer token', $client_ip);
        }

        $stored_hash = (string) Configuration::get('DOWABA_AI_API_KEY_HASH');
        if ('' === $stored_hash) {
            return self::fail(503, 'API key not yet generated', $client_ip);
        }
        if (!hash_equals($stored_hash, hash('sha256', $provided_key))) {
            return self::fail(401, 'Invalid bearer token', $client_ip);
        }

        $ip_whitelist = trim((string) Configuration::get('DOWABA_AI_IP_WHITELIST'));
        if ('' !== $ip_whitelist) {
            $allowed = array_filter(array_map('trim', explode(',', $ip_whitelist)));
            if (!in_array($client_ip, $allowed, true)) {
                return self::fail(403, 'IP not whitelisted', $client_ip);
            }
        }

        Configuration::updateValue('DOWABA_AI_API_KEY_LAST_USED', date('Y-m-d H:i:s'));

        return ['success' => true, 'status' => 200, 'error' => null, 'client_ip' => $client_ip];
    }

    public static function generateKey(): string
    {
        $plain_key = 'psm_'.bin2hex(random_bytes(32));
        Configuration::updateValue('DOWABA_AI_API_KEY_HASH', hash('sha256', $plain_key));
        Configuration::updateValue('DOWABA_AI_API_KEY_PREFIX', substr($plain_key, 0, 12));
        Configuration::deleteByName('DOWABA_AI_API_KEY_LAST_USED');

        return $plain_key;
    }

    private static function fail(int $status, string $error, string $client_ip): array
    {
        return ['success' => false, 'status' => $status, 'error' => $error, 'client_ip' => $client_ip];
    }

    public static function authHeader(): string
    {
        $hdr = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
        if ('' === $hdr && function_exists('getallheaders')) {
            $headers = getallheaders();
            $hdr = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
        }

        return (string) $hdr;
    }

    public static function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
