<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Http\Controllers;

use Dowaba\LaravelBridge\Auth\TrustedPartnerToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request, TrustedPartnerToken $token): RedirectResponse
    {
        $localUserId = Auth::check() ? (int) Auth::id() : null;

        $token->revoke($localUserId);

        return redirect()
            ->to($request->input('redirect_to', '/'))
            ->with('status', 'Dowaba bağlantısı kapatıldı.');
    }
}
