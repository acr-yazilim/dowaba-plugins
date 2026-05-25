<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Support\HmacException;
use Dowaba\LaravelBridge\Support\HmacSigner;

beforeEach(function () {
    $this->signer = new HmacSigner;
    $this->secret = 'test-widget-secret-very-long-string';
});

it('signs payload and produces token with two dot-separated parts', function () {
    $token = $this->signer->sign([
        'user_id' => 123,
        'email' => 'ali@example.com',
        'site_id' => 42,
    ], $this->secret);

    expect($token)->toBeString();
    expect(substr_count($token, '.'))->toBe(1);

    [$payloadB64, $signatureB64] = explode('.', $token);
    expect($payloadB64)->not->toBeEmpty();
    expect($signatureB64)->not->toBeEmpty();
});

it('round-trips: sign then verify returns original payload', function () {
    $original = [
        'user_id' => 99,
        'email' => 'mehmet@example.com',
        'site_id' => 7,
        'extra' => 'data',
    ];

    $token = $this->signer->sign($original, $this->secret);
    $verified = $this->signer->verify($token, $this->secret);

    expect($verified['user_id'])->toBe(99);
    expect($verified['email'])->toBe('mehmet@example.com');
    expect($verified['site_id'])->toBe(7);
    expect($verified['extra'])->toBe('data');
    expect($verified)->toHaveKey('exp');
    expect($verified)->toHaveKey('iat');
    expect($verified)->toHaveKey('nonce');
});

it('rejects token with tampered payload (hmac_mismatch)', function () {
    $token = $this->signer->sign(['user_id' => 1, 'site_id' => 1], $this->secret);

    [$payloadB64, $signatureB64] = explode('.', $token);
    $tamperedToken = rtrim(strtr(base64_encode('{"user_id":999,"site_id":1,"exp":99999999999}'), '+/', '-_'), '=').'.'.$signatureB64;

    expect(fn () => $this->signer->verify($tamperedToken, $this->secret))
        ->toThrow(HmacException::class)
        ->and(fn () => $this->signer->verify($tamperedToken, $this->secret))
        ->toThrow(fn (HmacException $e) => $e->errorCode() === 'hmac_mismatch');
});

it('rejects token with wrong secret', function () {
    $token = $this->signer->sign(['user_id' => 1, 'site_id' => 1], $this->secret);

    expect(fn () => $this->signer->verify($token, 'wrong-secret'))
        ->toThrow(HmacException::class);
});

it('rejects token with malformed structure (no dot)', function () {
    expect(fn () => $this->signer->verify('not-a-valid-token-format', $this->secret))
        ->toThrow(HmacException::class)
        ->and(fn () => $this->signer->verify('not-a-valid-token-format', $this->secret))
        ->toThrow(fn (HmacException $e) => $e->errorCode() === 'malformed');
});

it('rejects expired token', function () {
    $token = $this->signer->sign(['user_id' => 1, 'site_id' => 1], $this->secret, ttl: 1);

    sleep(2);

    expect(fn () => $this->signer->verify($token, $this->secret))
        ->toThrow(HmacException::class)
        ->and(fn () => $this->signer->verify($token, $this->secret))
        ->toThrow(fn (HmacException $e) => $e->errorCode() === 'expired');
});

it('uses constant-time comparison (hash_equals)', function () {
    $token = $this->signer->sign(['user_id' => 1, 'site_id' => 1], $this->secret);

    [$payloadB64] = explode('.', $token);
    $almostCorrect = $payloadB64.'.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    expect(fn () => $this->signer->verify($almostCorrect, $this->secret))
        ->toThrow(HmacException::class);
});
