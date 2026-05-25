<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Resources;

class Channels
{
    public function whatsapp(): WhatsApp
    {
        return app(WhatsApp::class);
    }

    public function mail(): array
    {
        return ['skeleton' => true, 'channel' => 'mail'];
    }

    public function instagram(): array
    {
        return ['skeleton' => true, 'channel' => 'instagram'];
    }

    public function telegram(): array
    {
        return ['skeleton' => true, 'channel' => 'telegram'];
    }

    public function sip(): array
    {
        return ['skeleton' => true, 'channel' => 'sip'];
    }
}
