<?php
/**
 * Dowaba AI — Bearer Auth + IP whitelist (OpenCart 3.x, global namespace).
 * OC4 namespace'li versiyonun port'u.
 */
final class DowabaAuth {

    public static function verify($registry) {
        $config  = $registry->get('config');
        $request = $registry->get('request');

        $clientIp = self::clientIp($request);

        if (!$config->get('module_dowaba_ai_status')) {
            return self::fail(503, 'Dowaba AI module is disabled', $clientIp);
        }

        $authHeader = self::authHeader($request);
        if ($authHeader === '' || !preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
            return self::fail(401, 'Bearer token required', $clientIp);
        }
        $providedKey = $m[1];

        if (!preg_match('/^opc_[a-f0-9]{32,128}$/i', $providedKey)) {
            return self::fail(401, 'Invalid bearer token', $clientIp);
        }

        $storedHash = (string)$config->get('module_dowaba_ai_api_key_hash');
        if ($storedHash === '') {
            return self::fail(503, 'API key not yet generated', $clientIp);
        }
        if (!hash_equals($storedHash, hash('sha256', $providedKey))) {
            return self::fail(401, 'Invalid bearer token', $clientIp);
        }

        $ipWhitelist = trim((string)$config->get('module_dowaba_ai_ip_whitelist'));
        if ($ipWhitelist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $ipWhitelist)));
            if (!in_array($clientIp, $allowed, true)) {
                return self::fail(403, 'IP not whitelisted', $clientIp);
            }
        }

        // last_used_at update — best-effort silent
        try {
            $db = $registry->get('db');
            $db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $db->escape(date('Y-m-d H:i:s')) . "' WHERE `code` = 'module_dowaba_ai' AND `key` = 'module_dowaba_ai_api_key_last_used'");
        } catch (\Throwable $e) {}

        return array('success' => true, 'status' => 200, 'error' => null, 'client_ip' => $clientIp);
    }

    private static function fail($status, $error, $clientIp) {
        return array('success' => false, 'status' => $status, 'error' => $error, 'client_ip' => $clientIp);
    }

    private static function authHeader($request) {
        $server = $request->server;
        $candidates = array(
            isset($server['HTTP_AUTHORIZATION']) ? $server['HTTP_AUTHORIZATION'] : '',
            isset($server['REDIRECT_HTTP_AUTHORIZATION']) ? $server['REDIRECT_HTTP_AUTHORIZATION'] : '',
        );
        foreach ($candidates as $h) {
            if ($h !== '') return (string)$h;
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach (array('Authorization', 'authorization') as $key) {
                if (!empty($headers[$key])) return (string)$headers[$key];
            }
        }
        return '';
    }

    private static function clientIp($request) {
        return isset($request->server['REMOTE_ADDR']) ? (string)$request->server['REMOTE_ADDR'] : '0.0.0.0';
    }
}
