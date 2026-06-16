<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin;

use SebastianBergmann\CodeCoverage\Test\Target\Target;
use TelegramBotEssentials\Announcements\Jobs\DeleteAnnouncementJob;
use TelegramBotEssentials\Announcements\Jobs\SendAnnouncementJob;
use Throwable;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;

class AnnouncementsQuery extends CallbackQuery
{
    protected string $type = 'ANNOUNCEMENTS';
    protected int $perm = Roles::ADMIN->value;

    /**
     * @throws InvalidPageNumber
     * @throws TelegramSDKException
     */
    public function start(int $page = 1, int $currentPage = 0): void
    {
        AnnouncementsFeature::menu($page, $currentPage)->update();
    }

    public function createAnnouncement(int $lastPage = 1): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.creatingAnnouncement'));
        wHook()->user()->changeState(encodeAnswerState($this->type, 'createAnnouncement', [
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.sendMessagePrompt'),
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);

        $this->answer(__('tbe-announcements::announcements.main.answers.creatingAnnouncement'));
    }

    /**
     * @throws TelegramSDKException
     */
    function show(Announcement $announcement, int $lastPage = 1): void
    {
        AnnouncementsFeature::show($announcement, $lastPage)->update();
    }

    function preview(Announcement $announcement): void
    {
        $announcement->sendTo(wHook()->peerId());

        $this->answer(__('tbe-announcements::announcements.main.answers.previewSent'));
    }

    function change(Announcement $announcement, string $target, int $lastPage = 1): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        wHook()->user()->changeState(encodeAnswerState($this->type, 'change', [
            'announcement' => $announcement->id,
            'target' => $target,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));
        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.changingField', [
            'field' => __('tbe-announcements::announcements.main.fields.' . $target),
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.enterField', [
                'field' => __('tbe-announcements::announcements.main.fields.' . $target),
            ]),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    function setMessage(Announcement $announcement, int $lastPage = 1): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();

        wHook()->user()->changeState(encodeAnswerState($this->type, 'setMessage', [
            'announcement' => $announcement->id,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));

        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.settingMessage'));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.sendMessagePrompt'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    function changeMethod(Announcement $announcement, string $method, int $lastPage = 1): void
    {
        $methods = ['html', 'copy', 'forward'];
        $announcement->method = nextInArray($methods, $method);
        $announcement->save();

        AnnouncementsFeature::show($announcement, $lastPage)
            ->answer(__('tbe-announcements::announcements.main.answers.methodChanged', [
                'method' => AnnouncementsFeature::methodLabel($announcement->method),
            ]))
            ->update();
    }

    function sendingAnnouncement(Announcement $announcement, int $page = 1, int $currentPage = 0): void
    {
        AnnouncementsFeature::sendingAnnouncement($announcement, $page, $currentPage)->update();
    }

    function reloadTargetUsers(Announcement $announcement, int $page = 1): void
    {
        $now = now();

        $rows = BotUser::query()
            ->pluck('id')
            ->map(fn(int $botUserId) => [
                'bot_id' => $announcement->bot_id,
                'announcement_id' => $announcement->id,
                'bot_user_id' => $botUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            AnnouncementTarget::query()->insertOrIgnore($rows);
        }

        AnnouncementsFeature::sendingAnnouncement($announcement, $page)->update();
    }

    function announcementTargetSend(AnnouncementTarget $announcementTarget, int $page)
    {
        try {
            $message = $announcementTarget->announcement->sendTo($announcementTarget->botUser->telegramUser->peer_id);
            $announcementTarget->update([
                'message_id' => $message->messageId,
                'status' => 'sent',
            ]);

            $this->answer(__('tbe-announcements::announcements.main.answers.targetSent', [
                'user' => $announcementTarget->botUser->telegramUser->full_name,
            ]));
        } catch (Throwable) {
            $announcementTarget->update([
                'status' => 'forbidden',
            ]);

            $this->answer(__('tbe-announcements::announcements.main.answers.targetForbidden', [
                'user' => $announcementTarget->botUser->telegramUser->full_name,
            ]));
        }

        AnnouncementsFeature::sendingAnnouncement($announcementTarget->announcement, $page)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function announcementTargetDelete(AnnouncementTarget $announcementTarget, int $page)
    {
        try {
            wHook()->api()->deleteMessage([
                'chat_id' => $announcementTarget->botUser->telegramUser->peer_id,
                'message_id' => $announcementTarget->message_id,
            ]);
            $announcementTarget->update([
                'message_id' => null,
                'status' => 'deleted',
            ]);

            $this->answer(__('tbe-announcements::announcements.main.answers.targetDeleted', [
                'user' => $announcementTarget->botUser->telegramUser->full_name,
            ]));
        } catch (Throwable) {
            $announcementTarget->update([
                'status' => 'forbidden',
            ]);

            $this->answer(__('tbe-announcements::announcements.main.answers.targetForbidden', [
                'user' => $announcementTarget->botUser->telegramUser->full_name,
            ]));
        }

        AnnouncementsFeature::sendingAnnouncement($announcementTarget->announcement, $page)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    function startSendingMessages(Announcement $announcement): void
    {
        $statusMessage = wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.sendingProgressInitial', [
                'total' => $announcement->targets()->count(),
            ]),
            'parse_mode' => 'HTML',
        ]);

        $announcement->update([
            'action_status_chat_id' => $statusMessage->chat->id,
            'action_status_message_id' => $statusMessage->messageId,
        ]);

        $announcement->targets()
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'forbidden'))
            ->each(fn (AnnouncementTarget $target) => SendAnnouncementJob::dispatch(wHook()->exportContext(), $target));
        $this->answer(__('tbe-announcements::announcements.main.answers.sendingStarted'));
    }

    /**
     * @throws TelegramSDKException
     */
    function deleteSentMessages(Announcement $announcement): void
    {
        $statusMessage = wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.deletingProgressInitial', [
                'total' => $announcement->targets()->count(),
            ]),
            'parse_mode' => 'HTML',
        ]);

        $announcement->update([
            'action_status_chat_id' => $statusMessage->chat->id,
            'action_status_message_id' => $statusMessage->messageId,
        ]);

        $announcement->targets()
            ->where('status', 'sent')
            ->each(fn (AnnouncementTarget $target) => DeleteAnnouncementJob::dispatch(wHook()->exportContext(), $target));
        $this->answer(__('tbe-announcements::announcements.main.answers.deletingStarted'));
    }
}
