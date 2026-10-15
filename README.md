# PlainShow Login

`login.plainshow.se`: the single sign-on service for PlainShow. Every app (core, blog, LaTeX, web, Nextcloud) is an OpenID Connect client of this server.

| Endpoint | Purpose |
|---|---|
| `/login`, `/two-factor` | Sign in, with optional TOTP |
| `/logout?return=` | Sign out of every app (front-channel logout) |
| `/reset/{token}?u=` | Set a password from an admin-issued link |
| `/oauth/authorize`, `/oauth/token` | Authorization code flow with PKCE |
| `/oauth/jwks`, `/.well-known/openid-configuration`, `/oauth/userinfo` | Keys, discovery, profile |

Tokens carry the standard OIDC claims plus `ps_role`, `ps_services`, `ps_caps` and `ps_pi_user`, worked out with `PlainShow\Kit\Access` from the tables plainshow-core manages.

## Database

Shares the `plainshow` MariaDB database with core. Login owns `users` and `audit_events`; its internal tables are prefixed `login_` and the Passport tables keep their `oauth_` names.

## Commands

```sh
php artisan plainshow:client blog "Blog" https://blog.plainshow.se/auth/callback --logout=https://blog.plainshow.se/auth/frontchannel
php artisan plainshow:import-users --dry-run
php artisan plainshow:reset-link USERNAME
```

## Checks

```sh
php artisan serve --port=8098   # with APP_URL=http://127.0.0.1:8098 SESSION_SECURE_COOKIE=false
php tests/e2e/oidc-flow.php http://127.0.0.1:8098
```
