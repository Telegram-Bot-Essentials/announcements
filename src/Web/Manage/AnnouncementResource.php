<?php

namespace TelegramBotEssentials\Announcements\Web\Manage;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AnnouncementsFeature;
use TelegramBotEssentials\Announcements\Web\Widgets\DeliveryChart;
use TelegramBotEssentials\Essence\Web\Filament\PanelDates;
use TelegramBotEssentials\Essence\Web\Filament\PanelGroup;
use TelegramBotEssentials\Essence\Web\Filament\PanelResource;
use UnitEnum;

class AnnouncementResource extends PanelResource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $ability = 'announcements.manage';

    protected static ?string $slug = 'announcements';

    protected static ?string $tenantOwnershipRelationshipName = 'bot';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = PanelGroup::Marketing;

    public static function getNavigationLabel(): string
    {
        return (string) __('tbe-announcements::web.nav');
    }

    public static function getModelLabel(): string
    {
        return (string) __('tbe-announcements::web.announcement');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('tbe-announcements::web.nav');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount([
                    'targets',
                    'targets as sent_count' => fn (Builder $q): Builder => $q->where('status', 'sent'),
                ]))
            ->columns([
                TextColumn::make('label')->label((string) __('tbe-announcements::web.label'))->searchable(),
                TextColumn::make('method')
                    ->label((string) __('tbe-announcements::web.method'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AnnouncementsFeature::methodLabel($state)),
                TextColumn::make('targets_count')->label((string) __('tbe-announcements::web.recipients'))->numeric(),
                TextColumn::make('sent_count')->label((string) __('tbe-announcements::web.sent'))->numeric(),
                PanelDates::since(TextColumn::make('sent_at'))->label((string) __('tbe-announcements::web.finished'))->placeholder('—'),
                PanelDates::since(TextColumn::make('created_at'))->label((string) __('tbe-announcements::web.created'))->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->emptyStateHeading((string) __('tbe-announcements::web.empty.announcements'))
            ->recordUrl(fn (Announcement $record): string => ViewAnnouncement::getUrl(['record' => $record]));
    }

    public static function getWidgets(): array
    {
        return [DeliveryChart::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'view' => ViewAnnouncement::route('/{record}'),
        ];
    }
}
