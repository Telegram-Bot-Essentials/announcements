<?php

use TelegramBotEssentials\Announcements\Services\Alerts;
use TelegramBotEssentials\Announcements\Services\Audiences;
use TelegramBotEssentials\Announcements\Services\Placeholders;

if (! function_exists('announcementPlaceholders')) {
    /** The `{$key}` values announcement texts can use; packages add theirs from boot(). */
    function announcementPlaceholders(): Placeholders
    {
        return app(Placeholders::class);
    }
}

if (! function_exists('announcementAudiences')) {
    /** The groups of users announcements and alerts can be aimed at; packages add theirs from boot(). */
    function announcementAudiences(): Audiences
    {
        return app(Audiences::class);
    }
}

if (! function_exists('announcementAlerts')) {
    /** The automatic alerts the bot can send; packages register theirs from boot(). */
    function announcementAlerts(): Alerts
    {
        return app(Alerts::class);
    }
}
