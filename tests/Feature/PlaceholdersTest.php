<?php

declare(strict_types=1);

use TelegramBotEssentials\Announcements\DTOs\Placeholder;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;
use TelegramBotEssentials\Essence\Models\BotUser;

beforeEach(function () {
    $this->bot = $this->makeBot();
    $this->user = $this->makeBotUser($this->bot, 7001);
    $this->user->telegramUser->update(['first_name' => 'Sara', 'last_name' => '<Ahmadi>', 'username' => 'sara']);
    $this->user->refresh();
});

it('fills in the built-in user placeholders, escaped for Telegram HTML', function () {
    $text = announcementPlaceholders()->render('Hi <b>{$name}</b> {$username} #{$user_id}', $this->user);

    expect($text)->toBe('Hi <b>Sara &lt;Ahmadi&gt;</b> @sara #7001');
});

it('uses the fallback for an empty value and leaves raw values alone', function () {
    announcementPlaceholders()
        ->add(new Placeholder('test.empty', 'Empty', fn (BotUser $user) => null, fallback: 'friend'))
        ->add(new Placeholder('test.link', 'Link', fn (BotUser $user) => '<a href="https://t.me/x">x</a>', raw: true));

    expect(announcementPlaceholders()->render('{$test.empty} {$test.link}', $this->user))
        ->toBe('friend <a href="https://t.me/x">x</a>');
});

it('never fails a render: a broken value becomes its fallback, an unknown key a blank', function () {
    announcementPlaceholders()->add(new Placeholder('test.broken', 'Broken', fn () => throw new RuntimeException('down'), fallback: '-'));

    expect(announcementPlaceholders()->render('[{$test.broken}] [{$nobody.home}]', $this->user))->toBe('[-] []');
});

it('treats an inactive placeholder as unknown', function () {
    announcementPlaceholders()->add(new Placeholder('test.off', 'Off', fn () => 'x', active: fn () => false));

    expect(announcementPlaceholders()->all())->not->toHaveKey('test.off')
        ->and(announcementPlaceholders()->unknownIn('{$name} {$test.off} {$typo}'))->toBe(['test.off', 'typo']);
});

it('refuses a text with an unknown placeholder and lists the known ones in the prompt', function () {
    expect(fn () => AnnouncementsFeature::assertPlaceholdersKnown('Hello {$nmae}'))->toThrow(FeatureIsDisabled::class);

    AnnouncementsFeature::assertPlaceholdersKnown('Hello {$name}, plain {braces} and $dollars are fine');

    expect(AnnouncementsFeature::messagePrompt())->toContain('<code>{$name}</code>');
});
