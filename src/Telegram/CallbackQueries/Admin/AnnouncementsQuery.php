<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Announcements\Jobs\DeleteAnnouncementJob;
use TelegramBotEssentials\Announcements\Jobs\SendAnnouncementJob;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Models\MessageMeta;
use TelegramBotEssentials\Essence\Telegram\CallbackQueries\CallbackQuery;
use Throwable;

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
        AnnouncementsFeature::menu(max(1, $page), $currentPage)->update();
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
    public function show(Announcement $announcement, int $lastPage = 1): void
    {
        AnnouncementsFeature::show($announcement, $lastPage)->update();
    }

    public function preview(Announcement $announcement): void
    {
        $announcement->sendTo(wHook()->peerId());

        $this->answer(__('tbe-announcements::announcements.main.answers.previewSent'));
    }

    public function change(Announcement $announcement, string $target, int $lastPage = 1): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        wHook()->user()->changeState(encodeAnswerState($this->type, 'change', [
            'announcement' => $announcement->id,
            'target' => $target,
            'lastPage' => $lastPage,
            'message_meta' => $messageMeta->id,
        ]));
        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.changingField', [
            'field' => __('tbe-announcements::announcements.main.fields.'.$target),
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.enterField', [
                'field' => __('tbe-announcements::announcements.main.fields.'.$target),
            ]),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }

    public function setMessage(Announcement $announcement, int $lastPage = 1): void
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

    /**
     * @throws InvalidPageNumber
     */
    public function delete(Announcement $announcement, int $lastPage = 1): void
    {
        $announcement->delete();

        AnnouncementsFeature::menu(max(1, $lastPage))
            ->answer(__('tbe-announcements::announcements.main.answers.deleted'))
            ->update();
    }

    public function changeMethod(Announcement $announcement, string $method, int $lastPage = 1): void
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

    public function sendingAnnouncement(Announcement $announcement, int $page = 1, int $currentPage = 0): void
    {
        AnnouncementsFeature::sendingAnnouncement($announcement, $page, $currentPage)->update();
    }

    /**
     * Navigation buttons put the page arguments first, so the announcement
     * arrives last. Keep it separate from sendingAnnouncement() rather than
     * reshuffling that signature for every other caller.
     *
     * @throws InvalidPageNumber
     * @throws TelegramSDKException
     */
    public function sendingAnnouncementPage(int $page, int $currentPage, Announcement $announcement): void
    {
        AnnouncementsFeature::sendingAnnouncement($announcement, $page, $currentPage)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    public function sendingSetPage(Announcement $announcement): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.settingPage'));
        wHook()->user()->changeState(encodeAnswerState($this->type, 'sendingSetPage', [
            'message_meta' => $messageMeta->id,
            'announcement' => $announcement->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.enterPagePrompt'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->answer(__('tbe-announcements::announcements.main.answers.settingPage'));
    }

    public function reloadTargetUsers(Announcement $announcement, int $page = 1): void
    {
        $now = now();

        $rows = BotUser::query()
            ->pluck('id')
            ->map(fn (int $botUserId) => [
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

    public function announcementTargetSend(AnnouncementTarget $announcementTarget, int $page)
    {
        dependsOn(
            $announcementTarget->status !== 'sent',
            __('tbe-announcements::announcements.main.errors.alreadySent')
        );

        // Deliberately attempted even for a skipped target: forcing one send is
        // how an admin overrules, or re-tests, what we believe about a user.
        try {
            $message = $announcementTarget->announcement->sendTo($announcementTarget->botUser->telegramUser->peer_id);
            $announcementTarget->update([
                'message_id' => $message->messageId,
                'status' => 'sent',
            ]);

            botUserStatus()->reportSuccess($announcementTarget->botUser);

            $this->answer(__('tbe-announcements::announcements.main.answers.targetSent', [
                'user' => $announcementTarget->botUser->telegramUser->full_name,
            ]));
        } catch (Throwable $exception) {
            $status = botUserStatus()->reportFailure($announcementTarget->botUser, $exception);

            $announcementTarget->update([
                'status' => $status === null ? 'failed' : 'forbidden',
            ]);

            $this->answer(__(
                $status === null
                    ? 'tbe-announcements::announcements.main.answers.targetFailed'
                    : 'tbe-announcements::announcements.main.answers.targetForbidden',
                ['user' => $announcementTarget->botUser->telegramUser->full_name]
            ));
        }

        AnnouncementsFeature::sendingAnnouncement($announcementTarget->announcement, $page)->update();
    }

    /**
     * @throws TelegramSDKException
     */
    public function announcementTargetDelete(AnnouncementTarget $announcementTarget, int $page)
    {
        dependsOn(
            $announcementTarget->status === 'sent',
            __('tbe-announcements::announcements.main.errors.cannotDelete')
        );

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
    public function startSendingMessages(Announcement $announcement): void
    {
        $statusMessage = wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => AnnouncementsFeature::getSendingProgressText($announcement),
            'parse_mode' => 'HTML',
        ]);

        $announcement->update([
            'action_status_chat_id' => $statusMessage->chat->id,
            'action_status_message_id' => $statusMessage->messageId,
        ]);

        $targetIds = $announcement->targets()
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'forbidden'))
            ->pluck('id')
            ->toArray();

        $batchSize = config('tbe-announcements.batch_size', 100);
        $chunks = array_chunk($targetIds, $batchSize);

        $context = wHook()->exportContext();
        foreach ($chunks as $chunk) {
            SendAnnouncementJob::dispatch($context, $chunk);
        }

        $this->answer(__('tbe-announcements::announcements.main.answers.sendingStarted'));
    }

    /**
     * @throws TelegramSDKException
     */
    public function setStartPage(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction(__('tbe-announcements::announcements.main.lock-keys.settingPage'));
        wHook()->user()->changeState(encodeAnswerState($this->type, 'setStartPage', [
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.text.enterPagePrompt'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->answer(__('tbe-announcements::announcements.main.answers.settingPage'));
    }

    /**
     * @throws TelegramSDKException
     */
    public function deleteSentMessages(Announcement $announcement): void
    {
        $statusMessage = wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => AnnouncementsFeature::getDeletingProgressText($announcement),
            'parse_mode' => 'HTML',
        ]);

        $announcement->update([
            'action_status_chat_id' => $statusMessage->chat->id,
            'action_status_message_id' => $statusMessage->messageId,
        ]);

        $targetIds = $announcement->targets()
            ->where('status', 'sent')
            ->pluck('id')
            ->toArray();

        $batchSize = config('tbe-announcements.batch_size', 100);
        $chunks = array_chunk($targetIds, $batchSize);

        $context = wHook()->exportContext();
        foreach ($chunks as $chunk) {
            DeleteAnnouncementJob::dispatch($context, $chunk);
        }

        $this->answer(__('tbe-announcements::announcements.main.answers.deletingStarted'));
    }
}
