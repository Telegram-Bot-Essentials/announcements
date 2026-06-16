# Telegram Bot Essentials - Announcements

[![Latest Version](https://img.shields.io/github/v/release/Elyar0/tbe-announcements?style=flat-square)](https://github.com/Elyar0/tbe-announcements/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

An elegant, highly optimized announcements module built for the `telegram-bot-essentials/essence` bot ecosystem. This package allows administrators to compose, preview, schedule, dispatch, and retract announcements to bot users while maintaining high performance, safety, and beautiful visual statistics.

---

## Features

- 🚀 **Asynchronous Batch Processing**: Announcements are chunked into smaller batches to prevent database/Redis queue bloat.
- ⚡ **Non-Blocking Dedicated Queue**: Dispatches jobs on a configurable low-priority/announcements queue to keep interactive bot actions instant.
- 📊 **Descriptive UI & Progress Indicators**: Real-time Telegram message updates with beautiful ASCII progress bars (`██████░░░░░ 60%`) and live delivery statistics.
- 🛡️ **Rate Limit Protection**: Throttles Telegram API progress updates using cache locks to avoid triggering rate-limiting errors (`429 Too Many Requests`).
- 📁 **Multiple Delivery Methods**: Supports message rendering in **HTML**, **Copying** existing messages, or **Forwarding** them directly.
- 🎛️ **Target Management**: Eager-loaded data structures with the ability to reload target lists and track delivery statuses (`sent`, `deleted`, `forbidden/blocked`).
- 🏢 **Multi-Tenant Friendly**: Integrates seamlessly with `stancl/tenancy` for SaaS bot installations.

---

## Installation

Install the package via Composer:

```bash
composer require telegram-bot-essentials/announcements
```

After installing, run the database migrations to create the announcements and target tracking tables:

```bash
php artisan migrate
```

---

## Configuration

You can publish the configuration file to customize the default queue name and batch sizes:

```bash
php artisan vendor:publish --tag=tbe-announcements-config
```

This creates a configuration file at `config/tbe-announcements.php`:

```php
return [
    /*
     * The queue connection and name to be used for dispatching announcement jobs.
     */
    'queue' => env('TBE_ANNOUNCEMENTS_QUEUE', 'announcements'),

    /*
     * The number of users to process in a single job batch.
     */
    'batch_size' => (int) env('TBE_ANNOUNCEMENTS_BATCH_SIZE', 100),
];
```

Make sure your queue worker is configured to process the `announcements` queue alongside your default queue:

```bash
php artisan queue:work --queue=default,announcements
```

---

## Usage

The package automatically hooks into the Telegram Bot Essentials bus systems:
- Registers the `ANNOUNCEMENTS` Callback Query processor.
- Registers the `createAnnouncement` and `change` Answer State processors.

### Admin Interfaces

#### 1. Menu & Announcement Details
View all announcements, check details such as delivery methods, timestamps, message bodies, and check detailed live target statistics:
- **Total targets**
- **Sent** / **Pending**
- **Deleted** / **Forbidden** (blocked by user)

#### 2. Previews
Send a mockup of the announcement directly to your personal admin chat before committing to a bulk broadcast to avoid sending mistakes.

#### 3. Broadcasting
Trigger `Start Sending` to initiate queue jobs. The bot will send batches asynchronously and update the progress indicator message in your admin panel using cache-throttled updates.

#### 4. Message Retraction (Deletion)
If you need to recall or retract a sent announcement, choose `Delete Sent Messages` to queue deletion jobs. The bot will delete the message from target users' private chats and show deletion progress.

---

## Translation & Localization

The package supports localization and comes preloaded with English (`en`) and Persian (`fa`) translations. You can publish translations to customize the text styles:

```bash
php artisan vendor:publish --tag=tbe-announcements-translations
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
