<?php

namespace App\OAuth;

use Laravel\Passport\Passport;
use PlainShow\Kit\Jwt;
use RuntimeException;

/** The RSA key that signs every PlainShow token, as a JWK with a stable key id. */
final class SigningKey
{
    /** @return array{n: string, e: string} */
    private static function components(): array
    {
        $pem = str_replace('\\n', "\n", (string) config('passport.public_key', ''));
        if ($pem === '') {
            $pem = (string) file_get_contents(Passport::keyPath('oauth-public.key'));
        }
        $key = openssl_pkey_get_public($pem);
        $details = $key === false ? false : openssl_pkey_get_details($key);
        if (! $details || ! isset($details['rsa'])) {
            throw new RuntimeException('The OAuth public key is not an RSA key.');
        }

        return ['n' => Jwt::base64UrlEncode($details['rsa']['n']), 'e' => Jwt::base64UrlEncode($details['rsa']['e'])];
    }

    /** RFC 7638 thumbprint, so the id changes exactly when the key does. */
    public static function kid(): string
    {
        static $kid = null;
        if ($kid === null) {
            $parts = self::components();
            $kid = Jwt::base64UrlEncode(hash('sha256', json_encode(['e' => $parts['e'], 'kty' => 'RSA', 'n' => $parts['n']], JSON_UNESCAPED_SLASHES), true));
        }

        return $kid;
    }

    /** @return array<string, string> */
    public static function jwk(): array
    {
        return ['kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => self::kid()] + self::components();
    }
}
