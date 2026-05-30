<?php
/**
 * Dowaba AI Connector — Bearer Auth + IP whitelist verifier.
 *
 * Validates each inbound request:
 *   - Authorization: Bearer <mgm_xxx>  (or ?token= / ?api_key= fallback)
 *   - sha256(provided) === stored_hash (constant-time)
 *   - optional IP whitelist
 *   - touches api_key_last_used (silent, direct DB — never breaks the request)
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class Auth
{
    /** Bearer key format: mgm_ + 32..128 hex chars. */
    private const KEY_PATTERN = '/^mgm_[a-f0-9]{32,128}$/i';

    public function __construct(
        private readonly Config $config,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * @return array{success: bool, status: int, error: string|null, client_ip: string}
     */
    public function verify(HttpRequest $request): array
    {
        $clientIp = (string) ($this->remoteAddress->getRemoteAddress() ?: '0.0.0.0');

        // 1) Module enabled?
        if (!$this->config->isEnabled()) {
            return $this->fail(503, 'Dowaba AI module is disabled', $clientIp);
        }

        // 2) Authorization header parse
        $authHeader = $this->authHeader($request);
        if ($authHeader === '' || !preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
            return $this->fail(401, 'Bearer token required', $clientIp);
        }
        $providedKey = $m[1];

        // 3) Plain key format sanity
        if (!preg_match(self::KEY_PATTERN, $providedKey)) {
            return $this->fail(401, 'Invalid bearer token', $clientIp);
        }

        // 4) Stored hash compare (constant-time)
        $storedHash = $this->config->getApiKeyHash();
        if ($storedHash === '') {
            return $this->fail(503, 'API key not yet generated', $clientIp);
        }
        if (!hash_equals($storedHash, hash('sha256', $providedKey))) {
            return $this->fail(401, 'Invalid bearer token', $clientIp);
        }

        // 5) IP whitelist
        $ipWhitelist = $this->config->getIpWhitelist();
        if ($ipWhitelist !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $ipWhitelist)));
            if (!in_array($clientIp, $allowed, true)) {
                return $this->fail(403, 'IP not whitelisted', $clientIp);
            }
        }

        // 6) last_used touch — best effort, direct DB, no cache flush
        try {
            $this->config->saveValueDirect(Config::XML_API_KEY_LAST_USED, date('Y-m-d H:i:s'));
        } catch (\Throwable $e) {
            // silent — verify still succeeded
        }

        return ['success' => true, 'status' => 200, 'error' => null, 'client_ip' => $clientIp];
    }

    // -------------------------------------------------------------- helpers

    private function fail(int $status, string $error, string $clientIp): array
    {
        return ['success' => false, 'status' => $status, 'error' => $error, 'client_ip' => $clientIp];
    }

    /**
     * Authorization header with multi-source fallback.
     *
     * Many Apache/LiteSpeed/FastCGI setups do not pass HTTP_AUTHORIZATION to PHP
     * (no `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` in .htaccess)
     * → header is empty. The manifest also injects the token into the query string,
     * so we read ?token= / ?api_key= as a last resort. Same opc/mgm format + sha256
     * verify is applied either way.
     */
    private function authHeader(HttpRequest $request): string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            $value = (string) ($request->getServer($key) ?? '');
            if ($value !== '') {
                return $value;
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach (['Authorization', 'authorization'] as $key) {
                if (!empty($headers[$key])) {
                    return (string) $headers[$key];
                }
            }
        }

        foreach (['token', 'api_key'] as $qk) {
            $value = (string) ($request->getParam($qk) ?? '');
            if ($value !== '') {
                return 'Bearer ' . $value;
            }
        }

        return '';
    }
}
