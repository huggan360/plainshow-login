# Changelog

## 0.1.0

- One sign-in for every PlainShow app: OpenID Connect provider on Passport, with RS256 ID tokens that carry a key id, and discovery, JWKS and userinfo endpoints.
- Argon2id passwords, rate limits and per-account lockout, optional TOTP two-factor with recovery codes and replay protection.
- Sign-out everywhere through front-channel logout. Return addresses are limited to PlainShow domains.
- Admin-issued password reset links. Only a hash of the token is stored.
- `plainshow:import-users` brings the blog's accounts across with their ids, hashing their passwords.
- Passport's client and token management routes are not exposed.
