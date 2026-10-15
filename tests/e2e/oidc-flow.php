<?php

declare(strict_types=1);

/*
 * End-to-end sign-in against a running login server:
 *   php tests/e2e/oidc-flow.php http://127.0.0.1:8098
 * Creates a disposable account and client, signs in through the real
 * authorize, login and token endpoints with the kit's OIDC client, then
 * removes both.
 */

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PlainShow\Kit\Jwt;
use PlainShow\Kit\KitException;
use PlainShow\Kit\OidcClient;

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8098', '/');
$redirect = 'https://e2e.plainshow.se/auth/callback';
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    echo ($condition ? 'ok   ' : 'FAIL ').$message."\n";
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

/** Minimal browser: keeps cookies, never follows redirects. */
final class Browser
{
    private string $jar;

    public function __construct()
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'e2e-jar');
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    /** @return array{status: int, location: ?string, body: string} */
    public function go(string $method, string $url, ?array $form = null): array
    {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($form !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($form));
        }
        $raw = (string) curl_exec($handle);
        $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $headers = substr($raw, 0, $headerSize);
        preg_match('/^location:\s*(.+)$/mi', $headers, $match);

        return ['status' => $status, 'location' => isset($match[1]) ? trim($match[1]) : null, 'body' => substr($raw, $headerSize)];
    }

    public function csrf(string $html): string
    {
        preg_match('/name="_token" value="([^"]+)"/', $html, $match);

        return $match[1] ?? '';
    }
}

$username = 'e2e_'.Str::lower(Str::random(6));
$password = Str::random(20);
$user = new User;
$user->forceFill(['username' => $username, 'first_name' => 'End', 'last_name' => 'To End', 'password' => $password, 'role' => 'member', 'status' => 'active'])->save();
$secret = Str::random(48);
$client = new OAuthClient;
$client->forceFill(['name' => 'E2E', 'extension_id' => null, 'secret' => $secret, 'redirect' => $redirect, 'personal_access_client' => false, 'password_client' => false, 'revoked' => false, 'provider' => 'users'])->save();
// Members cannot use Web by default, so this client must be refused.
$webSecret = Str::random(48);
$webClient = OAuthClient::where('extension_id', 'web')->first();
$createdWeb = false;
if (! $webClient) {
    $webClient = new OAuthClient;
    $webClient->forceFill(['name' => 'Web E2E', 'extension_id' => 'web', 'secret' => $webSecret, 'redirect' => $redirect, 'personal_access_client' => false, 'password_client' => false, 'revoked' => false, 'provider' => 'users'])->save();
    $createdWeb = true;
}

try {
    $session = [];
    $oidc = new OidcClient(['issuer' => $base, 'client_id' => (string) $client->getKey(), 'client_secret' => $secret, 'redirect_uri' => $redirect, 'cache_dir' => sys_get_temp_dir()]);
    $browser = new Browser;

    $authorize = $oidc->authorizationUrl($session, '/after');
    $step = $browser->go('GET', $authorize);
    check($step['status'] === 302 && str_ends_with((string) $step['location'], '/login'), 'Signed-out authorize sends to /login');

    $page = $browser->go('GET', $base.'/login');
    check($page['status'] === 200 && str_contains($page['body'], 'Continue to E2E'), 'Login page names the app being signed in to');

    $wrong = $browser->go('POST', $base.'/login', ['_token' => $browser->csrf($page['body']), 'login' => $username, 'password' => 'not-the-password']);
    check($wrong['status'] === 302, 'Wrong password is rejected');

    $page = $browser->go('GET', $base.'/login');
    $login = $browser->go('POST', $base.'/login', ['_token' => $browser->csrf($page['body']), 'login' => $username, 'password' => $password]);
    check($login['status'] === 302 && str_contains((string) $login['location'], '/oauth/authorize'), 'Correct password returns to authorize');

    $approved = $browser->go('GET', (string) $login['location']);
    check($approved['status'] === 302 && str_starts_with((string) $approved['location'], $redirect.'?'), 'Authorize redirects to the app without a consent screen');
    parse_str((string) parse_url((string) $approved['location'], PHP_URL_QUERY), $query);

    $result = $oidc->handleCallback($session, $query);
    $identity = $result['identity'];
    check($identity->id() === $user->id && $identity->username() === $username, 'ID token verified with JWKS, nonce and audience');
    check($identity->role() === 'member' && in_array('core.dashboard.use', $identity->capabilities(), true), 'PlainShow claims are present');
    $header = json_decode(Jwt::base64UrlDecode(explode('.', $result['tokens']['id_token'])[0]), true);
    check(($header['alg'] ?? '') === 'RS256' && ! empty($header['kid']), 'ID token is RS256 with a key id');
    check(Jwt::peek($result['tokens']['id_token'])['iss'] === $base, 'Issuer is the configured URL');

    $claims = $oidc->verifyAccessToken($result['tokens']['access_token']);
    check((string) $claims['sub'] === (string) $user->id, 'Access token verifies with the same keys');

    $info = json_decode(curl_exec(curl_init_with($base.'/oauth/userinfo', $result['tokens']['access_token'])), true);
    check(($info['preferred_username'] ?? null) === $username && isset($info['ps_role']), 'Userinfo returns scoped claims');

    try {
        $oidc->handleCallback($session, $query);
        check(false, 'Replayed callback is refused');
    } catch (KitException) {
        check(true, 'Replayed callback is refused');
    }

    $web = new OidcClient(['issuer' => $base, 'client_id' => (string) $webClient->getKey(), 'client_secret' => $webSecret, 'redirect_uri' => $redirect, 'cache_dir' => sys_get_temp_dir()]);
    $denied = $browser->go('GET', $web->authorizationUrl($session, '/'));
    check($denied['status'] === 403 && str_contains($denied['body'], 'No access'), 'A member is refused an app they have no access to');

    // Two-factor: password alone must stop at the code step, a code finishes it,
    // and the same code cannot be used twice.
    $google2fa = new PragmaRX\Google2FA\Google2FA;
    $user->forceFill(['two_factor_secret' => $google2fa->generateSecretKey(32), 'two_factor_confirmed_at' => now()])->save();
    $browser2 = new Browser;
    $browser2->go('GET', $oidc->authorizationUrl($session, '/after'));
    $page = $browser2->go('GET', $base.'/login');
    $first = $browser2->go('POST', $base.'/login', ['_token' => $browser2->csrf($page['body']), 'login' => $username, 'password' => $password]);
    check($first['status'] === 302 && str_ends_with((string) $first['location'], '/two-factor'), 'Password with 2FA enabled stops at the code step');
    $codePage = $browser2->go('GET', $base.'/two-factor');
    $bad = $browser2->go('POST', $base.'/two-factor', ['_token' => $browser2->csrf($codePage['body']), 'code' => '000000']);
    check($bad['status'] === 302 && str_ends_with((string) $bad['location'], '/two-factor'), 'Wrong code is rejected');
    $code = $google2fa->getCurrentOtp($user->fresh()->two_factor_secret);
    $codePage = $browser2->go('GET', $base.'/two-factor');
    $good = $browser2->go('POST', $base.'/two-factor', ['_token' => $browser2->csrf($codePage['body']), 'code' => $code]);
    check($good['status'] === 302 && str_contains((string) $good['location'], '/oauth/authorize'), 'Correct code completes sign-in');

    $browser3 = new Browser;
    $browser3->go('GET', $oidc->authorizationUrl($session, '/after'));
    $page = $browser3->go('GET', $base.'/login');
    $browser3->go('POST', $base.'/login', ['_token' => $browser3->csrf($page['body']), 'login' => $username, 'password' => $password]);
    $codePage = $browser3->go('GET', $base.'/two-factor');
    $replay = $browser3->go('POST', $base.'/two-factor', ['_token' => $browser3->csrf($codePage['body']), 'code' => $code]);
    check(str_ends_with((string) $replay['location'], '/two-factor'), 'A used code cannot be replayed');

    $bogus = $browser->go('GET', $base.'/oauth/authorize?'.http_build_query(['client_id' => '999999', 'redirect_uri' => $redirect, 'response_type' => 'code', 'scope' => 'openid']));
    check($bogus['status'] >= 400 && $bogus['location'] === null, 'Unknown client is refused without redirecting');

    $out = $browser->go('GET', $base.'/logout?return='.rawurlencode('https://evil.example/'));
    check($out['status'] === 200 && ! str_contains($out['body'], 'evil.example'), 'Logout ignores foreign return addresses');
} finally {
    if ($createdWeb) {
        $webClient->delete();
    }
    DB::table('oauth_access_tokens')->where('client_id', $client->getKey())->delete();
    DB::table('oauth_auth_codes')->where('client_id', $client->getKey())->delete();
    DB::table('audit_events')->where('user_id', $user->id)->delete();
    $client->delete();
    $user->delete();
}

echo "End-to-end sign-in checks passed: $checks\n";

function curl_init_with(string $url, string $token)
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Accept: application/json']]);

    return $handle;
}
