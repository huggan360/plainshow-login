<?php

namespace App\OAuth;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OpenIDConnect\Entities\ScopeEntity;

/** Scopes come from config/openid.php, so `plainshow` is accepted alongside the OIDC ones. */
class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier)
    {
        if (! array_key_exists($identifier, (array) config('openid.passport.tokens_can'))) {
            return null;
        }
        $scope = new ScopeEntity;
        $scope->setIdentifier($identifier);

        return $scope;
    }

    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null, $authCodeId = null)
    {
        return array_values(array_filter($scopes, fn ($scope) => $this->getScopeEntityByIdentifier($scope->getIdentifier()) !== null));
    }
}
