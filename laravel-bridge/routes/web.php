<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dowaba Laravel Bridge — Routes
|--------------------------------------------------------------------------
|
| /dowaba/auth/login    — Dowaba authorize endpoint'ine yönlendir (PKCE state set)
| /dowaba/auth/callback — Authorization code → access_token exchange
| /dowaba/auth/logout   — Token revoke + session purge
|
| Şu an iskelet — Controller sınıfları sonraki seansta yazılacak.
|
*/

$prefix = config('dowaba.routes.prefix', 'dowaba');
$middleware = config('dowaba.routes.middleware', ['web']);

Route::middleware($middleware)
    ->prefix("{$prefix}/auth")
    ->name('dowaba.auth.')
    ->group(function () {
        // Route::get('/login', [LoginController::class, '__invoke'])->name('login');
        // Route::get('/callback', [CallbackController::class, '__invoke'])->name('callback');
        // Route::post('/logout', [LogoutController::class, '__invoke'])->name('logout');

        Route::get('/_skeleton', fn () => response()->json([
            'message' => 'Dowaba Laravel Bridge iskelet — OAuth route\'ları sonraki seansta eklenecek',
            'planned_routes' => [
                'GET  /dowaba/auth/login',
                'GET  /dowaba/auth/callback',
                'POST /dowaba/auth/logout',
            ],
        ]));
    });
