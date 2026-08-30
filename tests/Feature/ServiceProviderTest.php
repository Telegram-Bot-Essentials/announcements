<?php

declare(strict_types=1);

use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin\AnnouncementsQuery;
use TelegramBotEssentials\Announcements\Telegram\ReplyKeys\Admin\AnnouncementsKey;
use TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin\AnnouncementsAnswer;
use TelegramBotEssentials\Essence\Enums\Roles;

it('registers the announcements callback query and state answer with essence', function () {
    expect(callbackQueryBus()->getCallbackQueryTypes()['ANNOUNCEMENTS'] ?? null)->toBeInstanceOf(AnnouncementsQuery::class)
        ->and(stateAnswerBus()->getStateAnswerTypes()['ANNOUNCEMENTS'] ?? null)->toBeInstanceOf(AnnouncementsAnswer::class);
});

it('loads its migrations', function () {
    $announcement = Announcement::factory()->hasTargets(2)->create();

    expect(Announcement::count())->toBe(1)
        ->and(AnnouncementTarget::where('announcement_id', $announcement->id)->count())->toBe(2);
});

it('resolves the reply-key label in the locale active at read time', function () {
    $key = new AnnouncementsKey;

    app()->setLocale('en');
    $en = $key->getText();

    app()->setLocale('fa');

    expect($key->getText())->not->toBe($en)
        ->and($key->getPerm())->toBe(Roles::ADMIN->value);
});
