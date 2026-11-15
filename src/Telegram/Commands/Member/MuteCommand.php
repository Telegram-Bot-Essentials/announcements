<?php

namespace TelegramBotEssentials\Announcements\Telegram\Commands\Member;

use TelegramBotEssentials\Announcements\Services\Mutes;
use TelegramBotEssentials\Announcements\Telegram\Features\Member\MuteFeature;
use TelegramBotEssentials\Essence\Enums\ChatScope;
use TelegramBotEssentials\Essence\Telegram\Commands\Command;

/** /mute turns the bot's automatic alerts off for the user, and on again when sent a second time. */
class MuteCommand extends Command
{
    protected string $name = 'mute';

    protected ChatScope $chatScope = ChatScope::Private;

    protected function description(): string
    {
        return __('tbe-announcements::alerts.mute.command_description');
    }

    public function handle(): ?bool
    {
        MuteFeature::status(app(Mutes::class)->toggle(wHook()->user()))->send();

        return true;
    }
}
