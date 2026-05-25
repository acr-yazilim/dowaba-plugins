<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Auth\PkceHelper;
use Dowaba\LaravelBridge\Support\DowabaException;

beforeEach(function () {
    $this->pkce = new PkceHelper;
});

it('generates verifier within RFC 7636 length bounds (43-128)', function () {
    $verifier = $this->pkce->generateVerifier();

    expect(strlen($verifier))->toBe(64);
    expect($verifier)->toMatch('/^[A-Za-z0-9\-._~]+$/');
});

it('respects custom length parameter', function () {
    $short = $this->pkce->generateVerifier(43);
    $long = $this->pkce->generateVerifier(128);

    expect(strlen($short))->toBe(43);
    expect(strlen($long))->toBe(128);
});

it('rejects verifier length outside 43-128 range', function () {
    expect(fn () => $this->pkce->generateVerifier(42))->toThrow(DowabaException::class);
    expect(fn () => $this->pkce->generateVerifier(129))->toThrow(DowabaException::class);
    expect(fn () => $this->pkce->generateVerifier(0))->toThrow(DowabaException::class);
});

it('generates different verifiers on consecutive calls (entropy)', function () {
    $v1 = $this->pkce->generateVerifier();
    $v2 = $this->pkce->generateVerifier();
    $v3 = $this->pkce->generateVerifier();

    expect($v1)->not->toBe($v2);
    expect($v2)->not->toBe($v3);
    expect($v1)->not->toBe($v3);
});

it('challenge matches S256 spec: base64url(SHA-256(verifier))', function () {
    $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
    $expectedChallenge = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    $challenge = $this->pkce->generateChallenge($verifier);

    expect($challenge)->toBe($expectedChallenge);
});

it('generates pair with verifier, challenge, and S256 method', function () {
    $pair = $this->pkce->generatePair();

    expect($pair)->toHaveKeys(['verifier', 'challenge', 'method']);
    expect($pair['method'])->toBe('S256');
    expect(strlen($pair['verifier']))->toBeGreaterThanOrEqual(43);

    $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $pair['verifier'], true)), '+/', '-_'), '=');
    expect($pair['challenge'])->toBe($expectedChallenge);
});
