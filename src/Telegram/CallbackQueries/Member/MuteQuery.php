<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Member;

use TelegramBotEssentials\Announcements\Services\Mutes;
use TelegramBotEssentials\Announcements\Telegram\Features\Member\MuteFeature;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class MuteQuery extends CallbackQuery
{
    protected string $type = 'ANNMUTE';

    /**
     * From the key under an alert. The alert itself is left as it is: the
     * answer is a new message, which carries the key to turn alerts back on.
     */
    public function mute(): void
    {
        $mutes = app(Mutes::class);
        if (! $mutes->isMuted(wHook()->user())) {
            $mutes->toggle(wHook()->user());
        }

        MuteFeature::status(true)->send();

        $this->answer(__('tbe-announcements::alerts.mute.answers.muted'));
    }

    /** From the key on the status message: changes it, and shows the new state. */
    public function flip(): void
    {
        $muted = app(Mutes::class)->toggle(wHook()->user());

        MuteFeature::status($muted)->update();

        $this->answer(__('tbe-announcements::alerts.mute.answers.'.($muted ? 'muted' : 'unmuted')));
    }
}
