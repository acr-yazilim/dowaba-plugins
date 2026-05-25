<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dowaba — Saf PHP Snippet
|--------------------------------------------------------------------------
|
| Framework YOK. WordPress eklentisi, Symfony küçük servisi, raw PHP siteye
| Dowaba SaaS entegrasyonu eklemek için 3 helper fonksiyon:
|
|   dowaba_request(method, path, body, opts)  — curl + JSON + Bearer auth
|   dowaba_verify_webhook(raw_body, signature, secret) — HMAC-SHA256 doğrula
|   dowaba_widget_token(payload, secret, ttl)  — widget_user_token üret
|
| Kullanım: require __DIR__.'/lib/dowaba.php';
*/

if (! defined('DOWABA_TIMEOUT_SECONDS')) {
    define('DOWABA_TIMEOUT_SECONDS', 10);
}

if (! defined('DOWABA_USER_AGENT')) {
    define('DOWABA_USER_AGENT', 'Dowaba-PHP-Snippet/0.1');
}

/**
 * Dowaba REST API'sine HTTP isteği gönder.
 *
 * @param  string  $method  GET, POST, PUT, PATCH, DELETE
 * @param  string  $path  /api/wa/send, /api/conversations, vb.
 * @param  array|null  $body  JSON body (POST/PUT/PATCH için)
 * @param  array  $opts  ['base_url' => '', 'token' => '', 'timeout' => 10]
 * @return array{status:int, data:mixed, raw:string}
 */
function dowaba_request(string $method, string $path, ?array $body = null, array $opts = []): array
{
    $baseUrl = rtrim($opts['base_url'] ?? (defined('DOWABA_URL') ? DOWABA_URL : 'https://dowaba.com'), '/');
    $token = $opts['token'] ?? (defined('DOWABA_ACCESS_TOKEN') ? DOWABA_ACCESS_TOKEN : '');
    $timeout = (int) ($opts['timeout'] ?? DOWABA_TIMEOUT_SECONDS);

    $url = $baseUrl.'/'.ltrim($path, '/');
    $headers = [
        'Accept: application/json',
        'User-Agent: '.DOWABA_USER_AGENT,
    ];

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer '.$token;
    }

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    if ($err) {
        throw new RuntimeException("Dowaba {$method} {$path} curl hatası: {$err}");
    }

    $data = $raw !== '' && $raw !== false ? json_decode((string) $raw, true) : null;

    return [
        'status' => $status,
        'data' => $data,
        'raw' => (string) $raw,
    ];
}

/**
 * Dowaba'dan gelen webhook'un HMAC-SHA256 imzasını doğrula.
 *
 * Dowaba webhook header'ında: X-Dowaba-Signature: sha256=<hex>
 * Yazılımcı kendi endpoint'inde bu fonksiyonu çağırır:
 *
 *   $body = file_get_contents('php://input');
 *   $sig = $_SERVER['HTTP_X_DOWABA_SIGNATURE'] ?? '';
 *   if (! dowaba_verify_webhook($body, $sig, DOWABA_WEBHOOK_SECRET)) http_response_code(401);
 */
function dowaba_verify_webhook(string $rawBody, string $signatureHeader, string $secret): bool
{
    if ($signatureHeader === '' || $secret === '') {
        return false;
    }

    // Header format: "sha256=<hex>"
    $parts = explode('=', $signatureHeader, 2);

    if (count($parts) !== 2 || strtolower($parts[0]) !== 'sha256') {
        return false;
    }

    $expected = hash_hmac('sha256', $rawBody, $secret);

    return hash_equals($expected, strtolower($parts[1]));
}

/**
 * Widget user token üret (HMAC-imzalı, Dowaba widget /api/widget/auth tarafından
 * doğrulanır). Yazılımcı kendi backend'inde bu token'ı üretip widget snippet'ine
 * `data-user-token="..."` olarak basar.
 *
 * Format:
 *   payload_b64 = base64url(JSON({user_id, email, site_id, exp, iat, nonce, ...}))
 *   signature_b64 = base64url(HMAC-SHA256(secret, payload_b64))
 *   token = payload_b64 + '.' + signature_b64
 */
function dowaba_widget_token(array $payload, string $secret, int $ttl = 300): string
{
    $now = time();
    $payload = array_merge($payload, [
        'iat' => $now,
        'exp' => $now + $ttl,
        'nonce' => bin2hex(random_bytes(8)),
    ]);

    $payloadB64 = dowaba_base64url_encode(
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
    );

    $signature = hash_hmac('sha256', $payloadB64, $secret, true);
    $signatureB64 = dowaba_base64url_encode($signature);

    return $payloadB64.'.'.$signatureB64;
}

function dowaba_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function dowaba_base64url_decode(string $data): string
{
    $padded = $data.str_repeat('=', (4 - strlen($data) % 4) % 4);
    $decoded = base64_decode(strtr($padded, '-_', '+/'), true);

    if ($decoded === false) {
        throw new RuntimeException('Base64url decode başarısız');
    }

    return $decoded;
}
