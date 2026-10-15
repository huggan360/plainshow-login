<?php

namespace App\OAuth;

use Lcobucci\JWT\Builder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use OpenIDConnect\IdTokenResponse as BaseResponse;
use OpenIDConnect\Interfaces\IdentityEntityInterface;

/**
 * Adds what clients such as Nextcloud need: a key id header, so they can pick
 * the key from the JWKS, and a fixed issuer instead of one taken from the
 * request's Host header.
 */
class IdTokenResponse extends BaseResponse
{
    protected function getBuilder(AccessTokenEntityInterface $accessToken, IdentityEntityInterface $userEntity): Builder
    {
        return parent::getBuilder($accessToken, $userEntity)
            ->withHeader('kid', SigningKey::kid())
            ->issuedBy(rtrim((string) config('app.url'), '/'));
    }
}
