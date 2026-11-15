<?php

use TelegramBotEssentials\Announcements\Services\Placeholders;

if (! function_exists('announcementPlaceholders')) {
    /** The `{$key}` values announcement texts can use; packages add theirs from boot(). */
    function announcementPlaceholders(): Placeholders
    {
        return app(Placeholders::class);
    }
}
