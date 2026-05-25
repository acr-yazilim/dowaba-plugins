<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Http\Middleware;

use Closure;
use Dowaba\LaravelBridge\Auth\TrustedPartnerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard — Dowaba OAuth tokenı yoksa /dowaba/auth/login'e yönlendir.
 *
 * Kullanım:
 *   Route::middleware('dowaba.connected')->group(function () { ... });
 *
 * Alias `dowaba.connected` ServiceProvider'da register edilir.
 */
class EnsureDowabaConnected
{
    public function __construct(protected TrustedPartnerToken $token) {}

    public function handle(Request $request, Closure $next): Response
    {
        $localUserId = Auth::check() ? (int) Auth::id() : null;

        if ($this->token->current($localUserId) === null) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('dowaba.auth.login');
        }

        return $next($request);
    }
}
