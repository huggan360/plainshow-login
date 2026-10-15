<?php

namespace App\Entities;

use App\Models\User;
use App\Support\PlatformAccess;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use OpenIDConnect\Claims\Traits\WithClaims;
use OpenIDConnect\Interfaces\IdentityEntityInterface;

/** Collects the claims that go into every PlainShow ID token. */
class IdentityEntity implements IdentityEntityInterface
{
    use EntityTrait;
    use WithClaims;

    protected User $user;

    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
        $this->user = User::findOrFail($identifier);
    }

    public function getClaims(): array
    {
        return self::claimsFor($this->user);
    }

    /** Shared with the userinfo endpoint so both always agree. */
    public static function claimsFor(User $user): array
    {
        $access = PlatformAccess::for($user);

        return array_filter([
            'name' => $user->name(),
            'given_name' => $user->first_name,
            'family_name' => $user->last_name,
            'preferred_username' => $user->username,
            'picture' => $user->avatarUrl(),
            'email' => $user->email,
            'email_verified' => $user->email !== null ? false : null,
            'updated_at' => $user->updated_at?->getTimestamp(),
            'ps_role' => $user->role,
            'ps_services' => $access['services'],
            'ps_caps' => $access['capabilities'],
            'ps_pi_user' => $user->pi_username,
        ], fn ($value) => $value !== null);
    }
}
