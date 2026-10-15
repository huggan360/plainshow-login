<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `users` and `audit_events` are shared with plainshow-core. Login owns
     * their schema; core reads and updates rows but never migrates them.
     * User ids are kept from the blog's `user` table so existing posts and
     * LaTeX documents keep their owners.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 32)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('first_name', 60)->default('');
            $table->string('last_name', 60)->default('');
            $table->string('password');
            $table->string('role', 16)->default('member')->index();
            $table->string('status', 16)->default('active');
            $table->string('avatar_path')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('pi_username', 32)->nullable()->unique();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('login_password_reset_tokens', function (Blueprint $table) {
            $table->string('username', 32)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('login_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('actor', 64)->nullable();
            $table->string('source', 16);
            $table->string('event', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('login_sessions');
        Schema::dropIfExists('login_password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
