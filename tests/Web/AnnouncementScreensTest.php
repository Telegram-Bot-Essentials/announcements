<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use TelegramBotEssentials\Announcements\DTOs\Audience;
use TelegramBotEssentials\Announcements\Jobs\DeleteAnnouncementJob;
use TelegramBotEssentials\Announcements\Jobs\SendAnnouncementJob;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Services\Broadcasts;
use TelegramBotEssentials\Announcements\Web\Manage\ListAnnouncements;
use TelegramBotEssentials\Announcements\Web\Manage\ViewAnnouncement;
use TelegramBotEssentials\Announcements\Web\Widgets\DeliveryChart;
use TelegramBotEssentials\Essence\Models\BotUser;
use TelegramBotEssentials\Essence\Testing\TestAccount;
use TelegramBotEssentials\Essence\Web\WebAccounts;

function textAnnouncement(BotUser $author, string $label = 'Sale'): Announcement
{
    return Announcement::query()->create([
        'bot_id' => $author->bot_id, 'bot_user_id' => $author->id,
        'method' => 'html', 'message_text' => '<b>Half price</b>', 'label' => $label,
    ]);
}

function signIn(object $test, BotUser $user): TestAccount
{
    $account = $test->panelAccount($user);
    Auth::guard(WebAccounts::guard())->login($account);

    return $account;
}

beforeEach(function () {
    Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 1, 'date' => 0, 'chat' => ['id' => 1, 'type' => 'private']]])]);
    $this->bot = $this->makeBot(['bot_owner_peer_id' => 9001]);
    $this->admin = $this->grant($this->makeBotUser($this->bot, 9001), 'announcements.manage', 'web.manage');
    $this->customer = $this->makeBotUser($this->bot, 9002);
});

it('lists this bot\'s announcements only', function () {
    textAnnouncement($this->admin, 'Ours');
    $otherBot = $this->makeBot(['unique_id' => 'other-bot']);
    textAnnouncement($this->makeBotUser($otherBot, 9100), 'Theirs');
    signIn($this, $this->admin);

    $this->get('/manage/'.$this->bot->unique_id.'/announcements')
        ->assertOk()
        ->assertSee('Ours')
        ->assertDontSee('Theirs');
});

it('keeps the screens from anyone without announcements.manage', function () {
    signIn($this, $this->grant($this->makeBotUser($this->bot, 9003), 'web.manage'));

    $this->get('/manage/'.$this->bot->unique_id.'/announcements')->assertForbidden();
});

it('creates a text announcement as the acting user', function () {
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ListAnnouncements::class)
        ->callAction('create', ['label' => 'Launch', 'text' => 'We are <b>live</b>'])
        ->assertHasNoActionErrors();

    $announcement = Announcement::query()->where('label', 'Launch')->firstOrFail();
    expect($announcement->method)->toBe('html')
        ->and($announcement->message_text)->toBe('We are <b>live</b>')
        ->and($announcement->bot_user_id)->toBe($this->admin->id);
});

it('adds recipients and queues the send, without a status message in Telegram', function () {
    Queue::fake();
    $announcement = textAnnouncement($this->admin);
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])
        ->callAction('addRecipients')
        ->callAction('send');

    expect($announcement->targets()->count())->toBe(2);
    Queue::assertPushed(SendAnnouncementJob::class, 1);
    expect($announcement->fresh()->action_status_message_id)->toBeNull();
});

it('retracts delivered copies only', function () {
    Queue::fake();
    $announcement = textAnnouncement($this->admin);
    AnnouncementTarget::query()->create(['bot_id' => $this->bot->id, 'announcement_id' => $announcement->id, 'bot_user_id' => $this->customer->id, 'status' => 'sent', 'message_id' => 5]);
    AnnouncementTarget::query()->create(['bot_id' => $this->bot->id, 'announcement_id' => $announcement->id, 'bot_user_id' => $this->admin->id, 'status' => 'forbidden']);
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])->callAction('retract');

    Queue::assertPushed(DeleteAnnouncementJob::class, 1);
});

it('previews in the acting user\'s own chat', function () {
    $announcement = textAnnouncement($this->admin);
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->id])->callAction('preview')->assertNotified();

    Http::assertSent(fn ($request) => str_ends_with((string) $request->url(), '/sendMessage') && (int) $request['chat_id'] === 9001);
});

it('does not open another bot\'s announcement', function () {
    $otherBot = $this->makeBot(['unique_id' => 'other-bot']);
    $theirs = textAnnouncement($this->makeBotUser($otherBot, 9100));
    signIn($this, $this->admin);

    $this->get('/manage/'.$this->bot->unique_id.'/announcements/'.$theirs->id)->assertNotFound();
});

it('charts the delivery of one announcement, live', function () {
    $announcement = textAnnouncement($this->admin);
    AnnouncementTarget::query()->create(['bot_id' => $this->bot->id, 'announcement_id' => $announcement->id, 'bot_user_id' => $this->customer->id, 'status' => 'sent', 'message_id' => 5]);
    AnnouncementTarget::query()->create(['bot_id' => $this->bot->id, 'announcement_id' => $announcement->id, 'bot_user_id' => $this->admin->id]);
    $this->enterPanel('manage', $this->bot, $this->admin);

    $chart = Livewire::test(DeliveryChart::class, ['record' => $announcement]);
    $data = (fn () => $this->getCachedData())->call($chart->instance());

    expect($data['datasets'][0]['data'])->toBe([1, 1])
        ->and($data['labels'][1])->toContain(__('tbe-announcements::web.status.sent'));
});

describe('over real requests', function () {
    it('sends for a signed-in admin', function () {
        Queue::fake();
        $announcement = textAnnouncement($this->admin);
        $announcement->targets()->create(['bot_id' => $this->bot->id, 'bot_user_id' => $this->customer->id]);
        signIn($this, $this->admin);

        $this->livewireRequest('/manage/'.$this->bot->unique_id.'/announcements/'.$announcement->id, 'ViewAnnouncement', 'mountAction', ['send'])->assertOk();
    });

    it('refuses once signed out', function () {
        $announcement = textAnnouncement($this->admin);
        signIn($this, $this->admin);

        $this->livewireRequest(
            '/manage/'.$this->bot->unique_id.'/announcements/'.$announcement->id, 'ViewAnnouncement', 'mountAction', ['delete'],
            fn () => Auth::guard(WebAccounts::guard())->logout(),
        )->assertUnauthorized();

        expect(Announcement::query()->find($announcement->id))->not->toBeNull();
    });
});

it('refuses a text with a placeholder nothing can fill in', function () {
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ListAnnouncements::class)
        ->callAction('create', ['label' => 'Typo', 'text' => 'Hi {$nmae}'])
        ->assertHasActionErrors(['text']);

    expect(Announcement::query()->where('label', 'Typo')->exists())->toBeFalse();
});

it('sends each recipient the text filled in for them', function () {
    $this->customer->telegramUser->update(['first_name' => 'Ali', 'last_name' => null]);
    $this->admin->telegramUser->update(['first_name' => 'Sara', 'last_name' => null]);
    $this->enterPanel('manage', $this->bot, $this->admin);

    $announcement = app(Broadcasts::class)->createText($this->admin, 'Hello', 'Hi {$first_name}!');
    app(Broadcasts::class)->addAllUsers($announcement);

    (new SendAnnouncementJob(wHook()->exportContext(), $announcement->targets()->pluck('id')->all()))->handle();

    $texts = collect(Http::recorded())
        ->map(fn (array $pair) => $pair[0])
        ->filter(fn ($request) => str_contains((string) $request->url(), '/sendMessage'))
        ->map(fn ($request) => $request['text']);

    expect($texts)->toContain('Hi Sara!', 'Hi Ali!')
        ->and($announcement->targets()->where('status', 'sent')->count())->toBe(2);
});

it('lists announcements an admin wrote, and automatic ones only when asked', function () {
    $written = textAnnouncement($this->admin, 'By hand');
    $automatic = Announcement::query()->create([
        'bot_id' => $this->bot->id, 'method' => 'html', 'message_text' => 'Auto', 'label' => 'On its own', 'alert_key' => 'test.alert',
    ]);
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ListAnnouncements::class)
        ->assertCanSeeTableRecords([$written])
        ->assertCanNotSeeTableRecords([$automatic])
        ->filterTable('automatic', true)
        ->assertCanSeeTableRecords([$automatic])
        ->assertCanNotSeeTableRecords([$written]);
});

it('chooses who gets an announcement: an audience, with or without muted users', function () {
    announcementAudiences()->add(new Audience('test.customers', 'Customers', fn ($query) => $query->where('bot_users.id', $this->customer->id)));
    $announcement = textAnnouncement($this->admin);
    $this->enterPanel('manage', $this->bot, $this->admin);

    Livewire::test(ViewAnnouncement::class, ['record' => $announcement->getKey()])
        ->callAction('addRecipients', ['audience' => 'test.customers', 'include_muted' => false])
        ->assertHasNoActionErrors();

    expect($announcement->targets()->pluck('bot_user_id')->all())->toBe([$this->customer->id])
        ->and($announcement->refresh()->audience_key)->toBe('test.customers')
        ->and($announcement->include_muted)->toBeFalse();
});
