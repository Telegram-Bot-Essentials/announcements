<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use TelegramBotEssentials\Announcements\DTOs\Alert;
use TelegramBotEssentials\Announcements\DTOs\AlertParam;
use TelegramBotEssentials\Announcements\DTOs\Audience;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Services\AlertSweep;
use TelegramBotEssentials\Announcements\Services\Broadcasts;
use TelegramBotEssentials\Announcements\Services\Mutes;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AlertsFeature;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;

/** The texts Telegram was asked to send, in order. */
function sentTexts(): array
{
    return collect(Http::recorded())
        ->map(fn (array $pair) => $pair[0])
        ->filter(fn ($request) => str_contains((string) $request->url(), '/sendMessage'))
        ->map(fn ($request) => $request['text'])
        ->values()
        ->all();
}

function sweep(object $test): int
{
    return wHook()->runInContext($test->bot, null, null, null, fn () => app(AlertSweep::class)->run($test->bot));
}

beforeEach(function () {
    // The base test case answers every request with an empty body, and the first fake wins.
    Http::swap(new Factory);
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'date' => 0, 'chat' => ['id' => 1, 'type' => 'private']]])]);

    // Jobs run as they are queued, so a sweep ends with its messages sent.
    config(['queue.default' => 'sync']);

    Carbon::setTestNow('2026-11-15 12:00:00');

    $this->bot = $this->makeBot();
    $this->vip = $this->makeBotUser($this->bot, 7001, ['has_private_chat' => true]);
    $this->other = $this->makeBotUser($this->bot, 7002, ['has_private_chat' => true]);
    $this->vip->telegramUser->update(['first_name' => 'Sara', 'last_name' => null]);

    // "VIPs": whoever is named in the test, so the audience is easy to move.
    $this->vips = [$this->vip->id];
    announcementAudiences()->add(new Audience('test.vips', 'VIPs', fn (Builder $query) => $query->whereIn('bot_users.id', $this->vips)));

    $this->alert = new Alert(
        key: 'test.vip_offer',
        label: 'VIP offer',
        audience: 'test.vips',
        text: fn () => 'Hi {$first_name}, spend {$alert.amount} today',
        params: [new AlertParam('amount', 'Amount', 500, format: fn (int $value) => $value.' coins')],
        priority: 10,
        keyboard: fn () => [[['text' => 'Shop', 'callback_data' => 'SHOP']]],
    );
    announcementAlerts()->register($this->alert);

    wHook()->setBot($this->bot);
    settings()->set(announcementAlerts()->settingKey($this->alert, 'status'), true, $this->bot);
});

it('sends nothing for an alert nobody switched on', function () {
    settings()->set(announcementAlerts()->settingKey($this->alert, 'status'), false, $this->bot);

    expect(sweep($this))->toBe(0)
        ->and(Announcement::query()->count())->toBe(0);
});

it('sends an alert to its audience only, filled in, with its buttons and the way out', function () {
    expect(sweep($this))->toBe(1)
        ->and(sentTexts())->toBe(['Hi Sara, spend 500 coins today']);

    $announcement = Announcement::query()->sole();
    expect($announcement->alert_key)->toBe('test.vip_offer')
        ->and($announcement->bot_user_id)->toBeNull()
        ->and($announcement->targets()->where('status', 'sent')->pluck('bot_user_id')->all())->toBe([$this->vip->id]);

    $markup = collect(Http::recorded())->map(fn (array $pair) => $pair[0])
        ->first(fn ($request) => str_contains((string) $request->url(), '/sendMessage'))['reply_markup'];
    $buttons = collect(json_decode((string) $markup, true)['inline_keyboard'])->flatten(1)->pluck('callback_data');

    expect($buttons->all())->toBe(['SHOP', encodeCallback('ANNMUTE', 'mute')]);
});

it('sends an alert once, unless it is set to repeat', function () {
    sweep($this);
    Carbon::setTestNow(now()->addDays(10));

    expect(sweep($this))->toBe(0);

    settings()->set(announcementAlerts()->settingKey($this->alert, 'repeat_days'), 7, $this->bot);

    expect(sweep($this))->toBe(1);

    Carbon::setTestNow(now()->addDays(3));

    expect(sweep($this))->toBe(0);
});

it('uses the admin\'s wording and number when they changed them', function () {
    settings()->set(announcementAlerts()->settingKey($this->alert, 'text'), 'Only {$alert.amount} left', $this->bot);
    settings()->set(announcementAlerts()->settingKey($this->alert, 'amount'), 90, $this->bot);

    sweep($this);

    expect(sentTexts())->toBe(['Only 90 coins left']);
});

it('refuses a wording with an unknown placeholder, and puts the default back on "-"', function () {
    $key = announcementAlerts()->settingKey($this->alert, 'text');

    expect(fn () => settings()->set($key, 'Hi {$nmae}', $this->bot))->toThrow(ValidationException::class);

    settings()->set($key, 'Custom {$name}', $this->bot);
    expect(announcementAlerts()->text($this->alert))->toBe('Custom {$name}');

    settings()->set($key, '-', $this->bot);
    expect(announcementAlerts()->text($this->alert))->toBe('Hi {$first_name}, spend {$alert.amount} today');
});

it('gives a user one alert within the cap, the most urgent first', function () {
    $second = new Alert('test.second', 'Second', 'test.vips', fn () => 'Second message', priority: 50);
    announcementAlerts()->register($second);
    settings()->set(announcementAlerts()->settingKey($second, 'status'), true, $this->bot);

    expect(sweep($this))->toBe(1)
        ->and(sentTexts())->toBe(['Hi Sara, spend 500 coins today']);

    Carbon::setTestNow(now()->addHours(23));
    expect(sweep($this))->toBe(0);

    Carbon::setTestNow(now()->addHours(2));
    expect(sweep($this))->toBe(1)
        ->and(sentTexts())->toContain('Second message');
});

it('leaves muted users out', function () {
    app(Mutes::class)->toggle($this->vip);

    expect(sweep($this))->toBe(0);

    app(Mutes::class)->toggle($this->vip);

    expect(sweep($this))->toBe(1);
});

it('waits for the sending hours', function () {
    Carbon::setTestNow('2026-11-15 03:00:00');
    expect(sweep($this))->toBe(0);

    Carbon::setTestNow('2026-11-15 10:00:00');
    expect(sweep($this))->toBe(1);
});

it('tries again next time for a user the message did not reach', function () {
    sweep($this);
    AnnouncementTarget::query()->update(['status' => 'failed']);

    Carbon::setTestNow(now()->addHour());

    expect(sweep($this))->toBe(1);
});

it('turns alerts off and on again with /mute', function () {
    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/mute', peerId: 7001))->assertOk();
    expect(app(Mutes::class)->isMuted($this->vip))->toBeTrue()
        ->and(sentTexts())->toContain(__('tbe-announcements::alerts.mute.text.muted'));

    $this->postWebhookUpdate($this->bot, $this->makeMessageUpdate('/mute', peerId: 7001))->assertOk();
    expect(app(Mutes::class)->isMuted($this->vip))->toBeFalse();
});

it('mutes from the key under an alert, and flips back from the answer', function () {
    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('ANNMUTE', 'mute'), peerId: 7001))->assertOk();
    expect(app(Mutes::class)->isMuted($this->vip))->toBeTrue();

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('ANNMUTE', 'flip'), peerId: 7001))->assertOk();
    expect(app(Mutes::class)->isMuted($this->vip))->toBeFalse();
});

it('aims a written announcement at an audience, with or without muted users', function () {
    $announcement = Announcement::factory()->create(['bot_id' => $this->bot->id, 'bot_user_id' => $this->other->id, 'method' => 'html', 'message_text' => 'Hi']);
    $targets = fn () => $announcement->targets()->pluck('bot_user_id')->sort()->values()->all();
    $broadcasts = app(Broadcasts::class);

    $broadcasts->aim($announcement, 'test.vips', true);
    expect($targets())->toBe([$this->vip->id]);

    $broadcasts->aim($announcement, null, true);
    expect($targets())->toBe([$this->vip->id, $this->other->id]);

    app(Mutes::class)->toggle($this->vip);
    $broadcasts->aim($announcement, null, false);
    expect($targets())->toBe([$this->other->id]);
});

it('keeps the row of a user already tried when the audience moves on', function () {
    $announcement = Announcement::factory()->create(['bot_id' => $this->bot->id, 'bot_user_id' => $this->other->id, 'method' => 'html', 'message_text' => 'Hi']);
    app(Broadcasts::class)->aim($announcement, null, true);
    $announcement->targets()->where('bot_user_id', $this->other->id)->update(['status' => 'sent']);

    app(Broadcasts::class)->aim($announcement, 'test.vips', true);

    expect($announcement->targets()->pluck('status', 'bot_user_id')->all())
        ->toBe([$this->vip->id => null, $this->other->id => 'sent']);
});

it('keeps alerts out of the announcement list and shows them on their own screen', function () {
    sweep($this);

    expect(AnnouncementsFeature::menu()->text)->toBe(__('tbe-announcements::announcements.main.text.menu_empty'));

    $screen = AlertsFeature::menu();
    expect($screen->text)->toContain('✅ <b>VIP offer</b>')
        ->and(AlertsFeature::counts())->toBe(['test.vip_offer' => ['sent' => 1, 'forbidden' => 0]]);
});

it('previews an alert to the admin as they would get it', function () {
    $this->grant($this->vip, 'announcements.manage');

    $this->postWebhookUpdate($this->bot, $this->makeCallbackQueryUpdate(encodeCallback('ANNOUNCEMENTS', 'alertPreview', [0]), peerId: 7001))->assertOk();

    // The update carries the sender's name as Telegram has it now.
    expect(sentTexts())->toBe(['Hi Test, spend 500 coins today'])
        ->and(Announcement::query()->count())->toBe(0);
});
