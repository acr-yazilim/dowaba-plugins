<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Dowaba SaaS URL
    |--------------------------------------------------------------------------
    | Dowaba SaaS endpoint'i. Production: https://dowaba.com
    | Bayi kendi white-label domain'ini kullanıyorsa: https://destek.firmaniz.com
    */
    'url' => env('DOWABA_URL', 'https://dowaba.com'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Bilgileri
    |--------------------------------------------------------------------------
    | Dowaba admin panelinden alınır: /admin/oauth/clients
    | client_id "dosc_" prefix'iyle başlar, client_secret "dosec_" ile.
    */
    'client_id' => env('DOWABA_CLIENT_ID'),
    'client_secret' => env('DOWABA_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    | OAuth callback'in geleceği yer. Dowaba client kayıtında bu URI olmalı.
    | Bridge route'u: /dowaba/auth/callback
    */
    'redirect_uri' => env('DOWABA_REDIRECT_URI', env('APP_URL').'/dowaba/auth/callback'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scope'lar
    |--------------------------------------------------------------------------
    | Boşluk ile ayrılmış scope listesi. MVP: openid profile email
    | Genişletilmiş (Faz 1.1): dowaba.sites.read, dowaba.conversations.read,
    | dowaba.whatsapp.send, dowaba.contacts.write, offline_access, vb.
    */
    'scopes' => env('DOWABA_SCOPES', 'openid profile email offline_access'),

    /*
    |--------------------------------------------------------------------------
    | Widget Embed Ayarları
    |--------------------------------------------------------------------------
    | <x-dowaba::widget-script> component'inin HMAC token üretmesi için.
    | widget_secret Dowaba admin → site detay → "Widget Secret" alanı.
    */
    'widget' => [
        'site_id' => env('DOWABA_WIDGET_SITE_ID'),
        'secret' => env('DOWABA_WIDGET_SECRET'),
        'token_ttl' => env('DOWABA_WIDGET_TOKEN_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP İstemci Ayarları
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('DOWABA_HTTP_TIMEOUT', 10),
        'connect_timeout' => env('DOWABA_HTTP_CONNECT_TIMEOUT', 3),
        'retries' => env('DOWABA_HTTP_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache
    |--------------------------------------------------------------------------
    | OAuth access/refresh token'ları nerede saklansın. 'database' = dowaba_oauth_tokens tablosu (publish edilen migration), 'cache' = Laravel Cache (TTL ile).
    */
    'token_store' => env('DOWABA_TOKEN_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | JWKS Cache TTL
    |--------------------------------------------------------------------------
    | Dowaba JWKS (public key) ne kadar süre cache'lensin (saniye).
    | Default 3600 (1 saat). Key rotation'da kid mismatch'te otomatik invalidate.
    */
    'jwks_cache_ttl' => env('DOWABA_JWKS_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix + Middleware
    |--------------------------------------------------------------------------
    | Bridge'in kendi route'ları (/dowaba/auth/{login,callback,logout}).
    | Çakışma varsa prefix değiştirilebilir.
    */
    'routes' => [
        'prefix' => env('DOWABA_ROUTE_PREFIX', 'dowaba'),
        'middleware' => ['web'],
    ],
];
