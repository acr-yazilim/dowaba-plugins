<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dowaba — WhatsApp mesaj gönder (saf-PHP snippet)
|--------------------------------------------------------------------------
|
| Kullanım:
|   CLI:   php send-whatsapp.php "+905551234567" "Merhaba"
|   HTTP:  POST /send-whatsapp.php?phone=+905551234567&message=Merhaba
|   Cron:  her 15dk → php /path/send-whatsapp.php "+905..." "Hatırlatma"
|
| Önce config.example.php → config.php olarak kopyala ve değerleri doldur.
*/

require __DIR__.'/lib/dowaba.php';

if (! file_exists(__DIR__.'/config.php')) {
    fwrite(STDERR, "HATA: config.php yok. config.example.php → config.php olarak kopyalayın ve değerleri doldurun.\n");
    exit(1);
}

require __DIR__.'/config.php';

// CLI veya HTTP'den parametre al
[$phone, $message] = (PHP_SAPI === 'cli')
    ? [($argv[1] ?? null), ($argv[2] ?? null)]
    : [($_REQUEST['phone'] ?? null), ($_REQUEST['message'] ?? null)];

if (! $phone || ! $message) {
    $usage = "Kullanım: php send-whatsapp.php <phone> <message>\n";
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $usage);
    } else {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo $usage;
    }
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

    if (PHP_SAPI === 'cli') {
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
        exit($output['sent'] ? 0 : 2);
    }

    header('Content-Type: application/json');
    echo json_encode($output, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $err = ['error' => $e->getMessage()];

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, json_encode($err)."\n");
        exit(2);
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($err);
}
