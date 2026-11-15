<?php

namespace TelegramBotEssentials\Announcements\Services;

use TelegramBotEssentials\Announcements\Models\AnnouncementMute;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * Who asked not to get automatic alerts. One switch per user and bot covers
 * every alert; an admin's own announcement may still be sent to them.
 */
class Mutes
{
    public function isMuted(BotUser $user): bool
    {
        return AnnouncementMute::query()->where('bot_user_id', $user->getKey())->exists();
    }

    /** @return bool whether the user is muted now */
    public function toggle(BotUser $user): bool
    {
        if (AnnouncementMute::query()->where('bot_user_id', $user->getKey())->delete() > 0) {
            return false;
        }

        AnnouncementMute::query()->firstOrCreate([
            'bot_id' => $user->getAttribute('bot_id'),
            'bot_user_id' => $user->getKey(),
        ]);

        return true;
    }
}
