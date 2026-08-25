<?php

namespace TelegramBotEssentials\Announcements\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class AnnouncementsKey extends ReplyKey
{
    protected int $perm = Roles::ADMIN->value;

    protected function text(): string
    {
        return __('tbe-announcements::announcements.reply_key');
    }

    protected function response(): string
    {
        return __('tbe-announcements::announcements.main.answers.menuLoaded');
    }

    public function handle(): void
    {
        AnnouncementsFeature::menu()->send();
    }
}
