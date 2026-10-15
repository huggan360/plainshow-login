<?php

namespace App\Http\Middleware;

use App\Models\OAuthClient;
use App\Support\Audit;
use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before Passport approves a sign-in: the account must be active and
 * allowed to use the app (extension) that is asking.
 */
class EnsureClientAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }
        if (! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => 'This account is disabled. Ask an administrator.']);
        }

        $client = OAuthClient::find($request->query('client_id'));
        if ($client && ! PlatformAccess::canUseClient($user, $client->extension_id)) {
            Audit::record('authorize.denied', $user->id, $user->username, ['extension' => $client->extension_id]);

            return response()->view('auth.denied', ['service' => $client->name], 403);
        }

        return $next($request);
    }
}
