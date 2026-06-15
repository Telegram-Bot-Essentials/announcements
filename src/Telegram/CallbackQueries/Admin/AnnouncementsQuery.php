<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin;

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
        switch ($announcement->method) {
            case 'copy':
                dependsOn($announcement->message_id);
                dependsOn($announcement->from_chat_id);

                wHook()->api()->copyMessage([
                    'chat_id' => wHook()->peerId(),
                    'from_chat_id' => $announcement->from_chat_id,
                    'message_id' => $announcement->message_id,
                ]);
                break;
            case 'forward':
                dependsOn($announcement->message_id);
                dependsOn($announcement->from_chat_id);

                wHook()->api()->forwardMessage([
                    'chat_id' => wHook()->peerId(),
                    'from_chat_id' => $announcement->from_chat_id,
                    'message_id' => $announcement->message_id,
                ]);
                break;
            case 'html':
                dependsOn(
                    $announcement->message_text,
                    __('tbe-announcements::announcements.main.text.messageRequiredForHtml')
                );

                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->peerId(),
                    'text' => $announcement->message_text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
                break;
        }

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
        $announcement = $announcementTarget->announcement;
        switch ($announcement->method) {
            case 'copy':
                dependsOn($announcement->message_id);
                dependsOn($announcement->from_chat_id);

                wHook()->api()->copyMessage([
                    'chat_id' => $announcementTarget->botUser->telegramUser->peer_id,
                    'from_chat_id' => $announcement->from_chat_id,
                    'message_id' => $announcement->message_id,
                ]);
                break;
            case 'forward':
                dependsOn($announcement->message_id);
                dependsOn($announcement->from_chat_id);

                wHook()->api()->forwardMessage([
                    'chat_id' => $announcementTarget->botUser->telegramUser->peer_id,
                    'from_chat_id' => $announcement->from_chat_id,
                    'message_id' => $announcement->message_id,
                ]);
                break;
            case 'html':
                dependsOn(
                    $announcement->message_text,
                    __('tbe-announcements::announcements.main.text.messageRequiredForHtml')
                );

                wHook()->api()->sendMessage([
                    'chat_id' => $announcementTarget->botUser->telegramUser->peer_id,
                    'text' => $announcement->message_text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
                break;
        }
    }

    function announcementTargetDelete(AnnouncementTarget $announcementTarget, int $page)
    {

    }
}
