<?php

namespace Tests\Feature;

use App\Entities\IdentityEntity;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GroupClaimsTest extends TestCase
{
    public function test_cloud_claim_contains_managed_groups(): void
    {
        Schema::create('core_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('core_group_members', function (Blueprint $table) {
            $table->integer('group_id');
            $table->integer('user_id');
        });
        DB::table('core_groups')->insert(['id' => 3, 'name' => 'Family']);
        DB::table('core_group_members')->insert(['group_id' => 3, 'user_id' => 42]);
        $user = new User(['username' => 'tester', 'role' => 'admin']);
        $user->id = 42;
        $user->status = 'active';

        $this->assertSame([['gid' => 'ps-3', 'displayName' => 'Family']], IdentityEntity::claimsFor($user)['ps_groups']);
    }
}
