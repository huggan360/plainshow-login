<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\AccountLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Reset links are created by an administrator in core (or the CLI). Only a
 * SHA-256 of the token is stored, and a link works once within an hour.
 */
class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $username = (string) $request->query('u', '');

        return view('auth.reset', [
            'token' => $token,
            'username' => $username,
            'valid' => $this->record($username, $token) !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:128'],
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(10)->max(512)->uncompromised(0)->letters()],
        ]);

        if ($this->record($data['username'], $data['token']) === null) {
            throw ValidationException::withMessages(['password' => __('This reset link has expired or was already used.')]);
        }
        $user = User::where('username', $data['username'])->firstOrFail();
        AccountLifecycle::syncPassword($user, $data['password']);
        $user->forceFill(['password' => $data['password'], 'password_changed_at' => now()])->save();
        $user->tokens()->update(['revoked' => true]);
        DB::table('login_password_reset_tokens')->where('username', $user->username)->delete();
        DB::table('login_sessions')->where('user_id', $user->id)->delete();
        Audit::record('password.reset', $user->id, $user->username);

        return redirect()->route('login')->with('status', __('Your password is set. Sign in with it now.'));
    }

    private function record(string $username, string $token): ?object
    {
        $row = DB::table('login_password_reset_tokens')->where('username', $username)->first();
        if (! $row || ! hash_equals($row->token, hash('sha256', $token))) {
            return null;
        }

        return now()->subMinutes((int) config('plainshow.password_reset_minutes'))->lt($row->created_at) ? $row : null;
    }
}
