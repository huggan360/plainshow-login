# Changelog

## v1.6.1

- The backdrop's light is as gentle in every theme colour as in orange (kit 1.13.1).

## v1.6.0

- The sign-in pages follow the theme colour chosen in the dashboard (kit 1.13): Bree Serif titles and dark grey text in the light theme.

## v1.5.0

- Every address comes from the platform file (kit 1.12): where people may return to, the apps the sign-out page reaches, Cloud's device sign-in and sign-out. The staging Cloud is gone from the code.

## v1.4.2

- Background: large soft orange blobs that fade in, drift and fade out, lighting only the glyphs (kit 1.10).

## v1.4.1

- New background: embers from orange to yellow that move away from the pointer, so you can chase them (kit 1.9).

## v1.4.0

- English, Dutch and Swedish: the browser's language by default, or chosen in the account menu (the choice follows you to every PlainShow site).

## v1.3.0

- PlainShow's error pages.
- AGENTS.md for developers.

## 1.1.4

- Refuse Cloud and Web sign-in when no corresponding account is linked; device approval follows the same rule.

## 1.1.3

- Device connection approval and results use the PlainShow sign-in design for Cloud phone and desktop apps.
- Cloud joins shared sign-out with session revocation by the Login server.

## 1.1.0

- The sign-in page has the PlainShow glyph backdrop behind it, and glass frames (kit 1.3).

## 1.0.2

- plainshow-kit 1.1.

## 1.0.1

- The sign-in page is plain: no grid and no gradient behind it.

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
