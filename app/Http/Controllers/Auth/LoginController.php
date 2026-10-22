<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Lockout;
use App\Support\ReturnUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** A real Argon2id hash, so unknown usernames take as long as known ones. */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$OXVtMmhQU3VuRmRxSldYRA$KWjwtjPsOlCmaBRQ70Kcoox4yRPSHwffkjVcA6IM9So';

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->filled('return') && ReturnUrl::allowed($request->query('return'))) {
            $request->session()->put('url.intended', $request->query('return'));
        }
        if (Auth::check()) {
            return redirect()->intended(ReturnUrl::or(null));
        }

        return view('auth.login', [
            'status' => session('status'),
            'locked' => session('locked'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:512'],
            'remember' => ['nullable', 'boolean'],
        ]);
        $login = trim($credentials['login']);

        if (Lockout::locked($login)) {
            Audit::record('login.locked', null, $login);
            throw ValidationException::withMessages([
                'login' => __('Too many failed attempts. Try again in :minutes minutes.', ['minutes' => max(1, (int) ceil(Lockout::secondsLeft($login) / 60))]),
            ]);
        }

        $user = User::query()
            ->where('username', $login)
            ->orWhere('email', mb_strtolower($login))
            ->first();

        $valid = Hash::check($credentials['password'], $user?->password ?? self::DUMMY_HASH);
        if (! $user || ! $valid) {
            Lockout::fail($login);
            Audit::record('login.failed', $user?->id, $login);
            throw ValidationException::withMessages(['login' => __('That username and password do not match.')]);
        }
        if (! $user->isActive()) {
            Audit::record('login.disabled', $user->id, $user->username);
            throw ValidationException::withMessages(['login' => __('This account is disabled. Ask an administrator.')]);
        }
        Lockout::clear($login);

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $credentials['password']])->save();
        }

        if ($user->hasTwoFactor()) {
            $request->session()->put('login.two_factor', [
                'user' => $user->id,
                'remember' => (bool) ($credentials['remember'] ?? false),
                'at' => time(),
            ]);

            return redirect()->route('two-factor');
        }
        if ($user->role === 'admin' && config('plainshow.require_admin_two_factor')) {
            Audit::record('login.two_factor_required', $user->id, $user->username);
            throw ValidationException::withMessages(['login' => __('Administrators need two-factor authentication. Ask another administrator to reset your access.')]);
        }

        return self::complete($request, $user, (bool) ($credentials['remember'] ?? false), 'password');
    }

    public static function complete(Request $request, User $user, bool $remember, string $method): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('login.two_factor');

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();
        Audit::record('login.success', $user->id, $user->username, ['method' => $method]);

        return redirect()->intended(ReturnUrl::or(null));
    }
}
