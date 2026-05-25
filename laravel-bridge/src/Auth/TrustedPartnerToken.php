<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Auth;

use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Dowaba\LaravelBridge\Support\AuthenticationException;
use Illuminate\Support\Facades\Cache;

/**
 * Trusted Partner Sanctum PAT istemci-side cache + auto-refresh helper.
 *
 * DowabaClient her HTTP çağrısından önce `current(...)` ile geçerli token alır.
 * 401 dönerse `tryRefresh(...)` çağrılır; başarılı olursa 1 retry.
 *
 * 60 saniye safety margin: token expire'a yakınsa proaktif refresh.
 *
 * Cache-lock 5 sn: paralel istek aynı anda refresh denerse race condition önlenir.
 */
class TrustedPartnerToken
{
    public function __construct(
        protected TokenStore $tokens,
        protected OauthFlow $oauth,
    ) {}

    public function current(?int $localUserId, int $safetyMarginSeconds = 60): ?string
    {
        $record = $this->tokens->getForUser($localUserId);

        if (! $record) {
            return null;
        }

        if ($record->isExpired($safetyMarginSeconds)) {
            $record = $this->tryRefresh($record);

            if (! $record) {
                return null;
            }
        }

        return $record->access_token;
    }

    public function tryRefresh(DowabaOauthToken $record): ?DowabaOauthToken
    {
        if (! $record->refresh_token) {
            return null;
        }

        $lockKey = 'dowaba.bridge.refresh.'.$record->id;

        return Cache::lock($lockKey, 5)->block(5, function () use ($record) {
            $record->refresh();

            if (! $record->isExpired(60)) {
                return $record;
            }

            try {
                return $this->oauth->refreshAccess($record);
            } catch (AuthenticationException) {
                return null;
            }
        });
    }

    public function revoke(?int $localUserId): void
    {
        $record = $this->tokens->getForUser($localUserId);

        if ($record) {
            $this->oauth->revoke($record);
        }
    }
}
