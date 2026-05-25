<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Models\DowabaOauthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['web', 'dowaba.connected'])
        ->get('/_test-protected', fn () => response('PROTECTED_CONTENT', 200));
});

it('redirects to dowaba auth login when no token present', function () {
    $response = $this->get('/_test-protected');

    $response->assertRedirect(route('dowaba.auth.login'));
});

it('stores intended URL in session before redirecting', function () {
    $this->get('/_test-protected');

    expect(session('url.intended'))->toContain('/_test-protected');
});

it('allows the request through when a valid token is stored', function () {
    DowabaOauthToken::create([
        'local_user_id' => null,
        'dowaba_user_id' => 'usr_guest_session',
        'access_token' => 'doat_valid_token',
        'refresh_token' => 'dort_refresh',
        'expires_at' => now()->addHour(),
        'scopes' => ['openid', 'profile'],
    ]);

    $response = $this->get('/_test-protected');

    $response->assertOk();
    $response->assertSeeText('PROTECTED_CONTENT');
});

it('redirects when token is expired and no refresh_token', function () {
    DowabaOauthToken::create([
        'local_user_id' => null,
        'dowaba_user_id' => 'usr_expired',
        'access_token' => 'doat_old',
        'refresh_token' => null,
        'expires_at' => now()->subHour(),
        'scopes' => ['openid'],
    ]);

    $response = $this->get('/_test-protected');

    $response->assertRedirect(route('dowaba.auth.login'));
});
