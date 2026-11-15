<?php

namespace TelegramBotEssentials\Announcements\Telegram\Features\Member;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class MuteFeature
{
    public static string $type = 'ANNMUTE';

    /** Says whether the user gets automatic alerts, with a key to change it. */
    public static function status(bool $muted): TelegramResponse
    {
        $replyMarkup = Keyboard::make()->inline()->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::alerts.mute.keys.'.($muted ? 'unmute' : 'mute')),
                'callback_data' => encodeCallback(self::$type, 'flip'),
            ]),
        ]);

        return new TelegramResponse(
            text: __('tbe-announcements::alerts.mute.text.'.($muted ? 'muted' : 'unmuted')),
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    /**
     * The key under every automatic alert.
     *
     * @return array<string, mixed>
     */
    public static function muteButton(): array
    {
        return [
            'text' => __('tbe-announcements::alerts.mute.keys.mute'),
            'callback_data' => encodeCallback(self::$type, 'mute'),
        ];
    }
}
