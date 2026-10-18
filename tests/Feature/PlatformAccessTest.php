<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlatformAccessTest extends TestCase
{
    public function test_cloud_and_web_require_linked_accounts_even_for_admins(): void
    {
        DB::statement('CREATE TABLE core_linked_accounts (user_id INTEGER, provider TEXT, external_id TEXT)');
        $user = new User(['username' => 'tester', 'role' => 'admin']);
        $user->id = 42;

        $this->assertNotContains('cloud', PlatformAccess::for($user)['services']);
        $this->assertNotContains('web', PlatformAccess::for($user)['services']);

        DB::table('core_linked_accounts')->insert(['user_id' => 42, 'provider' => 'nextcloud', 'external_id' => 'tester']);
        $user->pi_username = 'piuser';
        $this->assertContains('cloud', PlatformAccess::for($user)['services']);
        $this->assertContains('web', PlatformAccess::for($user)['services']);
    }
}
