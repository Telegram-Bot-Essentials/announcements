# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

## [0.0.12] - 2026-09-01

### Changed

- **BREAKING:** requires `telegram-bot-essentials/essence` `^0.12`. Handlers
  are locale-lazy and resume paths use `StateAnswer::requireMessageMeta()`.

### Added

- `AnnouncementTarget.status` gains `skipped` (user already known
  unreachable — no API call spent) and `failed` (transient error: rate
  limit, outage, markup — reachability left untouched), alongside `sent` /
  `forbidden`. Reachability is checked per target at send time, and send
  outcomes feed back through essence's `botUserStatus()` (0.0.11).
- Pest test suite, Laravel Pint, Larastan (level max), GitHub Actions CI,
  `LICENSE` (MIT) and this changelog.

### Fixed

- Target-list pagination callbacks (0.0.11).
- `sent_at` is set when the whole broadcast finishes; dropped an unused
  `deleted_at` column (0.0.11).
