<?php

namespace TelegramBotEssentials\Announcements\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
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
     * @param  array<int>  $targetIds
     */
    public function __construct(
        private readonly WebhookContext $context,
        private readonly array $targetIds
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
        if (! $announcement) {
            return;
        }

        foreach ($targets as $target) {
            $botUser = $target->botUser;

            // Checked here rather than when the targets were built, so that a
            // user who unblocked the bot in the meantime still receives this.
            if (! $botUser?->isReachable()) {
                $target->update(['status' => 'skipped']);

                continue;
            }

            try {
                $message = $announcement->sendTo($botUser->telegramUser->peer_id);

                $target->update([
                    'message_id' => $message->messageId,
                    'status' => 'sent',
                ]);
            } catch (\Throwable $exception) {
                // Only failures Telegram attributes to the user count as
                // forbidden. Everything else (rate limits, outages, markup
                // errors) is recorded as failed instead, because marking a
                // reachable user blocked is now self-reinforcing: they would be
                // skipped from every later announcement as well.
                $status = botUserStatus()->reportFailure($botUser, $exception);

                $target->update([
                    'status' => $status === null ? 'failed' : 'forbidden',
                ]);

                tbeLog('announcements')->debug('Failed to send announcement to user: '.$exception->getMessage(), [
                    'announcement_id' => $announcement->getKey(),
                    'target_id' => $target->getKey(),
                    'peer_id' => $botUser->telegramUser->peer_id,
                    'user_status' => $status,
                ]);
            }
        }

        $hasPending = $announcement->targets()
            ->whereNull('status')
            ->exists();

        if (! $hasPending) {
            $announcement->update(['sent_at' => now()]);

            tbeLog('announcements')->info('Announcement fully sent', [
                'announcement_id' => $announcement->getKey(),
                'sent_count' => $announcement->targets()->where('status', 'sent')->count(),
                'forbidden_count' => $announcement->targets()->where('status', 'forbidden')->count(),
                'skipped_count' => $announcement->targets()->where('status', 'skipped')->count(),
                'failed_count' => $announcement->targets()->where('status', 'failed')->count(),
            ]);
        }

        self::updateProgress($announcement, ! $hasPending);
    }

    /**
     * Update the sending progress on Telegram with throttling to avoid rate limits.
     */
    public static function updateProgress(Announcement $announcement, bool $force = false): void
    {
        $lockKey = "tbe-announcements:progress-update:{$announcement->id}";

        if ($force || Cache::add($lockKey, true, 3)) {
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
