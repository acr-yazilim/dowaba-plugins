<?php

/*
|--------------------------------------------------------------------------
| Dowaba Saf-PHP Snippet — Config
|--------------------------------------------------------------------------
|
| 1. Bu dosyayı `config.php` olarak kopyala (gitignore'da)
| 2. Aşağıdaki define'ları gerçek değerlerle doldur
| 3. Her snippet'in başında `require __DIR__.'/config.php';`
*/

// Dowaba SaaS endpoint (production: https://dowaba.com, white-label: kendi domain)
define('DOWABA_URL', 'https://dowaba.com');

// OAuth access token (Bearer). Dowaba admin → /admin/oauth/clients → token endpoint
// veya kullanıcı oturumundan al. CLI/cron için Trusted Partner Sanctum PAT kullan.
define('DOWABA_ACCESS_TOKEN', 'doat_REPLACE_ME');

// Webhook signature secret (Dowaba admin → site → webhooks → secret)
define('DOWABA_WEBHOOK_SECRET', 'REPLACE_ME_WEBHOOK_SECRET');

// Widget site ID + secret (Dowaba admin → site detayı)
define('DOWABA_WIDGET_SITE_ID', 'REPLACE_ME_SITE_ID');
define('DOWABA_WIDGET_SECRET', 'REPLACE_ME_WIDGET_SECRET');

// Kullanılan kanalları belirten site_id (numerik)
define('DOWABA_SITE_ID', 42);
