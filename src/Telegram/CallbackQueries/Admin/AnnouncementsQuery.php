<?php

namespace TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin;

use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
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

    public function createAnnouncement(): void
    {
        $messageMeta = MessageMeta::makeWithCurrentMessage();
        $messageMeta->lockAction();
        wHook()->user()->changeState(encodeAnswerState($this->type, 'createAnnouncement', [
            'input' => 'label',
            'message_meta' => $messageMeta->id,
        ]));

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => 'Enter the label that you want to set to the announcement. (Is only shown to you)',
            'reply_markup' => wHook()->user()->getKeyboard(),
            'parse_mode' => 'HTML',
        ]);
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
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => $announcement->message,
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->answer('Preview message sent');
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
        $messageMeta->lockAction();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => "Enter the {$target}:",
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
    }
}
