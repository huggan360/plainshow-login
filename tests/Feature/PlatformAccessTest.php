<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlatformAccessTest extends TestCase
{
    public function test_cloud_follows_permission_and_web_requires_a_server_account(): void
    {
        DB::statement('CREATE TABLE core_linked_accounts (user_id INTEGER, provider TEXT, external_id TEXT)');
        $user = new User(['username' => 'tester', 'role' => 'admin']);
        $user->id = 42;
        $user->status = 'active';

        $this->assertContains('cloud', PlatformAccess::for($user)['services']);
        $this->assertNotContains('web', PlatformAccess::for($user)['services']);

        DB::table('core_linked_accounts')->insert(['user_id' => 42, 'provider' => 'nextcloud', 'external_id' => 'tester']);
        $user->pi_username = 'piuser';
        $this->assertContains('cloud', PlatformAccess::for($user)['services']);
        $this->assertContains('web', PlatformAccess::for($user)['services']);

        $user->status = 'disabled';
        $this->assertSame(['services' => [], 'capabilities' => []], PlatformAccess::for($user));
        $this->assertFalse(PlatformAccess::canUseClient($user, 'core'));
    }
}
