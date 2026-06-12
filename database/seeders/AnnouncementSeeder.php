<?php

namespace TelegramBotEssentials\Announcements\Database\Seeders;

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\TelegramUser;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bot = Bot::query()->first() ?? $this->createBot();

        $creator = BotUser::query()
            ->where('bot_id', $bot->id)
            ->first() ?? $this->createBotUser($bot->id);

        $recipients = collect(range(1, 8))
            ->map(fn () => $this->createBotUser($bot->id));

        Announcement::factory()
            ->count(3)
            ->for($bot)
            ->for($creator, 'botUser')
            ->create()
            ->each(function (Announcement $announcement) use ($bot, $recipients) {
                $recipients->random(rand(3, 6))->each(function (BotUser $recipient) use ($announcement, $bot) {
                    AnnouncementTarget::factory()
                        ->for($bot)
                        ->for($announcement)
                        ->for($recipient, 'botUser')
                        ->create();
                });
            });

        Announcement::factory()
            ->count(2)
            ->deleted()
            ->for($bot)
            ->for($creator, 'botUser')
            ->hasTargets(rand(2, 5))
            ->create();
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
