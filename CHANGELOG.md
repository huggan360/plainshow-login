# Changelog

## 1.0.0

- Personal API tokens, made for core's account page over signed requests: service scopes, read only if wanted, 90 days.
- The orange brand, Outfit for text and Source Serif 4 for titles.
- Role rules with wildcards no longer reach core's own capabilities (kit 1.0).

## 0.1.0

- One sign-in for every PlainShow app: OpenID Connect provider on Passport, with RS256 ID tokens that carry a key id, and discovery, JWKS and userinfo endpoints.
- Argon2id passwords, rate limits and per-account lockout, optional TOTP two-factor with recovery codes and replay protection.
- Sign-out everywhere through front-channel logout. Return addresses are limited to PlainShow domains.
- Admin-issued password reset links. Only a hash of the token is stored.
- `plainshow:import-users` brings the blog's accounts across with their ids, hashing their passwords.
- Passport's client and token management routes are not exposed.
