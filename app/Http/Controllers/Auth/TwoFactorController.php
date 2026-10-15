<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private const PENDING_TTL = 300;

    public function show(Request $request): View|RedirectResponse
    {
        return $this->pendingUser($request) ? view('auth.two-factor') : redirect()->route('login');
    }

    public function store(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->with('status', 'Your sign-in timed out. Please start again.');
        }
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $code = preg_replace('/\s+/', '', $data['code']);

        if (preg_match('/^\d{6}$/', $code)) {
            // verifyKeyNewer refuses a code from a time step already used, so a
            // code seen over someone's shoulder cannot be replayed.
            $lastStep = (int) Cache::get('2fa-step:'.$user->id, 0);
            $step = $google2fa->verifyKeyNewer($user->two_factor_secret, $code, $lastStep, 1);
            if (is_int($step)) {
                Cache::put('2fa-step:'.$user->id, $step, 300);

                return LoginController::complete($request, $user, (bool) $request->session()->get('login.two_factor.remember'), 'totp');
            }
        } else {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (password_verify(strtoupper($code), $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    Audit::record('two_factor.recovery_code_used', $user->id, $user->username, ['remaining' => count($codes)]);

                    return LoginController::complete($request, $user, (bool) $request->session()->get('login.two_factor.remember'), 'recovery');
                }
            }
        }

        Audit::record('two_factor.failed', $user->id, $user->username);
        throw ValidationException::withMessages(['code' => 'That code is not valid.']);
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('login.two_factor');
        if (! is_array($pending) || ($pending['at'] ?? 0) < time() - self::PENDING_TTL) {
            $request->session()->forget('login.two_factor');

            return null;
        }
        $user = User::find($pending['user']);

        return $user && $user->isActive() && $user->hasTwoFactor() ? $user : null;
    }
}
