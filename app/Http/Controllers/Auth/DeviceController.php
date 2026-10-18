<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeviceController extends Controller
{
    private const SERVERS = [
        'cloud.plainshow.se' => 'https://cloud.plainshow.se',
        'cloud-staging.plainshow.se' => 'https://cloud-staging.plainshow.se',
    ];

    public function connect(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login', ['return' => $request->fullUrl()]);
        }

        $server = self::SERVERS[$request->query('server', '')] ?? null;
        $flow = $request->query('flow');
        $state = $request->query('state');
        $token = $request->query('requesttoken');
        $account = $request->query('account');
        if ($server === null || ! in_array($flow, ['v1', 'v2'], true)
            || ! is_string($state) || ! preg_match('/^[a-zA-Z0-9]{64}$/D', $state)
            || ! is_string($token) || $token === '' || strlen($token) > 256
            || ! is_string($account) || $account === '' || strlen($account) > 64) {
            return redirect()->route('devices.result', ['status' => 'expired']);
        }

        $user = Auth::user();
        $linked = DB::table('core_linked_accounts')->where('user_id', $user->id)->where('provider', 'nextcloud')->value('external_id');
        $expected = is_string($linked) && $linked !== '' ? $linked : $user->username;
        if (! hash_equals($expected, $account)) {
            return redirect()->route('devices.result', ['status' => 'account']);
        }

        $client = $request->query('client');
        $client = is_string($client) ? mb_substr(trim($client), 0, 80) : '';

        return view('auth.device', [
            'server' => $server,
            'action' => $server.($flow === 'v1' ? '/index.php/login/flow' : '/index.php/login/v2/grant'),
            'state' => $state,
            'token' => $token,
            'client' => $client !== '' ? $client : 'your device',
            'account' => $account,
        ]);
    }

    public function result(Request $request): View
    {
        $status = $request->query('status');
        $message = match ($status) {
            'connected' => 'Your device is connected. You can close this page.',
            'account' => 'This Cloud session belongs to another account. Sign out of Cloud and try again.',
            default => 'This device request expired. Return to your app and start again.',
        };

        return view('auth.device-result', ['message' => $message]);
    }
}
