<?php

declare(strict_types=1);

use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Essence\Enums\Roles;
use TelegramBotEssentials\Essence\Exceptions\FeatureIsDisabled;

it('shows the empty-state text until an announcement exists', function () {
    expect(AnnouncementsFeature::menu()->text)
        ->toBe(__('tbe-announcements::announcements.main.text.menu_empty'));

    Announcement::factory()->create();

    expect(AnnouncementsFeature::menu()->text)
        ->toBe(__('tbe-announcements::announcements.main.text.menu'));
});

it('lists announcements newest first, one row each', function () {
    $first = Announcement::factory()->create(['label' => 'first']);
    $second = Announcement::factory()->create(['label' => 'second', 'bot_id' => $first->bot_id]);

    $rows = collect(AnnouncementsFeature::menu()->replyMarkup->toArray()['inline_keyboard']);
    $labels = $rows->flatten(1)->pluck('text');

    expect($labels)->toContain('first', 'second')
        ->and($labels->search('second'))->toBeLessThan($labels->search('first'));
});

it('refuses to send an HTML announcement that has no message text', function () {
    $announcement = Announcement::factory()->create(['method' => 'html', 'message_text' => null]);

    expect(fn () => $announcement->sendTo(123))->toThrow(FeatureIsDisabled::class);
});

it('tells an admin the step expired when the message meta was pruned mid-flow', function () {
    $bot = $this->makeBot();

    // 15 announcements -> page "2" is valid, so validation passes and the
    // flow reaches requireMessageMeta().
    Announcement::factory()->count(15)->create(['bot_id' => $bot->id]);

    $this->makeBotUser($bot, 7000, [
        'power' => Roles::ADMIN->value,
        'state' => encodeAnswerState('ANNOUNCEMENTS', 'setStartPage', ['message_meta_id' => 999999]),
    ]);

    $this->postWebhookUpdate($bot, $this->makeMessageUpdate('2', peerId: 7000))->assertOk();

    $this->assertTelegramSent(
        fn ($request) => str_contains((string) $request->url(), '/sendMessage')
            && str_contains((string) $request['text'], __('tbe::general.alerts.contextExpired'))
    );

    expect($bot->botUsers()->where('telegram_user_peer_id', 7000)->sole()->state)->toBeNull();
});
