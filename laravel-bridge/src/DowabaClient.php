<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge;

use Dowaba\LaravelBridge\Auth\TrustedPartnerToken;
use Dowaba\LaravelBridge\Support\AuthenticationException;
use Dowaba\LaravelBridge\Support\HttpException;
use Dowaba\LaravelBridge\Support\ScopeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Psr\Http\Message\ResponseInterface;

/**
 * Dowaba API HTTP istemcisi.
 *
 * Resource sınıfları (WhatsApp, Conversations, Contacts vb.) bu sınıfı enjekte eder
 * ve `get/post/put/delete` ile çağırır. Token Auth otomatik (TrustedPartnerToken),
 * 401 → tek seferlik refresh + retry, 403/scope → ScopeException.
 *
 * Kullanım örnekleri:
 *   $client->get('/api/sites');
 *   $client->forUser($userId)->post('/api/wa/send', ['contact_id' => 1, 'message' => 'hi']);
 */
class DowabaClient
{
    protected ?int $localUserId = null;
    protected bool $userExplicit = false;

    public function __construct(
        protected TrustedPartnerToken $token,
        protected ?Client $http = null,
    ) {
        $this->http ??= new Client([
            'base_uri' => rtrim(config('dowaba.url'), '/').'/',
            'timeout' => (int) config('dowaba.http.timeout', 10),
            'connect_timeout' => (int) config('dowaba.http.connect_timeout', 3),
            'http_errors' => false,
        ]);
    }

    public function forUser(?int $localUserId): self
    {
        $clone = clone $this;
        $clone->localUserId = $localUserId;
        $clone->userExplicit = true;

        return $clone;
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, ['json' => $body]);
    }

    public function put(string $path, array $body = []): array
    {
        return $this->request('PUT', $path, ['json' => $body]);
    }

    public function patch(string $path, array $body = []): array
    {
        return $this->request('PATCH', $path, ['json' => $body]);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    public function request(string $method, string $path, array $options = []): array
    {
        $userId = $this->resolveUserId();
        $accessToken = $this->token->current($userId);

        if (! $accessToken) {
            throw (new AuthenticationException('Dowaba access token bulunamadı — `Dowaba ile Giriş` akışını tamamlayın.'))
                ->withCode('no_token');
        }

        $response = $this->sendWithBearer($method, $path, $options, $accessToken);

        if ($response->getStatusCode() === 401) {
            $record = $this->resolveTokenRecord($userId);
            $refreshed = $record ? $this->token->tryRefresh($record) : null;

            if ($refreshed) {
                $response = $this->sendWithBearer($method, $path, $options, $refreshed->access_token);
            }
        }

        return $this->decodeResponse($response, $method, $path);
    }

    private function sendWithBearer(string $method, string $path, array $options, string $bearer): ResponseInterface
    {
        $headers = $options['headers'] ?? [];
        $headers['Authorization'] = 'Bearer '.$bearer;
        $headers['Accept'] = 'application/json';
        $options['headers'] = $headers;

        try {
            return $this->http->request($method, ltrim($path, '/'), $options);
        } catch (GuzzleException $e) {
            throw (new HttpException("Dowaba {$method} {$path} network hatası: ".$e->getMessage()))
                ->withCode('network_error')
                ->withContext(['method' => $method, 'path' => $path]);
        }
    }

    private function decodeResponse(ResponseInterface $response, string $method, string $path): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = $body !== '' ? json_decode($body, true) : null;

        if ($status === 401) {
            throw (new AuthenticationException("Dowaba {$method} {$path} yetkilendirilmedi (401)"))
                ->withCode('unauthorized')->withContext(['response' => $data]);
        }

        if ($status === 403) {
            $errorCode = is_array($data) ? ($data['error'] ?? null) : null;
            $exception = $errorCode === 'insufficient_scope'
                ? new ScopeException("Dowaba {$method} {$path} için gereken scope yok")
                : new HttpException("Dowaba {$method} {$path} reddedildi (403)");

            throw $exception->withCode($errorCode ?? 'forbidden')->withContext(['response' => $data]);
        }

        if ($status >= 400) {
            throw (new HttpException("Dowaba {$method} {$path} HTTP {$status}: ".substr($body, 0, 200)))
                ->withCode('http_'.$status)
                ->withContext(['response' => $data, 'status' => $status]);
        }

        return is_array($data) ? $data : [];
    }

    private function resolveUserId(): ?int
    {
        if ($this->userExplicit) {
            return $this->localUserId;
        }

        return Auth::check() ? (int) Auth::id() : null;
    }

    private function resolveTokenRecord(?int $userId)
    {
        return app(\Dowaba\LaravelBridge\Auth\TokenStore::class)->getForUser($userId);
    }
}
