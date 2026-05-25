<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Auth\TokenStore;
use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = new TokenStore;
});

it('saves a new token record', function () {
    $token = $this->store->save(
        localUserId: 1,
        dowabaUserId: 'usr_abc123',
        accessToken: 'doat_test_access',
        refreshToken: 'dort_test_refresh',
        idToken: 'eyJraWQiOiJ0ZXN0In0.eyJzdWIiOiJ1c3JfYWJjMTIzIn0.signature',
        expiresIn: 3600,
        scopes: ['openid', 'profile', 'email'],
        claims: ['sub' => 'usr_abc123', 'email' => 'test@example.com'],
        email: 'test@example.com',
    );

    expect($token)->toBeInstanceOf(DowabaOauthToken::class);
    expect($token->dowaba_user_id)->toBe('usr_abc123');
    expect($token->access_token)->toBe('doat_test_access');
    expect($token->scopes)->toBe(['openid', 'profile', 'email']);
});

it('encrypts access_token and refresh_token at rest', function () {
    $this->store->save(
        localUserId: 1,
        dowabaUserId: 'usr_xyz',
        accessToken: 'doat_secret_value',
        refreshToken: 'dort_secret_refresh',
    );

    $rawRow = DB::table('dowaba_oauth_tokens')->where('dowaba_user_id', 'usr_xyz')->first();

    expect($rawRow->access_token)->not->toBe('doat_secret_value');
    expect($rawRow->refresh_token)->not->toBe('dort_secret_refresh');

    $hydrated = DowabaOauthToken::where('dowaba_user_id', 'usr_xyz')->first();
    expect($hydrated->access_token)->toBe('doat_secret_value');
    expect($hydrated->refresh_token)->toBe('dort_secret_refresh');
});

it('updateOrCreates on existing local_user_id + dowaba_user_id combo', function () {
    $first = $this->store->save(localUserId: 5, dowabaUserId: 'usr_a', accessToken: 'first_token');
    $second = $this->store->save(localUserId: 5, dowabaUserId: 'usr_a', accessToken: 'second_token');

    expect($first->id)->toBe($second->id);
    expect(DowabaOauthToken::count())->toBe(1);
    expect($second->access_token)->toBe('second_token');
});

it('getForUser returns latest token for user', function () {
    $this->store->save(localUserId: 1, dowabaUserId: 'usr_a', accessToken: 'a');
    $this->store->save(localUserId: 1, dowabaUserId: 'usr_b', accessToken: 'b');

    $token = $this->store->getForUser(1);

    expect($token)->not->toBeNull();
    expect($token->access_token)->toBe('b');
});

it('getForUser returns null when no token saved', function () {
    expect($this->store->getForUser(999))->toBeNull();
});

it('updateAccessToken keeps refresh_token when null passed', function () {
    $token = $this->store->save(localUserId: 1, dowabaUserId: 'usr_x', accessToken: 'old', refreshToken: 'r_old');

    $updated = $this->store->updateAccessToken($token, 'new_access', null, 7200);

    expect($updated->access_token)->toBe('new_access');
    expect($updated->refresh_token)->toBe('r_old');
    expect($updated->expires_at->isFuture())->toBeTrue();
});

it('deleteForUser removes all records for that user', function () {
    $this->store->save(localUserId: 1, dowabaUserId: 'usr_a', accessToken: 'a');
    $this->store->save(localUserId: 1, dowabaUserId: 'usr_b', accessToken: 'b');
    $this->store->save(localUserId: 2, dowabaUserId: 'usr_c', accessToken: 'c');

    $deleted = $this->store->deleteForUser(1);

    expect($deleted)->toBe(2);
    expect(DowabaOauthToken::count())->toBe(1);
    expect($this->store->getForUser(2)->access_token)->toBe('c');
});
