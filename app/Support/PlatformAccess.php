<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PlainShow\Kit\Access;

/**
 * Reads the extension and permission tables that plainshow-core owns and
 * turns them into the services and capabilities a user holds. Before core
 * has migrated, sensible defaults apply so sign-in still works.
 */
class PlatformAccess
{
    /** @return array{services: list<string>, capabilities: list<string>} */
    public static function for(User $user): array
    {
        if (! $user->isActive()) {
            return ['services' => [], 'capabilities' => []];
        }

        if (! Schema::hasTable('core_extensions')) {
            return self::restrictUnavailable($user, RoleAccess::resolve($user->role, self::fallbackExtensions(), [], Access::defaultRolePatterns()[$user->role] ?? [], []));
        }

        $extensions = DB::table('core_extensions')->orderBy('sort')->get()->map(fn ($row) => [
            'id' => $row->id,
            'enabled' => (bool) $row->enabled,
            'default_access' => json_decode($row->default_access ?: '[]', true) ?: [],
        ])->all();

        $accessOverrides = DB::table('core_extension_access')->where('user_id', $user->id)->pluck('effect', 'extension_id')->all();

        $patterns = DB::table('core_role_capabilities')->where('role', $user->role)->pluck('capability')->all();
        if ($patterns === [] && ! DB::table('core_role_capabilities')->exists()) {
            $patterns = Access::defaultRolePatterns()[$user->role] ?? [];
        }
        $capabilityOverrides = DB::table('core_user_capabilities')->where('user_id', $user->id)->pluck('effect', 'capability')->all();

        $resolved = RoleAccess::resolve($user->role, $extensions, $accessOverrides, $patterns, $capabilityOverrides);
        if ($resolved['capabilities'] !== ['*']) {
            $known = DB::table('core_capabilities')->pluck('name')->all();
            $resolved['capabilities'] = Access::expand($resolved['capabilities'], $known);
            $denied = array_keys(array_filter($capabilityOverrides, fn ($effect) => $effect === 'deny'));
            $resolved['capabilities'] = array_values(array_diff($resolved['capabilities'], $denied));
            $resolved['capabilities'] = CapabilityRules::expand($resolved['capabilities']);
        }

        return self::restrictUnavailable($user, $resolved);
    }

    private static function restrictUnavailable(User $user, array $resolved): array
    {
        $unavailable = [];
        if (! is_string($user->pi_username) || $user->pi_username === '') {
            $unavailable[] = 'web';
        }
        $resolved['services'] = array_values(array_diff($resolved['services'], $unavailable));
        if ($resolved['capabilities'] !== ['*']) {
            $resolved['capabilities'] = array_values(array_filter($resolved['capabilities'], fn (string $capability) => ! in_array(strstr($capability, '.', true), $unavailable, true)));
        }

        return $resolved;
    }

    /** Whether an OAuth client (one per extension) is open to this user right now. */
    public static function canUseClient(User $user, ?string $extensionId): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($extensionId === null || $extensionId === '' || $extensionId === 'core') {
            return true;
        }

        return in_array($extensionId, self::for($user)['services'], true);
    }

    private static function fallbackExtensions(): array
    {
        return [
            ['id' => 'blog', 'enabled' => true, 'default_access' => ['admin', 'moderator', 'user']],
            ['id' => 'latex', 'enabled' => true, 'default_access' => ['admin', 'moderator', 'user']],
            ['id' => 'web', 'enabled' => true, 'default_access' => ['admin']],
            ['id' => 'cloud', 'enabled' => true, 'default_access' => ['admin', 'moderator', 'user']],
        ];
    }
}
