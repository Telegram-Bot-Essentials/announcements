# Telegram Bot Essentials — Announcements

[![Latest Version](https://img.shields.io/packagist/v/telegram-bot-essentials/announcements.svg)](https://packagist.org/packages/telegram-bot-essentials/announcements)
[![tests](https://github.com/Telegram-Bot-Essentials/announcements/actions/workflows/tests.yml/badge.svg)](https://github.com/Telegram-Bot-Essentials/announcements/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Lets admins compose a message once and broadcast it to every bot user, or a filtered
subset, on top of
[`telegram-bot-essentials/essence`](https://github.com/Telegram-Bot-Essentials/essence).
Broadcasts run with live progress, per-target delivery tracking, and can be retracted
(deleted) afterwards — all without blocking the bot's normal interactive traffic.

## Installation

```bash
composer require telegram-bot-essentials/announcements
php artisan migrate

# optional — change the queue name or batch size
php artisan vendor:publish --tag=tbe-announcements-config
```

Run a worker that processes the dedicated queue alongside your default one:

```bash
php artisan queue:work --queue=default,announcements
```

Announcements deliberately use their own low-priority queue so a broadcast to thousands of
users can't starve interactive responses sharing the worker pool.

## How it works

- Three delivery methods: `html` (freeform composed text), `copy` (re-send with no
  "Forwarded from" tag), `forward` (keep the tag).
- Targets are chunked into `batch_size` groups; one queued `SendAnnouncementJob` per chunk
  imports the `WebhookContext` across the queue boundary.
- Reachability is re-checked **per target at send time** — a user who unblocked the bot
  still gets the message; a known-unreachable user is `skipped` with no API call spent.
- Per-target status: `sent` / `skipped` / `forbidden` (user's fault — feeds
  `botUserStatus()`) / `failed` (transient — reachability untouched), then `deleted` after
  retraction.
- The live progress message is edited with an ASCII progress bar, throttled via a 3-second
  cache lock so concurrent job completions don't hit a rate limit.

## Documentation

Full documentation — the data model, sending and retraction flows, and the `WebhookContext`
export/import pattern — lives on the Telegram Bot Essentials documentation site under
**Modules → Announcements**.

## License

[MIT](LICENSE).
