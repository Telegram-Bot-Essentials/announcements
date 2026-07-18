<?php

namespace TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Telegram\Bot\Exceptions\TelegramSDKException;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\StateAnswers\StateAnswer;

class AnnouncementsAnswer extends StateAnswer
{
    protected string $type = 'ANNOUNCEMENTS';
    protected int $perm = Roles::ADMIN->value;
    protected array $allowedFields = [
        AllowableFields::TEXT->value
    ];

    // TODO: Implement cancel() method for custom cancellation logic
    // function cancel(): void
    // {
    // }

    public function createAnnouncement(int $lastPage): void
    {
        $announcement = Announcement::create([
            'bot_user_id' => wHook()->user()->id,
            'from_chat_id' => wHook()->update()->message->from->id,
            'message_id' => wHook()->update()->message->messageId,
            'message_text' => wHook()->update()->message->text,
            'label' => Str::limit(wHook()->update()->message->text, 8)
        ])->refresh();

        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->user()->telegramUser->peer_id,
            'text' => __('tbe-announcements::announcements.main.answers.created'),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->messageMeta()->updateAndContinueAction(
            AnnouncementsFeature::show($announcement, $lastPage)
        );
    }

    function change(Announcement $announcement, string $target, int $lastPage = 1): void
    {
        $answer = wHook()->update()->message->text;
        $announcement->$target = $answer;
        $announcement->save();
        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.answers.updated'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $this->messageMeta()->updateAndContinueAction(
            AnnouncementsFeature::show($announcement, $lastPage)
        );
    }

    function setMessage(Announcement $announcement, int $lastPage = 1): void
    {
        $announcement->message_id = wHook()->update()->message->messageId;
        $announcement->from_chat_id = wHook()->update()->message->from->id;
        $announcement->message_text = wHook()->update()->message->text;
        $announcement->save();

        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.answers.updated'),
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $this->messageMeta()->updateAndContinueAction(
            AnnouncementsFeature::show($announcement, $lastPage)
        );
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws InvalidPageNumber
     */
    public function setStartPage(): void
    {
        $page = wHook()->update()->message->text;
        $lastPage = Announcement::query()->paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = AnnouncementsFeature::menu(intval($page));

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.answers.pageLoaded', ['page' => $page]),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->messageMeta()->updateAndContinueAction($data);
    }

    /**
     * @throws LogicException
     * @throws TelegramSDKException
     * @throws BindingResolutionException
     * @throws InvalidPageNumber
     */
    public function sendingSetPage(Announcement $announcement): void
    {
        $page = wHook()->update()->message->text;
        $lastPage = $announcement->targets()->paginate(perPage: 10)->lastPage();

        TelegramPaginator::validatePageInput($page, $lastPage);

        $data = AnnouncementsFeature::sendingAnnouncement($announcement, intval($page));

        wHook()->user()->changeState();
        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => __('tbe-announcements::announcements.main.answers.pageLoaded', ['page' => $page]),
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);

        $this->messageMeta()->updateAndContinueAction($data);
    }
}
