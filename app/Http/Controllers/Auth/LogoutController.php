<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use App\Support\Audit;
use App\Support\CloudLogout;
use App\Support\ReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Signs out everywhere: ends the login session, then loads every app's
 * front-channel logout address in a hidden frame before moving on.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();
        if ($user) {
            Audit::record('logout', $user->id, $user->username);
            // Refresh tokens would let an app sign the person straight back in.
            $user->tokens()->update(['revoked' => true]);
            CloudLogout::send($user);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $issuer = rtrim((string) config('app.url'), '/');
        $frames = OAuthClient::query()
            ->whereNotNull('frontchannel_logout_uri')
            ->where('revoked', false)
            ->pluck('frontchannel_logout_uri')
            ->filter(fn ($uri) => ReturnUrl::allowed($uri))
            ->map(fn ($uri) => $uri.(str_contains($uri, '?') ? '&' : '?').http_build_query(['iss' => $issuer]))
            ->values();

        return view('auth.logout', [
            'frames' => $frames,
            // `post_logout_redirect_uri` is the OIDC name; Nextcloud sends it.
            'return' => ReturnUrl::or($request->query('return') ?? $request->query('post_logout_redirect_uri'), route('login')),
        ]);
    }
}
