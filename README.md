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

## Placeholders

A text (`html`) announcement can address each recipient by what the bot knows about
them. The admin writes `{$key}`; it is filled in when the message is sent:

```
Hi {$first_name}, you have {$wallet.balance} in your wallet.
```

Built in: `{$name}`, `{$first_name}`, `{$username}`, `{$user_id}`, `{$joined_at}`,
`{$last_interaction}`. The compose prompt in the bot and the web panel list every
placeholder available, and a text using an unknown one is refused. Copies and forwards
re-send Telegram's own message, so they are never filled in.

A package or the app adds its own from a service provider's `boot()`:

```php
use TelegramBotEssentials\Announcements\DTOs\Placeholder;
use TelegramBotEssentials\Essence\Models\BotUser;

if (class_exists(Placeholder::class)) { // only for a package that does not require announcements
    announcementPlaceholders()->add(new Placeholder(
        key: 'wallet.balance',                 // prefix with your package; never rename it
        label: fn () => __('my-package::placeholders.balance'),
        resolve: fn (BotUser $user) => currency()->priceFormat($balanceOf($user)),
        fallback: '0',                         // used when the value is null or empty
        example: '50,000',
        active: fn () => (bool) settings()->get('billing.user_wallet.status'),
        with: [],                              // BotUser relations to eager load per batch
        raw: false,                            // true when the value is Telegram HTML
    ));
}
```

- `resolve` runs on the queue in the **sender's** context: read from the `$user` it is
  given, never from `wHook()->user()` or helpers that act on the current user.
- Values are HTML-escaped unless `raw`. Format them yourself (money, dates).
- A send never fails over a placeholder: a value that throws becomes its fallback, and a
  key whose package was removed or switched off since is sent as an empty string and
  logged once.

## Audiences

A package can describe a group of users as a constraint on the bot's users. An admin can
aim a written announcement at one (and choose whether users who muted automatic messages
are included), and an automatic alert is sent to one.

```php
use Illuminate\Database\Eloquent\Builder;
use TelegramBotEssentials\Announcements\DTOs\Audience;

announcementAudiences()->add(new Audience(
    key: 'wallet.has_credit',                  // prefix with your package; never rename it
    label: fn () => __('my-package::audiences.has_credit'),
    apply: fn (Builder $query) => $query->whereIn('bot_users.id', $walletsWithCredit),
    active: fn () => (bool) settings()->get('billing.user_wallet.status'),
));
```

`apply` runs in the bot's context with no current user. Qualify columns (`bot_users.id`):
other constraints are added to the same query.

## Automatic alerts

An alert is a message the bot sends on its own to the users of an audience: "you have
credit to spend", "you never took your free test". The package that knows the subject
registers it; nothing is sent until an admin switches it on for their bot.

```php
use TelegramBotEssentials\Announcements\DTOs\Alert;
use TelegramBotEssentials\Announcements\DTOs\AlertParam;

announcementAlerts()->register(new Alert(
    key: 'wallet.idle_credit',                 // never rename it: it remembers who got it
    label: fn () => __('my-package::alerts.idle_credit.label'),
    audience: 'wallet.idle_credit',
    text: fn () => __('my-package::alerts.idle_credit.text'), // {$placeholders} and {$alert.<param>}
    params: [new AlertParam('min_balance', fn () => __('...'), default: 50000)],
    repeatDays: 30,                            // 0 sends it once ever
    priority: 20,                              // lowest goes first when a user matches several
    keyboard: fn (BotUser $user) => [[['text' => '🛒', 'callback_data' => '...']]],
));
```

- In a lang file, write the text in single quotes or escape the dollar (`{\$name}`): PHP
  reads `{$name}` inside double quotes as a variable.
- The audience reads its numbers with `announcementAlerts()->param('wallet.idle_credit', 'min_balance')`.
- Every hour `tbe:announcements:sweep-alerts` (scheduled by the package; run Laravel's
  scheduler) finds who is due for each alert that is on and queues it as an announcement
  of its own, so delivery tracking, the chart and retraction work as for any other.
- A user gets an alert once, or again after its repeat period while they still match; at
  most one automatic alert per cap period (24 hours by default) whatever they match; and
  only within the sending hours (10 to 22 by default). A message that failed is tried
  again on the next pass.
- Every alert carries a key to stop them, and `/mute` turns them off and on. One switch
  covers all alerts; muted users are never targeted.
- An admin switches alerts on, rewords them, and tunes their numbers, the repeat period,
  the cap and the sending hours in settings under **Announcements → Automatic alerts**
  (this package requires `telegram-bot-essentials/settings`). The announcements menu has
  an **Automatic alerts** screen with each alert's delivery counts and a preview, and
  the web panel shows a card per alert.

## Documentation

Full documentation — the data model, sending and retraction flows, and the `WebhookContext`
export/import pattern — lives on the Telegram Bot Essentials documentation site under
**Modules → Announcements**.

## License

[MIT](LICENSE).
