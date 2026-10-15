<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Writes to the platform audit log shared with core. Never blocks sign-in. */
class Audit
{
    public static function record(string $event, ?int $userId = null, ?string $actor = null, array $context = []): void
    {
        try {
            DB::table('audit_events')->insert([
                'user_id' => $userId,
                'actor' => $actor !== null ? mb_substr($actor, 0, 64) : null,
                'source' => 'login',
                'event' => $event,
                'ip_address' => request()?->ip(),
                'context' => $context ? json_encode($context) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $error) {
            Log::warning('Audit write failed', ['event' => $event, 'error' => $error->getMessage()]);
        }
    }
}
