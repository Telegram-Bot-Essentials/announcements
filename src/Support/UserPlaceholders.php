<?php

namespace TelegramBotEssentials\Announcements\Support;

use Carbon\CarbonInterface;
use Closure;
use TelegramBotEssentials\Announcements\DTOs\Placeholder;
use TelegramBotEssentials\Announcements\Services\Placeholders;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * The placeholders every bot has: what Telegram and the bot itself know
 * about a user. Other packages add their own.
 */
class UserPlaceholders
{
    public static function register(Placeholders $placeholders): void
    {
        $placeholders
            ->add(new Placeholder(
                key: 'name',
                label: self::label('name'),
                resolve: fn (BotUser $user) => self::telegram($user, 'full_name'),
                example: 'Sara Ahmadi',
                with: ['telegramUser'],
            ))
            ->add(new Placeholder(
                key: 'first_name',
                label: self::label('first_name'),
                resolve: fn (BotUser $user) => self::telegram($user, 'first_name'),
                example: 'Sara',
                with: ['telegramUser'],
            ))
            ->add(new Placeholder(
                key: 'username',
                label: self::label('username'),
                resolve: function (BotUser $user): ?string {
                    $username = self::telegram($user, 'username');

                    return $username === null || $username === '' ? null : '@'.$username;
                },
                example: '@sara',
                with: ['telegramUser'],
            ))
            ->add(new Placeholder(
                key: 'user_id',
                label: self::label('user_id'),
                resolve: fn (BotUser $user) => self::telegram($user, 'peer_id'),
                example: '123456789',
                with: ['telegramUser'],
            ))
            ->add(new Placeholder(
                key: 'joined_at',
                label: self::label('joined_at'),
                resolve: fn (BotUser $user) => self::date($user, 'created_at')?->toDateString(),
                example: '2026-01-15',
            ))
            ->add(new Placeholder(
                key: 'last_interaction',
                label: self::label('last_interaction'),
                resolve: fn (BotUser $user) => self::date($user, 'last_interaction')?->diffForHumans(),
                example: fn () => now()->subDays(3)->diffForHumans(),
            ));
    }

    /** @return Closure(): string */
    private static function label(string $key): Closure
    {
        return function () use ($key): string {
            $label = __('tbe-announcements::announcements.placeholders.'.$key);

            return is_string($label) ? $label : $key;
        };
    }

    private static function telegram(BotUser $user, string $attribute): ?string
    {
        $telegramUser = $user->telegramUser;
        $value = $telegramUser instanceof TelegramUser ? $telegramUser->getAttribute($attribute) : null;

        return is_scalar($value) ? (string) $value : null;
    }

    private static function date(BotUser $user, string $attribute): ?CarbonInterface
    {
        $value = $user->getAttribute($attribute);

        return $value instanceof CarbonInterface ? $value : null;
    }
}
