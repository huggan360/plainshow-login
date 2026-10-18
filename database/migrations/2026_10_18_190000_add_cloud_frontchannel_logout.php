<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('oauth_clients')->where('extension_id', 'cloud')->update([
            'frontchannel_logout_uri' => 'https://cloud.plainshow.se/index.php/apps/plainshow/auth/frontchannel',
        ]);
    }

    public function down(): void
    {
        DB::table('oauth_clients')->where('extension_id', 'cloud')->update(['frontchannel_logout_uri' => null]);
    }
};
