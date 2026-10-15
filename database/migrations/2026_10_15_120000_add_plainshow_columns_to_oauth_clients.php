<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('extension_id', 32)->nullable()->unique()->after('name');
            $table->string('frontchannel_logout_uri')->nullable()->after('redirect');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['extension_id', 'frontchannel_logout_uri']);
        });
    }
};
