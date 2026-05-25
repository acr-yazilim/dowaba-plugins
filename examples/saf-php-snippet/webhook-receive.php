<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dowaba — Webhook receiver (saf-PHP snippet)
|--------------------------------------------------------------------------
|
| Dowaba bir kanal eventi tetiklediğinde (yeni mesaj, sipariş durumu, vb.)
| bu endpoint'e POST atar. HMAC-SHA256 imzası `X-Dowaba-Signature` header'ında.
|
| Yapılandırma:
|   1. Bu dosyayı kendi sitenizde public URL'le serve edin
|      (örn. https://siz.com/dowaba-webhook.php)
|   2. Dowaba admin → site → Webhooks → URL: yukarıdaki URL + Secret üretin
|   3. config.php'de DOWABA_WEBHOOK_SECRET'a yapıştırın
|
| Test:
|   curl -X POST https://siz.com/dowaba-webhook.php \
|        -H "Content-Type: application/json" \
|        -H "X-Dowaba-Signature: sha256=$(echo -n '{}' | openssl dgst -sha256 -hmac SECRET)" \
|        -d '{}'
*/

require __DIR__.'/lib/dowaba.php';
require __DIR__.'/config.php';

$rawBody = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_DOWABA_SIGNATURE'] ?? '';

if (! dowaba_verify_webhook($rawBody, $signature, DOWABA_WEBHOOK_SECRET)) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'invalid_signature']);
    exit;
}

$payload = json_decode($rawBody, true);

if (! is_array($payload) || ! isset($payload['event'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'invalid_payload']);
    exit;
}

// === KENDİ İŞ MANTIĞIN BURAYA ===
// Örnek event tipleri (gerçek liste Dowaba dökümantasyonunda):
//   conversation.created — yeni konuşma
//   message.received     — gelen mesaj
//   message.sent         — gönderilen mesaj
//   contact.created      — yeni kişi
//   order.created        — yeni sipariş (e-ticaret entegrasyonu)

switch ($payload['event']) {
    case 'message.received':
        $contactPhone = $payload['data']['contact']['phone'] ?? '';
        $messageText = $payload['data']['message']['text'] ?? '';
        // Örnek: kendi CRM'inize kaydet
        file_put_contents(
            __DIR__.'/webhook.log',
            date('c')." [message.received] {$contactPhone}: ".substr($messageText, 0, 80)."\n",
            FILE_APPEND
        );
        break;

    case 'conversation.created':
        // Örnek: bir uyarı yolla, ekibe e-posta at, vb.
        file_put_contents(
            __DIR__.'/webhook.log',
            date('c')." [conversation.created] id=".($payload['data']['id'] ?? '?')."\n",
            FILE_APPEND
        );
        break;

    default:
        // Bilinmeyen event — log + 200 dön (Dowaba retry yapmasın)
        file_put_contents(
            __DIR__.'/webhook.log',
            date('c')." [{$payload['event']}] (handler yok)\n",
            FILE_APPEND
        );
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
