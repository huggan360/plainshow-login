<?php

namespace App\Support;

use App\Models\OAuthClient;
use App\Models\User;
use App\OAuth\SigningKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use PlainShow\Kit\Jwt;
use Throwable;

class CloudLogout
{
    private const HOSTS = ['cloud.plainshow.se', 'cloud-staging.plainshow.se'];

    public static function send(User $user): void
    {
        $clients = OAuthClient::query()->where('revoked', false)->whereNotNull('frontchannel_logout_uri')->get();
        foreach ($clients as $client) {
            $host = parse_url((string) $client->frontchannel_logout_uri, PHP_URL_HOST);
            if (! in_array($host, self::HOSTS, true)) {
                continue;
            }

            try {
                $token = self::token((string) $client->id, (string) $user->id);
                $request = Http::timeout(5)->connectTimeout(2)->asForm()
                    ->withOptions(['curl' => [CURLOPT_RESOLVE => ["$host:443:127.0.0.1"]]]);
                if ($host === 'cloud-staging.plainshow.se') {
                    $request = $request->withoutVerifying();
                }
                $response = $request->post("https://$host/index.php/apps/user_oidc/backchannel-logout/PlainShow", ['logout_token' => $token]);
                if (! $response->successful()) {
                    Log::warning('Cloud session logout failed', ['host' => $host, 'status' => $response->status()]);
                }
            } catch (Throwable $error) {
                Log::warning('Cloud session logout failed', ['host' => $host, 'error' => $error::class]);
            }
        }
    }

    private static function token(string $clientId, string $userId): string
    {
        $issued = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => SigningKey::kid()];
        $claims = [
            'iss' => rtrim((string) config('app.url'), '/'),
            'aud' => $clientId,
            'iat' => $issued,
            'exp' => $issued + 60,
            'jti' => bin2hex(random_bytes(16)),
            'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
            'sub' => $userId,
        ];
        $body = Jwt::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)).'.'.Jwt::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
        $privateKey = (string) config('passport.private_key', '') ?: (string) file_get_contents(Passport::keyPath('oauth-private.key'));
        if (! openssl_sign($body, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign Cloud logout.');
        }
        return $body.'.'.Jwt::base64UrlEncode($signature);
    }
}
