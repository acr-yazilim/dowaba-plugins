<?php

declare(strict_types=1);

use Dowaba\LaravelBridge\Http\Controllers\CallbackController;
use Dowaba\LaravelBridge\Http\Controllers\LoginController;
use Dowaba\LaravelBridge\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dowaba Laravel Bridge — Routes
|--------------------------------------------------------------------------
|
| Hepsi 'web' middleware grubunda (CSRF + session). Prefix config'ten gelir
| (default 'dowaba'). Yazılımcı kendi domain'inde bu URL'leri Dowaba admin
| panelindeki OAuth client kaydında redirect_uri olarak vermeli:
|
|   https://app.example.com/dowaba/auth/callback
|
*/

$prefix = config('dowaba.routes.prefix', 'dowaba');
$middleware = config('dowaba.routes.middleware', ['web']);

Route::middleware($middleware)
    ->prefix("{$prefix}/auth")
    ->name('dowaba.auth.')
    ->group(function () {
        Route::get('/login', LoginController::class)->name('login');
        Route::get('/callback', CallbackController::class)->name('callback');
        Route::match(['get', 'post'], '/logout', LogoutController::class)->name('logout');
    });
