<?php

namespace TelegramBotEssentials\Announcements\Web\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Services\Broadcasts;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsWidget;

/** Where an announcement stands, refreshed while it goes out. */
class DeliveryChart extends ChartWidget
{
    use GuardsWidget;

    protected static ?string $ability = 'announcements.manage';

    protected ?string $pollingInterval = '5s';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    private const COLORS = [
        'pending' => '#a1a1aa',
        'sent' => '#22c55e',
        'forbidden' => '#ef4444',
        'skipped' => '#f59e0b',
        'failed' => '#dc2626',
        'deleted' => '#3b82f6',
    ];

    public function getHeading(): string
    {
        return (string) __('tbe-announcements::web.delivery');
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        if (! $this->record instanceof Announcement) {
            return ['datasets' => [], 'labels' => []];
        }

        // Only the states that happened: a doughnut of zeros says nothing.
        $counts = array_filter(app(Broadcasts::class)->counts($this->record));

        return [
            'datasets' => [[
                'data' => array_values($counts),
                'backgroundColor' => array_map(fn (string $status): string => self::COLORS[$status] ?? '#71717a', array_keys($counts)),
                'borderWidth' => 0,
            ]],
            'labels' => array_map(
                fn (string $status, int $count): string => __('tbe-announcements::web.status.'.$status).' · '.number_format($count),
                array_keys($counts),
                $counts,
            ),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'right']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
