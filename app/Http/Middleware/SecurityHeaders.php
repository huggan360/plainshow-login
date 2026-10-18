<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/** Strict headers for every login page. Scripts need a per-request nonce. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);
        $request->attributes->set('csp_nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // The logout page frames every PlainShow app to sign them out too.
        $frames = "frame-src https://plainshow.se https://*.plainshow.se";
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-$nonce'",
            "style-src 'self' 'nonce-$nonce'",
            "img-src 'self' data: https://plainshow.se",
            "font-src 'self'",
            "connect-src 'self'",
            $frames,
            "frame-ancestors 'none'",
            "form-action 'self' https://plainshow.se https://*.plainshow.se".($request->is('devices/connect') ? ' nc:' : ''),
            "base-uri 'none'",
            "object-src 'none'",
        ]));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
