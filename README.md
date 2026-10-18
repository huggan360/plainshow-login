# PlainShow Login

`login.plainshow.se`: the single sign-on service for PlainShow. Every app (core, blog, LaTeX, web, Nextcloud) is an OpenID Connect client of this server.

| Endpoint | Purpose |
|---|---|
| `/login`, `/two-factor` | Sign in, with optional TOTP |
| `/logout?return=` | Sign out of every app and revoke Cloud browser sessions |
| `/devices/connect`, `/devices/result` | Approve Cloud phone and desktop connections with PlainShow sign-in |
| `/reset/{token}?u=` | Set a password from an admin-issued link |
| `/oauth/authorize`, `/oauth/token` | Authorization code flow with PKCE |
| `/oauth/jwks`, `/.well-known/openid-configuration`, `/oauth/userinfo` | Keys, discovery, profile |
| `/internal/tokens` | Personal API tokens, for core's account page only (signed requests) |

Tokens carry the standard OIDC claims plus `ps_role`, `ps_services`, `ps_caps` and `ps_pi_user`, worked out with `PlainShow\Kit\Access` from the tables plainshow-core manages.

## Personal API tokens

Login holds the signing keys, so it makes personal tokens. Core asks for them over requests signed with `PlainShow\Kit\Signed` and the secret the two share (`PLAINSHOW_INTERNAL_SECRET`). Tokens carry service scopes (`core`, `blog`, `split`, `latex`, optionally `read-only`) and last 90 days. The personal access client is made once with `php artisan passport:client --personal`; its id and secret go in `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and `_SECRET`.

## Database

Shares the `plainshow` MariaDB database with core. Login owns `users` and `audit_events`; its internal tables are prefixed `login_` and the Passport tables keep their `oauth_` names.

## Commands

```sh
php artisan plainshow:client blog "Blog" https://blog.plainshow.se/auth/callback --logout=https://blog.plainshow.se/auth/frontchannel
php artisan plainshow:import-users --dry-run
php artisan plainshow:reset-link USERNAME
```

## Release

```sh
bash ../core/ops/deploy.sh login "$PWD" [tag]   # or install it from Admin, Updates in core
```

## Checks

```sh
php artisan serve --port=8098   # with APP_URL=http://127.0.0.1:8098 SESSION_SECURE_COOKIE=false
php tests/e2e/oidc-flow.php http://127.0.0.1:8098
```
