<?php

declare(strict_types=1);

namespace Dowaba\LaravelBridge\Http\Controllers;

use Dowaba\LaravelBridge\Auth\OauthFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(Request $request, OauthFlow $oauth): RedirectResponse
    {
        $localUserId = Auth::check() ? (int) Auth::id() : null;

        if ($intended = $request->query('intended')) {
            session()->put('url.intended', $intended);
        }

        $pair = $oauth->authorizationUrl($localUserId);

        return redirect()->away($pair['url']);
    }
}
