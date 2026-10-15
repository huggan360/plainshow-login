<?php

namespace App\Console\Commands;

use App\Models\OAuthClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Creates or updates the OAuth client for one PlainShow app. A new secret is
 * printed once; existing clients keep theirs unless --rotate is given.
 */
class RegisterClient extends Command
{
    protected $signature = 'plainshow:client
        {extension : Extension id, for example core, blog, latex, web or cloud}
        {name : Name shown on the sign-in page}
        {redirect* : Allowed redirect URIs}
        {--logout= : Front-channel logout URI}
        {--rotate : Issue a new secret for an existing client}';

    protected $description = 'Register or update the sign-in client for a PlainShow app';

    public function handle(): int
    {
        foreach ($this->argument('redirect') as $uri) {
            if (! str_starts_with($uri, 'https://')) {
                $this->error("Redirect URIs must use HTTPS: $uri");

                return self::FAILURE;
            }
        }

        $client = OAuthClient::where('extension_id', $this->argument('extension'))->first();
        $secret = null;
        if (! $client) {
            $client = new OAuthClient;
            $client->forceFill([
                'extension_id' => $this->argument('extension'),
                'personal_access_client' => false,
                'password_client' => false,
                'revoked' => false,
                'provider' => 'users',
            ]);
            $secret = Str::random(48);
        } elseif ($this->option('rotate')) {
            $secret = Str::random(48);
        }

        $client->forceFill([
            'name' => $this->argument('name'),
            'redirect' => implode(',', $this->argument('redirect')),
            'frontchannel_logout_uri' => $this->option('logout'),
        ]);
        if ($secret !== null) {
            $client->secret = $secret;
        }
        $client->save();

        $this->line('client_id='.$client->getKey());
        if ($secret !== null) {
            $this->line('client_secret='.$secret);
            $this->warn('Store the secret now. Only a hash is kept.');
        }

        return self::SUCCESS;
    }
}
