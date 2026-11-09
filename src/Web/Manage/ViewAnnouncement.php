<?php

namespace TelegramBotEssentials\Announcements\Web\Manage;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Services\Broadcasts;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Announcements\Web\Widgets\DeliveryChart;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsResourcePage;
use Throwable;

/** One announcement: what it sends and to how many, with live delivery counts. */
class ViewAnnouncement extends Page
{
    use GuardsResourcePage;
    use InteractsWithRecord;

    protected static string $resource = AnnouncementResource::class;

    protected string $view = 'tbe-announcements::web.view';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function announcement(): Announcement
    {
        /** @var Announcement $announcement */
        $announcement = $this->getRecord();

        return $announcement->refresh();
    }

    public function getTitle(): string
    {
        return $this->announcement()->label;
    }

    public function methodLabel(): string
    {
        return AnnouncementsFeature::methodLabel($this->announcement()->method);
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return app(Broadcasts::class)->counts($this->announcement());
    }

    protected function getHeaderWidgets(): array
    {
        return [DeliveryChart::class];
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label((string) __('tbe-announcements::web.preview'))
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->action(function (): void {
                    try {
                        inBotLocale(fn () => $this->announcement()->sendTo(wHook()->user()->peerId()));
                        Notification::make()->title((string) __('tbe-announcements::web.preview_sent'))->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title((string) __('tbe-announcements::web.preview_failed'))->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('addRecipients')
                ->label((string) __('tbe-announcements::web.add_all'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('gray')
                ->action(function (Broadcasts $broadcasts): void {
                    $added = $broadcasts->addAllUsers($this->announcement());
                    Notification::make()->title((string) __('tbe-announcements::web.added', ['count' => $added]))->success()->send();
                }),
            Action::make('send')
                ->label((string) __('tbe-announcements::web.send'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => (string) __('tbe-announcements::web.send_confirm', ['count' => $this->sendable()]))
                ->visible(fn (): bool => $this->sendable() > 0)
                ->action(function (Broadcasts $broadcasts): void {
                    $queued = $broadcasts->send($this->announcement());
                    Notification::make()->title((string) __('tbe-announcements::web.sending', ['count' => $queued]))->success()->send();
                }),
            Action::make('retract')
                ->label((string) __('tbe-announcements::web.retract'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => (string) __('tbe-announcements::web.retract_confirm', ['count' => $this->counts()['sent']]))
                ->visible(fn (): bool => $this->counts()['sent'] > 0)
                ->action(function (Broadcasts $broadcasts): void {
                    $queued = $broadcasts->retract($this->announcement());
                    Notification::make()->title((string) __('tbe-announcements::web.retracting', ['count' => $queued]))->success()->send();
                }),
            Action::make('delete')
                ->label((string) __('tbe-announcements::web.delete'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription((string) __('tbe-announcements::web.delete_confirm'))
                ->action(function (): void {
                    $this->announcement()->delete();
                    $this->redirect(AnnouncementResource::getUrl());
                }),
        ];
    }

    /** Targets a send would queue: all but those who blocked the bot. */
    private function sendable(): int
    {
        $counts = $this->counts();

        return array_sum($counts) - $counts['forbidden'];
    }
}
