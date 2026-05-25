<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Saf-PHP Snippet — Smoke Test
|--------------------------------------------------------------------------
|
| Kurulum sonrası 6 kontrol — her biri exception veya assert false atarsa exit 1.
|   1. lib/dowaba.php yüklenir + 3 fonksiyon tanımlı
|   2. config.example.php yüklenir + tüm define'lar var
|   3. send-whatsapp.php syntax temiz (php -l)
|   4. webhook-receive.php syntax temiz
|   5. dowaba_widget_token roundtrip (üret → decode → payload eşleşir)
|   6. dowaba_verify_webhook (valid + tampered + wrong-secret)
|
| Kullanım: php tests/smoke-test.php
*/

$baseDir = dirname(__DIR__);
$failures = [];
$passes = 0;

function check(string $name, callable $fn, array &$failures, int &$passes): void
{
    try {
        $result = $fn();
        if ($result === false) {
            $failures[] = "✗ {$name}: assertion false";
        } else {
            echo "✓ {$name}\n";
            $passes++;
        }
    } catch (Throwable $e) {
        $failures[] = "✗ {$name}: ".$e->getMessage();
        echo "✗ {$name}: ".$e->getMessage()."\n";
    }
}

// --- Test 1: lib/dowaba.php yüklenir + 3 fonksiyon tanımlı ---
check('lib/dowaba.php yüklenir + helper fonksiyonlar tanımlı', function () use ($baseDir) {
    require_once $baseDir.'/lib/dowaba.php';

    if (! function_exists('dowaba_request')) {
        throw new RuntimeException('dowaba_request() yok');
    }
    if (! function_exists('dowaba_verify_webhook')) {
        throw new RuntimeException('dowaba_verify_webhook() yok');
    }
    if (! function_exists('dowaba_widget_token')) {
        throw new RuntimeException('dowaba_widget_token() yok');
    }

    return true;
}, $failures, $passes);

// --- Test 2: config.example.php yüklenir + define'lar var ---
check('config.example.php yapısı doğru', function () use ($baseDir) {
    require_once $baseDir.'/config.example.php';

    $required = ['DOWABA_URL', 'DOWABA_ACCESS_TOKEN', 'DOWABA_WEBHOOK_SECRET',
                 'DOWABA_WIDGET_SITE_ID', 'DOWABA_WIDGET_SECRET', 'DOWABA_SITE_ID'];

    foreach ($required as $constant) {
        if (! defined($constant)) {
            throw new RuntimeException("define('{$constant}', ...) eksik");
        }
    }

    return true;
}, $failures, $passes);

// --- Test 3: send-whatsapp.php syntax temiz ---
check('send-whatsapp.php syntax-clean', function () use ($baseDir) {
    $output = shell_exec("php -l {$baseDir}/send-whatsapp.php 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        throw new RuntimeException("Syntax error: {$output}");
    }
    return true;
}, $failures, $passes);

// --- Test 4: webhook-receive.php syntax temiz ---
check('webhook-receive.php syntax-clean', function () use ($baseDir) {
    $output = shell_exec("php -l {$baseDir}/webhook-receive.php 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        throw new RuntimeException("Syntax error: {$output}");
    }
    return true;
}, $failures, $passes);

// --- Test 5: dowaba_widget_token roundtrip ---
check('dowaba_widget_token roundtrip', function () {
    $secret = 'test-widget-secret-long-enough-for-hmac';
    $original = [
        'user_id' => '42',
        'email' => 'ali@example.com',
        'site_id' => 7,
    ];

    $token = dowaba_widget_token($original, $secret, ttl: 300);

    if (substr_count($token, '.') !== 1) {
        throw new RuntimeException("Token formatı geçersiz (dot count != 1): {$token}");
    }

    [$payloadB64, $signatureB64] = explode('.', $token);

    $expectedSig = hash_hmac('sha256', $payloadB64, $secret, true);
    $actualSig = dowaba_base64url_decode($signatureB64);

    if (! hash_equals($expectedSig, $actualSig)) {
        throw new RuntimeException('HMAC imza eşleşmiyor');
    }

    $payloadJson = dowaba_base64url_decode($payloadB64);
    $payload = json_decode($payloadJson, true);

    if ($payload['user_id'] !== '42' || $payload['email'] !== 'ali@example.com' || $payload['site_id'] !== 7) {
        throw new RuntimeException('Decoded payload orijinalle eşleşmiyor');
    }
    if (! isset($payload['exp']) || $payload['exp'] <= time()) {
        throw new RuntimeException('exp claim eksik veya geçmiş');
    }
    if (! isset($payload['nonce']) || strlen($payload['nonce']) < 8) {
        throw new RuntimeException('nonce çok kısa');
    }

    return true;
}, $failures, $passes);

// --- Test 6: dowaba_verify_webhook (valid + tampered + wrong-secret + missing header) ---
check('dowaba_verify_webhook 4 senaryo (valid/tampered/wrong-secret/missing)', function () {
    $secret = 'test-webhook-secret-long-enough';
    $body = '{"event":"message.received","data":{"id":42}}';
    $validSig = 'sha256='.hash_hmac('sha256', $body, $secret);

    if (! dowaba_verify_webhook($body, $validSig, $secret)) {
        throw new RuntimeException('Valid signature reddedildi');
    }

    if (dowaba_verify_webhook($body.'TAMPERED', $validSig, $secret)) {
        throw new RuntimeException('Tampered body kabul edildi');
    }

    if (dowaba_verify_webhook($body, $validSig, 'wrong-secret')) {
        throw new RuntimeException('Yanlış secret ile valid göründü');
    }

    if (dowaba_verify_webhook($body, '', $secret)) {
        throw new RuntimeException('Boş header kabul edildi');
    }

    if (dowaba_verify_webhook($body, 'md5=abc', $secret)) {
        throw new RuntimeException('Yanlış algoritma kabul edildi');
    }

    return true;
}, $failures, $passes);

// --- Sonuç raporu ---
echo "\n=== ÖZET ===\n";
echo "Geçen: {$passes}\n";
echo 'Başarısız: '.count($failures)."\n";

if (! empty($failures)) {
    echo "\nBaşarısız test'ler:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}

echo "\nTüm smoke test'ler başarılı.\n";
exit(0);
