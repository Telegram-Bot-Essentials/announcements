<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use TelegramBotEssentials\Announcements\Jobs\DeleteAnnouncementJob;
use TelegramBotEssentials\Announcements\Jobs\SendAnnouncementJob;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Announcements\Web\Manage\ListAnnouncements;
use TelegramBotEssentials\Announcements\Web\Manage\ViewAnnouncement;
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
