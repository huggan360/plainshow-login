<?php

namespace App\Support;

use App\Models\OAuthClient;

/** Which app someone is signing in to, read from the pending authorize request. */
class Destination
{
    private const GLOW = ['core' => 'blue', 'blog' => 'warm', 'latex' => 'green', 'web' => 'violet', 'cloud' => 'cyan'];

    /** @return array{name: string, glow: string}|null */
    public static function fromIntended(): ?array
    {
        $intended = session('url.intended');
        if (! is_string($intended) || ! str_contains($intended, '/oauth/authorize')) {
            return null;
        }
        parse_str((string) parse_url($intended, PHP_URL_QUERY), $query);
        $client = isset($query['client_id']) && is_string($query['client_id']) ? OAuthClient::find($query['client_id']) : null;
        if (! $client) {
            return null;
        }

        return ['name' => $client->name, 'glow' => self::GLOW[$client->extension_id] ?? 'blue'];
    }
}
