<?php

namespace TelegramBotEssentials\Announcements\Services;

use LogicException;
use TelegramBotEssentials\Announcements\Jobs\DeleteAnnouncementJob;
use TelegramBotEssentials\Announcements\Jobs\SendAnnouncementJob;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Support\WebhookContext;

/**
 * What an admin does with an announcement, from the bot or the web panel.
 * Runs in the bot's context (wHook()): the jobs carry it to the queue.
 */
class Broadcasts
{
    /** A text announcement, written in Telegram's HTML. */
    public function createText(BotUser $author, string $label, string $html): Announcement
    {
        return Announcement::query()->create([
            'bot_id' => $author->getAttribute('bot_id'),
            'bot_user_id' => $author->getKey(),
            'method' => 'html',
            'message_text' => $html,
            'label' => $label,
        ]);
    }

    /** Everyone in the bot now; users who join later need another call. */
    public function addAllUsers(Announcement $announcement): int
    {
        $now = now();

        $rows = [];
        foreach (self::ids(BotUser::query()->pluck('id')->all()) as $botUserId) {
            $rows[] = [
                'bot_id' => $announcement->bot_id,
                'announcement_id' => $announcement->id,
                'bot_user_id' => $botUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows === [] ? 0 : AnnouncementTarget::query()->insertOrIgnore($rows);
    }

    /**
     * Queues every target not known to have blocked the bot. With
     * $statusChatId, a message there shows the progress as it goes.
     *
     * @return int how many targets were queued
     */
    public function send(Announcement $announcement, ?int $statusChatId = null): int
    {
        $this->showStatus($announcement, $statusChatId, AnnouncementsFeature::getSendingProgressText($announcement));

        $targetIds = self::ids($announcement->targets()
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'forbidden'))
            ->pluck('id')
            ->all());

        $context = $this->context();
        foreach (array_chunk($targetIds, $this->batchSize()) as $chunk) {
            SendAnnouncementJob::dispatch($context, $chunk);
        }

        return count($targetIds);
    }

    /**
     * Deletes the copies already delivered, from every chat.
     *
     * @return int how many targets were queued
     */
    public function retract(Announcement $announcement, ?int $statusChatId = null): int
    {
        $this->showStatus($announcement, $statusChatId, AnnouncementsFeature::getDeletingProgressText($announcement));

        $targetIds = self::ids($announcement->targets()->where('status', 'sent')->pluck('id')->all());

        $context = $this->context();
        foreach (array_chunk($targetIds, $this->batchSize()) as $chunk) {
            DeleteAnnouncementJob::dispatch($context, $chunk);
        }

        return count($targetIds);
    }

    /**
     * Targets by delivery status; "pending" for those not tried yet.
     *
     * @return array<string, int>
     */
    public function counts(Announcement $announcement): array
    {
        $counts = ['pending' => 0, 'sent' => 0, 'forbidden' => 0, 'skipped' => 0, 'failed' => 0, 'deleted' => 0];

        $rows = $announcement->targets()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $status = is_string($row->status) ? $row->status : 'pending';
            $counts[$status] = ($counts[$status] ?? 0) + (is_numeric($row->total) ? (int) $row->total : 0);
        }

        return $counts;
    }

    private function showStatus(Announcement $announcement, ?int $chatId, string $text): void
    {
        if ($chatId === null) {
            $announcement->update(['action_status_chat_id' => null, 'action_status_message_id' => null]);

            return;
        }

        $statusMessage = wHook()->api()->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        $announcement->update([
            'action_status_chat_id' => $statusMessage->chat->id,
            'action_status_message_id' => $statusMessage->messageId,
        ]);
    }

    /** @return int<1, max> */
    private function batchSize(): int
    {
        $size = config('tbe-announcements.batch_size', 100);

        return is_numeric($size) ? max(1, (int) $size) : 100;
    }

    /** The jobs replay it: they send as this bot. */
    private function context(): WebhookContext
    {
        $context = wHook()->exportContext();
        if ($context === null) {
            throw new LogicException('Announcements are sent from a bot context: a webhook, a job or the web panel.');
        }

        return $context;
    }

    /**
     * @param  array<mixed>  $values
     * @return list<int>
     */
    private static function ids(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }
}
