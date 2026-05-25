<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dowaba — WhatsApp mesaj gönder (saf-PHP snippet, SADECE CLI)
|--------------------------------------------------------------------------
|
| Kullanım (yalnızca komut satırı):
|   CLI:   php send-whatsapp.php "+905551234567" "Merhaba"
|   Cron:  her 15dk → php /path/send-whatsapp.php "+905..." "Hatırlatma"
|
| ⚠️ GÜVENLİK: Bu dosya HTTP üzerinden ÇAĞRILAMAZ. Public web kökünden
|    çağrılması durumunda 403 döner. Sebebi: bu script Dowaba'ya yetkili
|    Bearer token ile istek atar; internet üzerinden açık bırakılırsa
|    herkes spam WhatsApp gönderebilir, kotanız tükenir, Meta hesabınız
|    banlanır.
|
|    Web request'ten WhatsApp tetiklemek için kendi backend'inizde auth +
|    rate limit + CSRF korumalı bir endpoint yazın; o endpoint bu fonksiyonu
|    `dowaba_request()` çağrısıyla kullansın (lib/dowaba.php'yi require et).
|
| Önce config.example.php → config.php olarak kopyala ve değerleri doldur.
*/

// CLI dışı (web request) reddet — defansif security guard
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Bu script yalnızca komut satırından (CLI) çalışır.\n";
    echo "HTTP üzerinden tetiklemek güvenlik açığıdır; kendi auth'lu endpoint'inizi yazın.\n";
    exit(1);
}

require __DIR__.'/lib/dowaba.php';

if (! file_exists(__DIR__.'/config.php')) {
    fwrite(STDERR, "HATA: config.php yok. config.example.php → config.php olarak kopyalayın ve değerleri doldurun.\n");
    exit(1);
}

require __DIR__.'/config.php';

$phone = $argv[1] ?? null;
$message = $argv[2] ?? null;

if (! $phone || ! $message) {
    fwrite(STDERR, "Kullanım: php send-whatsapp.php <phone> <message>\n");
    exit(1);
}

try {
    $response = dowaba_request('POST', '/api/wa/template', [
        'phone' => $phone,
        'template' => 'simple_message',
        'params' => ['message' => $message],
        'site_id' => DOWABA_SITE_ID,
    ]);

    $output = [
        'sent' => $response['status'] >= 200 && $response['status'] < 300,
        'status' => $response['status'],
        'response' => $response['data'],
    ];

    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
    exit($output['sent'] ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, json_encode(['error' => $e->getMessage()])."\n");
    exit(2);
}
