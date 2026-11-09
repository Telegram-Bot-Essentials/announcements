<?php

namespace TelegramBotEssentials\Announcements\Web\Manage;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use TelegramBotEssentials\Announcements\Services\Broadcasts;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsResourcePage;

class ListAnnouncements extends ListRecords
{
    use GuardsResourcePage;

    protected static string $resource = AnnouncementResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            // Copies and forwards start from a message sent to the bot; the web writes text.
            Action::make('create')
                ->label((string) __('tbe-announcements::web.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('label')->label((string) __('tbe-announcements::web.label'))->required()->maxLength(255),
                    Textarea::make('text')
                        ->label((string) __('tbe-announcements::web.text'))
                        ->helperText((string) __('tbe-announcements::web.text_help'))
                        ->required()
                        ->maxLength(4096)
                        ->rows(6),
                ])
                ->action(function (array $data, Broadcasts $broadcasts): void {
                    $label = $data['label'] ?? null;
                    $text = $data['text'] ?? null;
                    $announcement = $broadcasts->createText(wHook()->user(), is_string($label) ? $label : '', is_string($text) ? $text : '');
                    $this->redirect(ViewAnnouncement::getUrl(['record' => $announcement]));
                }),
        ];
    }
}
