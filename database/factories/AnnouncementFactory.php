<?php

namespace TelegramBotEssentials\Announcements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Announcement $announcement) {
            if ($announcement->bot_id && $announcement->bot_user_id) {
                return;
            }

            if (! $announcement->bot_id) {
                $announcement->bot_id = $this->createBot()->id;
            }

            if (! $announcement->bot_user_id) {
                $announcement->bot_user_id = $this->createBotUser($announcement->bot_id)->id;
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message' => fake()->paragraph(),
            'sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'deleted_at' => now()->addYear(),
        ];
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => fake()->dateTimeBetween($attributes['sent_at'], 'now'),
        ]);
    }

    public function hasTargets(int $count = 3): static
    {
        return $this->has(
            AnnouncementTarget::factory()
                ->count($count)
                ->state(fn (array $attributes, Announcement $announcement) => [
                    'bot_id' => $announcement->bot_id,
                ]),
            'targets'
        );
    }

    private function createBot(): Bot
    {
        $owner = TelegramUser::factory()->create();

        return Bot::query()->create([
            'bot_token' => fake()->unique()->numerify('##########'),
            'unique_id' => Uuid::uuid4()->toString(),
            'secret_token' => fake()->unique()->numerify('##########'),
            'bot_owner_peer_id' => $owner->peer_id,
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
