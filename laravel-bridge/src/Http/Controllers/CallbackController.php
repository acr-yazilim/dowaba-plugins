<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Http\Controllers;

use Dowaba\LaravelBridge\Auth\OauthFlow;
use Dowaba\LaravelBridge\Support\AuthenticationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CallbackController extends Controller
{
    public function __invoke(Request $request, OauthFlow $oauth): RedirectResponse|Renderable
    {
        if ($error = $request->query('error')) {
            return $this->errorView($error, (string) $request->query('error_description', ''));
        }

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');

        if ($code === '' || $state === '') {
            return $this->errorView('missing_params', 'code veya state parametresi eksik');
        }

        try {
            $oauth->exchangeCode($code, $state);
        } catch (AuthenticationException $e) {
            return $this->errorView($e->errorCode() ?? 'auth_failed', $e->getMessage());
        }

        $intended = $this->safeIntendedUrl(session()->pull('url.intended', '/'));

        return redirect()->to($intended)->with('status', 'Dowaba ile başarılı şekilde bağlandın.');
    }

    /**
     * Open Redirect koruması — sadece kendi domain'imizdeki path'lere yönlendir.
     *
     * Saldırı: `?intended=https://evil.com/phish` ile login → OAuth callback → evil.com.
     * Whitelist kuralı:
     *   1. `/` ile başlamalı (relative path)
     *   2. `//` ile başlamayacak (protocol-relative URL, https://evil.com'a denk)
     *   3. Boşsa veya geçersizse `/` (root) döner
     */
    private function safeIntendedUrl(?string $intended): string
    {
        if (! is_string($intended) || $intended === '') {
            return '/';
        }

        if (! str_starts_with($intended, '/')) {
            return '/';
        }

        if (str_starts_with($intended, '//')) {
            return '/';
        }

        return $intended;
    }

    private function errorView(string $code, string $message): Renderable
    {
        return view('dowaba::connect-error', [
            'errorCode' => $code,
            'errorMessage' => $message,
        ]);
    }
}
