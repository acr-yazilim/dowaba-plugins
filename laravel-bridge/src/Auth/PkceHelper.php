<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Auth;

use Dowaba\LaravelBridge\Support\DowabaException;

/**
 * PKCE (Proof Key for Code Exchange — RFC 7636) helper.
 *
 * - verifier: 43-128 karakter, [A-Z][a-z][0-9]-._~
 * - challenge: base64url(SHA-256(verifier))
 *
 * Dowaba OAuth /oauth/authorize endpoint'i `code_challenge` + `code_challenge_method=S256`
 * parametrelerini bekler. `/api/oauth/token` çağrısında orijinal `code_verifier` gönderilir.
 */
class PkceHelper
{
    private const VERIFIER_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';

    public function generateVerifier(int $length = 64): string
    {
        if ($length < 43 || $length > 128) {
            throw new DowabaException("PKCE verifier uzunluğu 43-128 arası olmalı, verilen: {$length}");
        }

        $alphabetSize = strlen(self::VERIFIER_CHARS);
        $verifier = '';

        for ($i = 0; $i < $length; $i++) {
            $verifier .= self::VERIFIER_CHARS[random_int(0, $alphabetSize - 1)];
        }

        return $verifier;
    }

    public function generateChallenge(string $verifier): string
    {
        return $this->base64UrlEncode(hash('sha256', $verifier, true));
    }

    public function generatePair(int $length = 64): array
    {
        $verifier = $this->generateVerifier($length);

        return [
            'verifier' => $verifier,
            'challenge' => $this->generateChallenge($verifier),
            'method' => 'S256',
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
