<?php

namespace TelegramBotEssentials\Announcements\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;
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
        private readonly WebhookContext|int $context,
        private readonly array $targetIds
    ) {
        $this->queue = config('tbe-announcements.queue', 'announcements');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // A bot id instead of a context: an automatic alert, sent as the bot
        // alone, with no admin behind it.
        if (is_int($this->context)) {
            $bot = Bot::query()->find($this->context);
            if ($bot instanceof Bot) {
                wHook()->runInContext($bot, null, null, null, fn () => $this->deliver());
            }

            return;
        }

        wHook()->importContext($this->context);

        $this->deliver();
    }

    private function deliver(): void
    {
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

        if ($announcement instanceof Announcement && $announcement->usesPlaceholders()) {
            $this->preparePlaceholders($announcement, $targets);
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
                $message = $announcement->sendTo($botUser->telegramUser->peer_id, $botUser);

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

                tbeLog('announcements')->for($botUser)->debug('Announcement #{announcement_id} not delivered ({user_status}): '.$exception->getMessage(), [
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

            tbeLog('announcements')->info('Announcement #{announcement_id} finished: {sent_count} sent, {forbidden_count} blocked, {skipped_count} skipped, {failed_count} failed', [
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
     * Loads what the text's placeholders read for the whole chunk at once,
     * and says once per announcement which keys nothing can fill in any more
     * (their package was removed or switched off after the text was written).
     *
     * @param  Collection<int, AnnouncementTarget>  $targets
     */
    private function preparePlaceholders(Announcement $announcement, Collection $targets): void
    {
        $relations = announcementPlaceholders()->relationsFor($announcement->message_text);
        if ($relations !== []) {
            $users = new Collection;
            foreach ($targets as $target) {
                if ($target->botUser instanceof BotUser) {
                    $users->push($target->botUser);
                }
            }
            $users->loadMissing($relations);
        }

        $unknown = announcementPlaceholders()->unknownIn($announcement->message_text);
        if ($unknown === []) {
            return;
        }

        $id = $announcement->getKey();
        if (Cache::add('tbe-announcements:unknown-placeholders:'.(is_scalar($id) ? $id : ''), true, now()->addHour())) {
            tbeLog('announcements')->warning('Announcement #{announcement_id} uses placeholders nothing can fill in, sent as empty: {keys}', [
                'announcement_id' => $announcement->getKey(),
                'keys' => implode(', ', $unknown),
            ]);
        }
    }

    /**
     * Update the sending progress on Telegram with throttling to avoid rate limits.
     */
    public static function updateProgress(Announcement $announcement, bool $force = false): void
    {
        // Sent from the web panel or by an alert: nobody is watching a status message.
        if ($announcement->action_status_message_id === null) {
            return;
        }

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
