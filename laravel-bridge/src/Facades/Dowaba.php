<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Facades;

use Dowaba\LaravelBridge\DowabaManager;
use Dowaba\LaravelBridge\Resources\AiFunctions;
use Dowaba\LaravelBridge\Resources\Channels;
use Dowaba\LaravelBridge\Resources\Contacts;
use Dowaba\LaravelBridge\Resources\Conversations;
use Dowaba\LaravelBridge\Resources\Sites;
use Dowaba\LaravelBridge\Resources\WhatsApp;
use Illuminate\Support\Facades\Facade;

/**
 * @method static WhatsApp whatsapp()
 * @method static Channels channels()
 * @method static Conversations conversations()
 * @method static Contacts contacts()
 * @method static Sites sites()
 * @method static AiFunctions aiFunctions()
 * @method static string version()
 *
 * @see DowabaManager
 */
class Dowaba extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DowabaManager::class;
    }
}
