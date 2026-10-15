<?php

namespace App\Models;

use Laravel\Passport\Client;

/**
 * Every client is a PlainShow app, so nobody is asked to approve them.
 * `extension_id` ties a client to the extension it signs in to, and
 * `frontchannel_logout_uri` is loaded when someone signs out everywhere.
 */
class OAuthClient extends Client
{
    public function skipsAuthorization(): bool
    {
        return true;
    }
}
