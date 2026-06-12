<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class AnnouncementsQuery extends CallbackQuery
{
    protected string $type = 'ANNOUNCEMENTS';
    protected int $perm = Roles::ADMIN->value;

    public function start(): void
    {
        // Logic to execute
    }
}
