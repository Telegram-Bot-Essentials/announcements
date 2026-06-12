<?php

namespace TelegramBotEssentials\Announcements\Telegram\ReplyKeys\Admin;

use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Telegram\ReplyKeys\ReplyKey;

class AnnouncementsKey extends ReplyKey
{
    protected string $text = 'Announcements';
    protected int $perm = Roles::ADMIN->value;
    protected string $response = 'Announcements executed successfully.';

    public function __construct()
    {
        // Multilingual translations
        // $this->text = __('');
        // $this->response = __('');
    }

    public function handle(): void
    {
        debugMessage('hello');
        // Logic to execute
    }
}
