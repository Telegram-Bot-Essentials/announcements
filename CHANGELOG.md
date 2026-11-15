# Changelog

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/). Until the API
stabilizes at 1.0 a `0.0.x` bump may carry breaking changes.

## [Unreleased]

## [0.0.26] - 2026-11-15

### Added

- Send automatic alerts registered by packages, once an admin switches them on.
- Sweep the alerts every hour, with a repeat period, a cap per user and sending hours.
- Let packages describe audiences, and aim a written announcement at one.
- Let users stop automatic alerts with `/mute` or the key under each one.
- Choose whether a written announcement also goes to users who muted alerts.
- Show the alerts with their delivery counts and a preview in the bot, and as cards in the web panel.
- Keep the announcements alerts made out of the list unless asked for.

### Changed

- Require `telegram-bot-essentials/settings`: what an admin chooses for alerts lives there.
- Stop editing a status message nobody asked for after each batch.

## [0.0.25] - 2026-11-15

### Added

- Fill in `{$key}` placeholders for each recipient of a text announcement.
- Let packages and the app register their own placeholders.
- Offer the recipient's name, username, Telegram ID, join date and last activity.
- List the available placeholders when composing, in the bot and the web panel.
- Refuse a text that uses a placeholder nothing can fill in.
- Warn when a copied or forwarded announcement holds placeholders.

## [0.0.24] - 2026-11-11

### Added

- List announcements, create text ones and send.
- Add the announcements panel plugin.
- Add the announcement view.
- Register the plugin and its views.
- Translate the screens to english.
- Translate the screens to persian.
- Chart an announcement's delivery live.
- Show the delivery chart on the announcement.
- Add empty-list messages.
- Group, date and tidy the panel screens.
- Show when an announcement finished in the viewer's calendar.

### Changed

- Move sending into a service.
- Send from the bot through the service.
- Drop the counts grid from the view.

## [0.0.23] - 2026-11-01

### Added

- `lang/<locale>/abilities.php` labels this package's Gate abilities for
  essence's `abilities()` catalog, so a roles screen (tbe-access) shows
  them by name.

## [0.0.22] - 2026-10-31

### Fixed

- Requires essence 0.19.0-beta.2 or later; 0.19.0-beta.1 still had the
  permission API.

## [0.0.21] - 2026-10-31

### Changed

- Requires essence 0.19. The admin handlers name a Laravel Gate ability
  (`announcements.manage`) instead of a permission, and nothing is registered with essence
  any more: the app's Gate decides who holds it. Essence no longer lets the
  bot owner through by default, so an app grants these with `Gate::before`
  or `Gate::define`.

### Removed

- The permission labels (`lang/*/permissions.php`).

## [0.0.20] - 2026-10-12

### Changed

- Requires essence 0.18. A flow's state is read and written on the webhook's
  conversation (`wHook()->conversation()`) instead of the user, so it stays
  in the chat it started in.

## [0.0.19] - 2026-10-12

Requires essence 0.17, which replaces power levels with named permissions.

### Changed

- The admin screens ask for `announcements.manage` instead of the admin power level.
  The names are registered with essence's permission registry, so a role
  package can grant them separately.

## [0.0.18] - 2026-10-01

### Changed

- Reworded the user-facing English and Persian strings to read more naturally; no keys or placeholders changed.

## [0.0.17] - 2026-09-28

### Changed

- Accepts essence 0.16 alongside 0.15.

## [0.0.16] - 2026-09-27

### Changed

- Requires essence `^0.15` for `tbeLog()->for()`.
- Announcement log messages name the announcement and, for a finished
  campaign, its outcome (`Announcement #4 finished: 120 sent, 3 blocked,
  0 skipped, 1 failed`); a per-user delivery failure is bound to that user.

## [0.0.15] - 2026-09-22

### Changed

- Pagination now uses `TelegramPaginator::addNavigationRow()` instead of
  `makeNavigationButtonsRow()`, so the nav row is skipped entirely on a
  single-page list.

## [0.0.14] - 2026-09-22

### Changed

- Accepts `telegram-bot-essentials/essence` `^0.14` as well as `^0.13`:
  0.14.0 only removed `DoneLimited`/`CannotSetItAsDone`/`HidesDone`, none of
  which this package uses.

## [0.0.13] - 2026-09-20

### Changed

- **BREAKING:** requires `telegram-bot-essentials/essence` `^0.13` (the JSON user
  state and the forms engine). No code change: the package's suite passes
  against essence 0.13.0.

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
