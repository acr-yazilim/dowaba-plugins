<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Auth\OauthFlow;
use Dowaba\LaravelBridge\Auth\PkceHelper;
use Dowaba\LaravelBridge\Auth\TokenStore;
use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Dowaba\LaravelBridge\Support\AuthenticationException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOauthFlowWithMockResponses(array $responses): OauthFlow
{
    $handler = HandlerStack::create(new MockHandler($responses));
    $http = new Client([
        'handler' => $handler,
        'base_uri' => 'https://dowaba.test/',
        'http_errors' => false,
    ]);

    return new OauthFlow(new PkceHelper, new TokenStore, $http);
}

it('authorizationUrl produces a valid Dowaba /oauth/authorize URL with PKCE params', function () {
    /** @var OauthFlow $oauth */
    $oauth = app(OauthFlow::class);

    $pair = $oauth->authorizationUrl(localUserId: 7);

    expect($pair)->toHaveKeys(['url', 'state', 'verifier']);
    expect($pair['url'])->toStartWith('https://dowaba.test/oauth/authorize?');

    parse_str(parse_url($pair['url'], PHP_URL_QUERY), $query);

    expect($query['response_type'])->toBe('code');
    expect($query['client_id'])->toBe('dosc_test_client');
    expect($query['code_challenge_method'])->toBe('S256');
    expect($query['state'])->toBe($pair['state']);
    expect(strlen($query['code_challenge']))->toBeGreaterThan(20);
    expect(session('dowaba.oauth.state'))->toBe($pair['state']);
    expect(session('dowaba.oauth.verifier'))->toBe($pair['verifier']);
    expect(session('dowaba.oauth.local_user_id'))->toBe(7);
});

it('exchangeCode rejects mismatched state (CSRF guard)', function () {
    /** @var OauthFlow $oauth */
    $oauth = app(OauthFlow::class);

    session()->put('dowaba.oauth.state', 'expected');
    session()->put('dowaba.oauth.verifier', 'verifier');

    expect(fn () => $oauth->exchangeCode('code', 'attacker-state'))
        ->toThrow(AuthenticationException::class)
        ->and(fn () => $oauth->exchangeCode('code', 'attacker-state'))
        ->toThrow(fn (AuthenticationException $e) => $e->errorCode() === 'state_mismatch');
});

it('exchangeCode rejects when verifier session missing', function () {
    /** @var OauthFlow $oauth */
    $oauth = app(OauthFlow::class);

    session()->put('dowaba.oauth.state', 'matching-state');
    // verifier put edilmiyor — session expired senaryosu

    expect(fn () => $oauth->exchangeCode('code', 'matching-state'))
        ->toThrow(AuthenticationException::class)
        ->and(fn () => $oauth->exchangeCode('code', 'matching-state'))
        ->toThrow(fn (AuthenticationException $e) => $e->errorCode() === 'verifier_missing');
});

it('refreshAccess rotates access_token and keeps refresh when not returned', function () {
    $flow = makeOauthFlowWithMockResponses([
        new Response(200, [], json_encode([
            'access_token' => 'doat_new',
            'expires_in' => 7200,
        ])),
    ]);

    $token = DowabaOauthToken::create([
        'local_user_id' => 1,
        'dowaba_user_id' => 'usr_a',
        'access_token' => 'doat_old',
        'refresh_token' => 'dort_keep',
        'expires_at' => now()->subMinute(),
        'scopes' => ['openid'],
    ]);

    $refreshed = $flow->refreshAccess($token);

    expect($refreshed->access_token)->toBe('doat_new');
    expect($refreshed->refresh_token)->toBe('dort_keep');
    expect($refreshed->expires_at->isFuture())->toBeTrue();
});

it('refreshAccess rotates refresh_token when Dowaba returns a new one', function () {
    $flow = makeOauthFlowWithMockResponses([
        new Response(200, [], json_encode([
            'access_token' => 'doat_new',
            'refresh_token' => 'dort_rotated',
            'expires_in' => 3600,
        ])),
    ]);

    $token = DowabaOauthToken::create([
        'local_user_id' => 1,
        'dowaba_user_id' => 'usr_b',
        'access_token' => 'doat_old',
        'refresh_token' => 'dort_old',
        'expires_at' => now()->subMinute(),
        'scopes' => ['openid'],
    ]);

    $refreshed = $flow->refreshAccess($token);

    expect($refreshed->access_token)->toBe('doat_new');
    expect($refreshed->refresh_token)->toBe('dort_rotated');
});

it('refreshAccess throws when no refresh_token stored', function () {
    $flow = makeOauthFlowWithMockResponses([]);

    $token = DowabaOauthToken::create([
        'local_user_id' => 1,
        'dowaba_user_id' => 'usr_c',
        'access_token' => 'doat_only',
        'refresh_token' => null,
        'expires_at' => now()->addHour(),
        'scopes' => ['openid'],
    ]);

    expect(fn () => $flow->refreshAccess($token))
        ->toThrow(AuthenticationException::class)
        ->and(fn () => $flow->refreshAccess($token))
        ->toThrow(fn (AuthenticationException $e) => $e->errorCode() === 'no_refresh_token');
});

it('refreshAccess throws when Dowaba returns 401 invalid_grant', function () {
    $flow = makeOauthFlowWithMockResponses([
        new Response(401, [], json_encode(['error' => 'invalid_grant'])),
    ]);

    $token = DowabaOauthToken::create([
        'local_user_id' => 1,
        'dowaba_user_id' => 'usr_d',
        'access_token' => 'doat_old',
        'refresh_token' => 'dort_invalid',
        'expires_at' => now()->subMinute(),
        'scopes' => ['openid'],
    ]);

    try {
        $flow->refreshAccess($token);
        $this->fail('Expected AuthenticationException');
    } catch (AuthenticationException $e) {
        expect($e->errorCode())->toBe('token_exchange_failed');
        expect($e->context())->toHaveKey('http_status');
    }
});
