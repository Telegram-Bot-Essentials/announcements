<?php

namespace TelegramBotEssentials\Announcements\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Essence\Support\WebhookContext;

class SendAnnouncementJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly WebhookContext     $context,
        private readonly AnnouncementTarget $announcementTarget
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        wHook()->importContext($this->context);
        $announcement = $this->announcementTarget->announcement;

        try {
            $message = $announcement->sendTo($this->announcementTarget->botUser->telegramUser->peer_id);

            $this->announcementTarget->update([
                'message_id' => $message->messageId,
                'status' => 'sent',
            ]);
        } catch (\Throwable $exception) {
            $this->announcementTarget->update([
                'status' => 'forbidden',
            ]);
        }

        wHook()->api()->editMessageText([
            'chat_id' => $announcement->action_status_chat_id,
            'message_id' => $announcement->action_status_message_id,
            'text' => $announcement->targets()->where('status', 'sent')->count() . '/' . $announcement->targets()->count() . ' | ' . $announcement->targets()->where('status', 'forbidden')->count(),
        ]);
    }
}
