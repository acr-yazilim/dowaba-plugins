<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge;

use Dowaba\LaravelBridge\Resources\AiFunctions;
use Dowaba\LaravelBridge\Resources\Channels;
use Dowaba\LaravelBridge\Resources\Contacts;
use Dowaba\LaravelBridge\Resources\Conversations;
use Dowaba\LaravelBridge\Resources\Sites;
use Dowaba\LaravelBridge\Resources\WhatsApp;
use Illuminate\Contracts\Foundation\Application;

/**
 * Dowaba Laravel Bridge — ana entry noktası.
 *
 * Kullanım:
 *   Dowaba::whatsapp()->send($contactId, 'Merhaba');
 *   Dowaba::conversations()->list($siteId);
 *
 * Şu an iskelet — Resource sınıfları sonraki seansta doldurulacak.
 */
class DowabaManager
{
    public function __construct(protected Application $app) {}

    public function whatsapp(): WhatsApp
    {
        return $this->app->make(WhatsApp::class);
    }

    public function channels(): Channels
    {
        return $this->app->make(Channels::class);
    }

    public function conversations(): Conversations
    {
        return $this->app->make(Conversations::class);
    }

    public function contacts(): Contacts
    {
        return $this->app->make(Contacts::class);
    }

    public function sites(): Sites
    {
        return $this->app->make(Sites::class);
    }

    public function aiFunctions(): AiFunctions
    {
        return $this->app->make(AiFunctions::class);
    }

    public function version(): string
    {
        return '0.0.1-skeleton';
    }
}
