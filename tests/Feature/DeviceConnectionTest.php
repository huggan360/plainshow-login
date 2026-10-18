<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeviceConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('CREATE TABLE core_linked_accounts (user_id INTEGER, provider TEXT, external_id TEXT)');
    }

    private function account(): User
    {
        $user = new User(['username' => 'tester']);
        $user->id = 42;
        return $user;
    }

    private function requestParameters(array $changes = []): array
    {
        return array_merge([
            'server' => 'cloud.plainshow.se',
            'flow' => 'v2',
            'state' => str_repeat('a', 64),
            'requesttoken' => 'valid-test-token',
            'account' => 'tester',
            'client' => 'Desktop sync',
        ], $changes);
    }

    public function test_device_approval_uses_a_fixed_cloud_action_and_checks_the_account(): void
    {
        $this->actingAs($this->account());
        $this->get('/devices/connect?'.http_build_query($this->requestParameters()))
            ->assertOk()
            ->assertSee('https://cloud.plainshow.se/index.php/login/v2/grant')
            ->assertSee('Connect device')
            ->assertSee('valid-test-token');

        $this->get('/devices/connect?'.http_build_query($this->requestParameters(['account' => 'someone-else'])))
            ->assertRedirect('/devices/result?status=account');
    }

    public function test_device_approval_rejects_an_unknown_server(): void
    {
        $this->actingAs($this->account());
        $this->get('/devices/connect?'.http_build_query($this->requestParameters(['server' => 'attacker.example'])))
            ->assertRedirect('/devices/result?status=expired');
    }

    public function test_linked_cloud_account_is_used_for_device_approval(): void
    {
        DB::table('core_linked_accounts')->insert(['user_id' => 42, 'provider' => 'nextcloud', 'external_id' => 'legacy-user']);
        $this->actingAs($this->account());
        $this->get('/devices/connect?'.http_build_query($this->requestParameters(['flow' => 'v1', 'account' => 'legacy-user'])))
            ->assertOk()
            ->assertSee('https://cloud.plainshow.se/index.php/login/flow');
    }
}
