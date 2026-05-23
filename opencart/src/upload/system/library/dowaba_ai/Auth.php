<?php
namespace Dowaba\Ai;

/**
 * Dowaba AI — Bearer Auth + IP whitelist verifier.
 *
 * Gelen istekleri doğrular:
 *   - Authorization: Bearer <opc_xxx> header'ı
 *   - sha256(provided) === stored_hash (constant-time)
 *   - IP whitelist (boş = kısıtlama yok)
 *   - api_key_last_used timestamp update (silent, başarısız check'leri etkilemez)
 *
 * Yanıt: Result struct (success bool + statusCode + error message).
 * Yanıtı `api.php::respond()` formatlayıp gönderir.
 *
 * Usage:
 *   $result = Auth::verify($registry);
 *   if (!$result['success']) { respond($result['status'], ['error' => $result['error']]); return; }
 *
 * Faz 2 — extracted from inline api.php guard().
 */
final class Auth {

    /**
     * Tek doğrulama metodu. Tüm guard'ları çalıştırır.
     *
     * @param \Opencart\System\Engine\Registry $registry OpenCart registry (config, request, db, log)
     * @return array{success: bool, status: int, error: string|null, client_ip: string}
     */
    public static function verify($registry): array {
        $config  = $registry->get('config');
        $request = $registry->get('request');

        $clientIp = self::clientIp($request);

        // 1) Module aktif mi
        if (!$config->get('module_dowaba_ai_status')) {
            return self::fail(503, 'Dowaba AI module is disabled', $clientIp);
        }

        // 2) Authorization header parse
        $authHeader = self::authHeader($request);
        if ($authHeader === '' || !preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
            return self::fail(401, 'Bearer token required', $clientIp);
        }
        $providedKey = $m[1];

        // 3) Plain key format sanity (opc_ + 64 hex chars)
        if (!preg_match('/^opc_[a-f0-9]{32,128}$/i', $providedKey)) {
            // Yanlış format → 401 (real key vermeden döner ki saldırgan format ayırt edemesin)
            return self::fail(401, 'Invalid bearer token', $clientIp);
        }

        // 4) Stored hash karşılaştırma (constant-time)
        $storedHash = (string) $config->get('module_dowaba_ai_api_key_hash');
        if ($storedHash === '') {
            return self::fail(503, 'API key not yet generated', $clientIp);
        }
        if (!hash_equals($storedHash, hash('sha256', $providedKey))) {
            return self::fail(401, 'Invalid bearer token', $clientIp);
        }

        // 5) IP whitelist
        $ipWhitelist = trim((string) $config->get('module_dowaba_ai_ip_whitelist'));
        if ($ipWhitelist !== '') {
            $allowed = array_map('trim', explode(',', $ipWhitelist));
            $allowed = array_filter($allowed, fn($ip) => $ip !== '');
            if (!in_array($clientIp, $allowed, true)) {
                return self::fail(403, 'IP not whitelisted', $clientIp);
            }
        }

        // 6) last_used_at update (best-effort, async-style — başarısız olursa request'i bozma)
        try {
            $setting = $registry->get('load')->model('setting/setting');
            // OC4 model lazy-load — düz DB update tercih edilir
            $db = $registry->get('db');
            $db->query("
                UPDATE `" . DB_PREFIX . "setting`
                SET `value` = '" . $db->escape(date('Y-m-d H:i:s')) . "'
                WHERE `code` = 'module_dowaba_ai' AND `key` = 'module_dowaba_ai_api_key_last_used'
            ");
        } catch (\Throwable $e) {
            // Silent — verify success ama last_used update fail oldu
        }

        return ['success' => true, 'status' => 200, 'error' => null, 'client_ip' => $clientIp];
    }

    // ---------------------------------------------------------------- helpers

    private static function fail(int $status, string $error, string $clientIp): array {
        return ['success' => false, 'status' => $status, 'error' => $error, 'client_ip' => $clientIp];
    }

    /**
     * Authorization header çekme — birden fazla kaynak fallback.
     * OpenCart 4'te bazı Apache/Nginx setup'larında HTTP_AUTHORIZATION super'a düşmüyor;
     * getallheaders() veya REDIRECT_HTTP_AUTHORIZATION da denenir.
     */
    private static function authHeader($request): string {
        $server = $request->server;

        $candidates = [
            $server['HTTP_AUTHORIZATION'] ?? '',
            $server['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
        ];

        foreach ($candidates as $h) {
            if ($h !== '') return (string) $h;
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach (['Authorization', 'authorization'] as $key) {
                if (!empty($headers[$key])) return (string) $headers[$key];
            }
        }

        return '';
    }

    /**
     * Client IP — REMOTE_ADDR (proxy header'lar GUVENİLMEZ, spoofable).
     * Eğer kullanıcı Cloudflare / proxy önünde ise X-Forwarded-For ayrı bir konfig olmalı (v0.2).
     */
    private static function clientIp($request): string {
        return (string) ($request->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
