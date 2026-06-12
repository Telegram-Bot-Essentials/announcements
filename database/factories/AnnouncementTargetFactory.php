<?php

namespace TelegramBotEssentials\Announcements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * @extends Factory<AnnouncementTarget>
 */
class AnnouncementTargetFactory extends Factory
{
    protected $model = AnnouncementTarget::class;

    public function configure(): static
    {
        return $this->afterMaking(function (AnnouncementTarget $target) {
            if (! $target->announcement_id) {
                $announcement = Announcement::factory()->create(
                    $target->bot_id ? ['bot_id' => $target->bot_id] : []
                );

                $target->announcement_id = $announcement->id;
                $target->bot_id ??= $announcement->bot_id;
            }

            if (! $target->bot_user_id && $target->bot_id) {
                $target->bot_user_id = $this->createBotUser($target->bot_id)->id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_sent' => fake()->boolean(70),
            'is_deleted' => false,
            'is_forbidden' => false,
            'message_id' => fake()->optional(0.7)->randomNumber(8),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'is_sent' => true,
            'message_id' => fake()->randomNumber(8),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn () => [
            'is_deleted' => true,
        ]);
    }

    public function forbidden(): static
    {
        return $this->state(fn () => [
            'is_forbidden' => true,
            'is_sent' => false,
            'message_id' => null,
        ]);
    }

    private function createBotUser(int $botId): BotUser
    {
        $telegramUser = TelegramUser::factory()->create();

        return BotUser::query()->create([
            'bot_id' => $botId,
            'telegram_user_peer_id' => $telegramUser->peer_id,
        ]);
    }
}
