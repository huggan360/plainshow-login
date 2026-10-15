<?php

namespace App\Providers;

use App\OAuth\IdTokenResponse;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\AuthorizationServer;
use OpenIDConnect\ClaimExtractor;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;
use OpenIDConnect\Laravel\PassportServiceProvider;

/** Passport with OpenID Connect, using our token response (key id, fixed issuer). */
class OidcServiceProvider extends PassportServiceProvider
{
    public function makeAuthorizationServer(): AuthorizationServer
    {
        $cryptKey = $this->makeCryptKey('private');
        $encryptionKey = app(Encrypter::class)->getKey();

        $responseType = new IdTokenResponse(
            app(config('openid.repositories.identity')),
            app(ClaimExtractor::class),
            Configuration::forSymmetricSigner(
                app(config('openid.signer')),
                InMemory::plainText($cryptKey->getKeyContents()),
            ),
            app(LaravelCurrentRequestService::class),
            $encryptionKey,
        );

        return new AuthorizationServer(
            app(ClientRepository::class),
            app(AccessTokenRepository::class),
            app(config('openid.repositories.scope')),
            $cryptKey,
            $encryptionKey,
            $responseType,
        );
    }
}
