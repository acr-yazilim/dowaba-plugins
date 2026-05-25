<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Auth;

use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Illuminate\Support\Carbon;

/**
 * OAuth access + refresh token DB persistence katmanı.
 *
 * `config('dowaba.token_store')` = 'database' (default) için bu sınıf kullanılır.
 * Tablo: dowaba_oauth_tokens (publish edilen migration). access_token, refresh_token
 * ve id_token Laravel'in built-in `encrypted` cast'i ile saklanır.
 *
 * Anahtarlama:
 *   - local_user_id != null  → yerel auth kullanıcısına bağlı oturum
 *   - local_user_id == null  → guest/client_credentials akışı (örn. cron job)
 */
class TokenStore
{
    public function save(
        ?int $localUserId,
        string $dowabaUserId,
        string $accessToken,
        ?string $refreshToken = null,
        ?string $idToken = null,
        ?int $expiresIn = null,
        array $scopes = [],
        array $claims = [],
        ?string $email = null,
    ): DowabaOauthToken {
        $expiresAt = $expiresIn !== null ? Carbon::now()->addSeconds($expiresIn) : null;

        return DowabaOauthToken::updateOrCreate(
            [
                'local_user_id' => $localUserId,
                'dowaba_user_id' => $dowabaUserId,
            ],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'id_token' => $idToken,
                'expires_at' => $expiresAt,
                'scopes' => $scopes,
                'claims' => $claims,
                'email' => $email,
            ]
        );
    }

    public function getForUser(?int $localUserId): ?DowabaOauthToken
    {
        return DowabaOauthToken::query()
            ->where('local_user_id', $localUserId)
            ->latest('id')
            ->first();
    }

    public function getByDowabaUserId(string $dowabaUserId): ?DowabaOauthToken
    {
        return DowabaOauthToken::query()
            ->where('dowaba_user_id', $dowabaUserId)
            ->latest('id')
            ->first();
    }

    public function updateAccessToken(
        DowabaOauthToken $token,
        string $newAccessToken,
        ?string $newRefreshToken = null,
        ?int $expiresIn = null,
    ): DowabaOauthToken {
        $token->access_token = $newAccessToken;

        if ($newRefreshToken !== null) {
            $token->refresh_token = $newRefreshToken;
        }

        if ($expiresIn !== null) {
            $token->expires_at = Carbon::now()->addSeconds($expiresIn);
        }

        $token->save();

        return $token;
    }

    public function deleteForUser(?int $localUserId): int
    {
        return DowabaOauthToken::query()
            ->where('local_user_id', $localUserId)
            ->delete();
    }

    public function deleteByDowabaUserId(string $dowabaUserId): int
    {
        return DowabaOauthToken::query()
            ->where('dowaba_user_id', $dowabaUserId)
            ->delete();
    }
}
