<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Auth;

use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Dowaba\LaravelBridge\Support\AuthenticationException;
use Dowaba\LaravelBridge\Support\DowabaException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Dowaba OAuth 2.0 / OIDC authorization code flow (PKCE zorunlu).
 *
 * Akış:
 *   1. authorizationUrl() → state + PKCE üret, session'a sakla, /oauth/authorize URL'i döner
 *   2. Kullanıcı dowaba.com'a yönlendirilir, consent verir, ?code=... ?state=... ile geri döner
 *   3. exchangeCode($code, $state, $verifier) → /api/oauth/token POST + JWKS verify + DB persist
 *   4. ileride refreshAccess() veya revoke()
 */
class OauthFlow
{
    private const SESSION_STATE_KEY = 'dowaba.oauth.state';
    private const SESSION_VERIFIER_KEY = 'dowaba.oauth.verifier';
    private const SESSION_LOCAL_USER_KEY = 'dowaba.oauth.local_user_id';

    public function __construct(
        protected PkceHelper $pkce,
        protected TokenStore $tokens,
        protected ?Client $http = null,
    ) {
        $this->http ??= new Client([
            'base_uri' => rtrim(config('dowaba.url'), '/').'/',
            'timeout' => (int) config('dowaba.http.timeout', 10),
            'connect_timeout' => (int) config('dowaba.http.connect_timeout', 3),
            'http_errors' => false,
        ]);
    }

    /**
     * /oauth/authorize URL'i üret + state ve verifier'ı session'a sakla.
     *
     * @return array{url:string, state:string, verifier:string}
     */
    public function authorizationUrl(?int $localUserId = null): array
    {
        $pair = $this->pkce->generatePair();
        $state = Str::random(40);

        Session::put(self::SESSION_STATE_KEY, $state);
        Session::put(self::SESSION_VERIFIER_KEY, $pair['verifier']);
        Session::put(self::SESSION_LOCAL_USER_KEY, $localUserId);

        $url = rtrim(config('dowaba.url'), '/').'/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('dowaba.client_id'),
            'redirect_uri' => config('dowaba.redirect_uri'),
            'scope' => config('dowaba.scopes'),
            'state' => $state,
            'code_challenge' => $pair['challenge'],
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url' => $url,
            'state' => $state,
            'verifier' => $pair['verifier'],
        ];
    }

    /**
     * Callback'ten gelen kodu erişim token'ına dönüştür + id_token doğrula + DB'ye kaydet.
     */
    public function exchangeCode(string $code, string $providedState): DowabaUser
    {
        $sessionState = Session::pull(self::SESSION_STATE_KEY);
        $verifier = Session::pull(self::SESSION_VERIFIER_KEY);
        $localUserId = Session::pull(self::SESSION_LOCAL_USER_KEY);

        if (! $sessionState || ! hash_equals($sessionState, $providedState)) {
            throw (new AuthenticationException('OAuth state eşleşmiyor (CSRF/replay koruması)'))
                ->withCode('state_mismatch');
        }

        if (! $verifier) {
            throw (new AuthenticationException('PKCE verifier session\'da bulunamadı'))
                ->withCode('verifier_missing');
        }

        $response = $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('dowaba.redirect_uri'),
            'client_id' => config('dowaba.client_id'),
            'client_secret' => config('dowaba.client_secret'),
            'code_verifier' => $verifier,
        ]);

        $claims = isset($response['id_token']) ? $this->verifyIdToken($response['id_token']) : [];
        $scopes = $this->parseScopes($response['scope'] ?? config('dowaba.scopes'));

        $expiresIn = isset($response['expires_in']) ? (int) $response['expires_in'] : null;
        $expiresAt = $expiresIn !== null ? Carbon::now()->addSeconds($expiresIn) : null;

        $this->tokens->save(
            localUserId: $localUserId,
            dowabaUserId: (string) ($claims['sub'] ?? ''),
            accessToken: (string) $response['access_token'],
            refreshToken: $response['refresh_token'] ?? null,
            idToken: $response['id_token'] ?? null,
            expiresIn: $expiresIn,
            scopes: $scopes,
            claims: $claims,
            email: $claims['email'] ?? null,
        );

        return DowabaUser::fromClaims($claims, $scopes, $expiresAt);
    }

    public function refreshAccess(DowabaOauthToken $token): DowabaOauthToken
    {
        if (! $token->refresh_token) {
            throw (new AuthenticationException('Refresh token mevcut değil'))->withCode('no_refresh_token');
        }

        $response = $this->postToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id' => config('dowaba.client_id'),
            'client_secret' => config('dowaba.client_secret'),
        ]);

        return $this->tokens->updateAccessToken(
            $token,
            (string) $response['access_token'],
            $response['refresh_token'] ?? null,
            isset($response['expires_in']) ? (int) $response['expires_in'] : null,
        );
    }

    public function revoke(DowabaOauthToken $token): void
    {
        try {
            $this->http->post('api/oauth/revoke', [
                'form_params' => [
                    'token' => $token->access_token,
                    'token_type_hint' => 'access_token',
                    'client_id' => config('dowaba.client_id'),
                    'client_secret' => config('dowaba.client_secret'),
                ],
            ]);
        } catch (GuzzleException) {
            // network hatasında bile DB'den temizle
        }

        $token->delete();
    }

    public function verifyIdToken(string $idToken): array
    {
        $keys = $this->fetchJwks();

        try {
            $decoded = JWT::decode($idToken, JWK::parseKeySet($keys));
        } catch (\Throwable $e) {
            throw (new AuthenticationException('id_token doğrulanamadı: '.$e->getMessage()))
                ->withCode('id_token_invalid');
        }

        return (array) $decoded;
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    public function fetchJwks(bool $forceRefresh = false): array
    {
        $cacheKey = 'dowaba.bridge.jwks';
        $ttl = (int) config('dowaba.jwks_cache_ttl', 3600);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $ttl, function () {
            try {
                $res = $this->http->get('.well-known/jwks.json');
            } catch (GuzzleException $e) {
                throw (new AuthenticationException('JWKS endpoint reachable değil: '.$e->getMessage()))
                    ->withCode('jwks_unreachable');
            }

            if ($res->getStatusCode() !== 200) {
                throw (new AuthenticationException('JWKS endpoint status: '.$res->getStatusCode()))
                    ->withCode('jwks_http_error');
            }

            $body = (string) $res->getBody();
            $data = json_decode($body, true);

            if (! is_array($data) || ! isset($data['keys']) || ! is_array($data['keys'])) {
                throw (new AuthenticationException('JWKS response invalid (keys alanı yok)'))
                    ->withCode('jwks_malformed');
            }

            return $data;
        });
    }

    private function postToken(array $params): array
    {
        try {
            $res = $this->http->post('api/oauth/token', ['form_params' => $params]);
        } catch (GuzzleException $e) {
            throw (new AuthenticationException('Token endpoint çağrısı başarısız: '.$e->getMessage()))
                ->withCode('token_request_failed');
        }

        $body = (string) $res->getBody();
        $data = json_decode($body, true);

        if ($res->getStatusCode() !== 200 || ! is_array($data) || ! isset($data['access_token'])) {
            throw (new AuthenticationException(
                'Token exchange başarısız (HTTP '.$res->getStatusCode().'): '.substr($body, 0, 200)
            ))->withCode('token_exchange_failed')->withContext(['http_status' => $res->getStatusCode()]);
        }

        return $data;
    }

    private function parseScopes(mixed $scopes): array
    {
        if (is_array($scopes)) {
            return $scopes;
        }

        if (is_string($scopes)) {
            return array_values(array_filter(preg_split('/\s+/', $scopes) ?: []));
        }

        return [];
    }
}
