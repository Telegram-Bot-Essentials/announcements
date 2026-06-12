<?php

namespace TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin;

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
}
