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

        $redirectTo = $this->safeRedirectUrl($request->input('redirect_to'));

        return redirect()
            ->to($redirectTo)
            ->with('status', 'Dowaba bağlantısı kapatıldı.');
    }

    /**
     * Open Redirect koruması — sadece kendi domain'imizdeki path'lere yönlendir.
     *
     * Saldırı: `<img src="/dowaba/auth/logout?redirect_to=https://evil.com">` ile
     * kurban farkında olmadan logout + phishing sayfasına gider. Whitelist
     * kuralları CallbackController ile aynı (path-only, protocol-relative blocked).
     */
    private function safeRedirectUrl(?string $url): string
    {
        if (! is_string($url) || $url === '') {
            return '/';
        }

        if (! str_starts_with($url, '/')) {
            return '/';
        }

        if (str_starts_with($url, '//')) {
            return '/';
        }

        return $url;
    }
}
