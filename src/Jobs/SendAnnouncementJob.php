<?php

namespace TelegramBotEssentials\Announcements\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class SendAnnouncementJob implements ShouldQueue
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
                $message = $announcement->sendTo($target->botUser->telegramUser->peer_id);

                $target->update([
                    'message_id' => $message->messageId,
                    'status' => 'sent',
                ]);
            } catch (\Throwable $exception) {
                $target->update([
                    'status' => 'forbidden',
                ]);
                tbeLog('announcements')->debug('Failed to send announcement to user: ' . $exception->getMessage(), [
                    'announcement_id' => $announcement->getKey(),
                    'target_id' => $target->getKey(),
                    'peer_id' => $target->botUser->telegramUser->peer_id,
                ]);
            }
        }

        $hasPending = $announcement->targets()
            ->whereNull('status')
            ->exists();

        if (!$hasPending) {
            $announcement->update(['sent_at' => now()]);

            tbeLog('announcements')->info('Announcement fully sent', [
                'announcement_id' => $announcement->getKey(),
                'sent_count' => $announcement->targets()->where('status', 'sent')->count(),
                'forbidden_count' => $announcement->targets()->where('status', 'forbidden')->count(),
            ]);
        }

        self::updateProgress($announcement, !$hasPending);
    }

    /**
     * Update the sending progress on Telegram with throttling to avoid rate limits.
     */
    public static function updateProgress(Announcement $announcement, bool $force = false): void
    {
        $lockKey = "tbe-announcements:progress-update:{$announcement->id}";

        if ($force || \Illuminate\Support\Facades\Cache::add($lockKey, true, 3)) {
            try {
                wHook()->api()->editMessageText([
                    'chat_id' => $announcement->action_status_chat_id,
                    'message_id' => $announcement->action_status_message_id,
                    'text' => AnnouncementsFeature::getSendingProgressText($announcement),
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Throwable $e) {
                // Ignore API failures or unchanged message errors
            }
        }
    }
}
