<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Support;

/**
 * Widget user token üreteci.
 *
 * Format:
 *   payload   = base64url(JSON({user_id, email, site_id, exp, iat, nonce, ...extra}))
 *   signature = HMAC-SHA256(widget_secret, payload_b64)
 *   token     = payload_b64 . '.' . signature_b64
 *
 * Dowaba backend `/api/widget/auth` endpoint'i token'ı çözer, HMAC'i `hash_equals`
 * ile doğrular, exp/site_id kontrol eder, ardından Contact upsert + widget session
 * token (`wst_*`, 5 dk TTL Redis) döner.
 *
 * Widget JS init'te token alınır → sonraki tüm fetch'lere `Authorization: Widget {wst_*}`.
 */
class HmacSigner
{
    /**
     * Token üret.
     *
     * @param  array  $payload  Zorunlu: user_id, email, site_id. Opsiyonel: name, phone, extra alanlar.
     * @param  string  $secret  Site'a özel widget_secret (Dowaba admin → site detay).
     * @param  int  $ttl  Token geçerlilik süresi (saniye). Default 300 (5 dk).
     */
    public function sign(array $payload, string $secret, int $ttl = 300): string
    {
        $now = time();

        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttl,
            'nonce' => bin2hex(random_bytes(8)),
        ]);

        $payloadB64 = $this->base64UrlEncode(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $signature = hash_hmac('sha256', $payloadB64, $secret, true);
        $signatureB64 = $this->base64UrlEncode($signature);

        return $payloadB64.'.'.$signatureB64;
    }

    /**
     * Token doğrula + payload geri döndür. Hata olursa HmacException atar.
     *
     * @return array Decoded payload (user_id, email, site_id, exp, iat, nonce, ...)
     *
     * @throws HmacException
     */
    public function verify(string $token, string $secret): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            throw (new HmacException('Geçersiz token formatı (parça sayısı != 2)'))->withCode('malformed');
        }

        [$payloadB64, $signatureB64] = $parts;

        $expectedSignature = hash_hmac('sha256', $payloadB64, $secret, true);
        $providedSignature = $this->base64UrlDecode($signatureB64);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            throw (new HmacException('İmza eşleşmiyor'))->withCode('hmac_mismatch');
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);

        try {
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw (new HmacException('Payload JSON parse edilemedi: '.$e->getMessage()))->withCode('invalid_json');
        }

        if (! is_array($payload)) {
            throw (new HmacException('Payload bir array değil'))->withCode('invalid_payload');
        }

        if (! isset($payload['exp']) || ! is_int($payload['exp'])) {
            throw (new HmacException('Payload exp alanı eksik veya integer değil'))->withCode('missing_exp');
        }

        if ($payload['exp'] < time()) {
            throw (new HmacException('Token süresi dolmuş'))->withCode('expired')
                ->withContext(['exp' => $payload['exp'], 'now' => time()]);
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padded = $data.str_repeat('=', (4 - strlen($data) % 4) % 4);
        $decoded = base64_decode(strtr($padded, '-_', '+/'), true);

        if ($decoded === false) {
            throw (new HmacException('Base64url decode başarısız'))->withCode('invalid_base64');
        }

        return $decoded;
    }
}
