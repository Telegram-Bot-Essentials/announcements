<?php

namespace TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin;

use Illuminate\Support\Facades\Validator;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\AllowableFields;
use TelegramBotEssentials\Essence\Enums\Roles;
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

    public function createAnnouncement(string $input): void
    {
        $answer = wHook()->update()->message->text;

        switch ($input) {
            case 'label':
                $sd = stateData()->store([
                    'label' => $answer,
                ]);
                wHook()->user()->addParamToState([
                    'input' => 'message',
                    'state_data' => $sd->id,
                ]);

                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => 'Enter the message',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);
                break;
            case 'message':
                stateData()->addData($this->stateData(), [
                    'message' => $answer,
                ]);
                $sd = $this->stateData();

                $announcement = Announcement::create([
                    'bot_user_id' => wHook()->user()->id,

                   'message' => $sd->data['message'],
                   'label' => $sd->data['label'],
                ]);

                $this->stateData()->delete();
                wHook()->user()->changeState();

                wHook()->api()->sendMessage([
                    'chat_id' => wHook()->user()->telegramUser->peer_id,
                    'text' => 'Notification created',
                    'reply_markup' => wHook()->user()->getKeyboard(),
                ]);

                $this->messageMeta()->updateAndContinueAction(
                    AnnouncementsFeature::menu()
                );
                break;
        }

        return;
    }

    function change(Announcement $announcement, string $target, int $lastPage = 1): void
    {
        $answer = wHook()->update()->message->text;
        $announcement->$target = $answer;
        $announcement->save();
        wHook()->user()->changeState();

        wHook()->api()->sendMessage([
            'chat_id' => wHook()->peerId(),
            'text' => "Announcement updated successfully",
            'parse_mode' => 'HTML',
            'reply_markup' => wHook()->user()->getKeyboard(),
        ]);
        $this->messageMeta()->updateAndContinueAction(
            AnnouncementsFeature::show($announcement, $lastPage)
        );
    }
}
