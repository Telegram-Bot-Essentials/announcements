<?php

namespace TelegramBotEssentials\Announcements\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class DeleteAnnouncementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param WebhookContext $context
     * @param array<int> $targetIds
     */
    public function __construct(
        private readonly WebhookContext $context,
        private readonly array          $targetIds
    ) {
        $this->queue = config('tbe-announcements.queue', 'announcements');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        wHook()->importContext($this->context);

        if (empty($this->targetIds)) {
            return;
        }

        $targets = AnnouncementTarget::with(['announcement', 'botUser.telegramUser'])->whereIn('id', $this->targetIds)->get();
        if ($targets->isEmpty()) {
            return;
        }

        $announcement = $targets->first()->announcement;
        if (!$announcement) {
            return;
        }

        foreach ($targets as $target) {
            try {
                wHook()->api()->deleteMessage([
                    'chat_id' => $target->botUser->telegramUser->peer_id,
                    'message_id' => $target->message_id,
                ]);

                $target->update([
                    'message_id' => null,
                    'status' => 'deleted',
                ]);
            } catch (\Throwable $exception) {
                $target->update([
                    'status' => 'forbidden',
                ]);
            }
        }

        $hasPending = $announcement->targets()
            ->where('status', 'sent')
            ->exists();

        self::updateProgress($announcement, !$hasPending);
    }

    /**
     * Update the deleting progress on Telegram with throttling to avoid rate limits.
     */
    public static function updateProgress(Announcement $announcement, bool $force = false): void
    {
        $lockKey = "tbe-announcements:delete-progress-update:{$announcement->id}";

        if ($force || \Illuminate\Support\Facades\Cache::add($lockKey, true, 3)) {
            try {
                wHook()->api()->editMessageText([
                    'chat_id' => $announcement->action_status_chat_id,
                    'message_id' => $announcement->action_status_message_id,
                    'text' => AnnouncementsFeature::getDeletingProgressText($announcement),
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Throwable $e) {
                // Ignore API failures or unchanged message errors
            }
        }
    }
}
