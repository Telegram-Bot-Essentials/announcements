<?php

namespace TelegramBotEssentials\Announcements\Web\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TelegramBotEssentials\Announcements\Telegram\Features\Admin\AlertsFeature;
use TelegramBotEssentials\Essence\Web\Filament\Concerns\GuardsWidget;

/** One card per automatic alert: whether it is on, and how many it reached or lost. */
class AlertStats extends StatsOverviewWidget
{
    use GuardsWidget;

    protected static ?string $ability = 'announcements.manage';

    // Drawn with the page above the announcement list.
    protected static bool $isLazy = false;

    /** @return array<Stat> */
    protected function getStats(): array
    {
        $counts = AlertsFeature::counts();

        $stats = [];
        foreach (announcementAlerts()->all() as $alert) {
            $on = announcementAlerts()->enabled($alert);

            $stats[] = Stat::make($alert->label(), (string) __('tbe-announcements::alerts.web.'.($on ? 'on' : 'off')))
                ->description((string) __('tbe-announcements::alerts.web.delivery', [
                    'sent' => number_format($counts[$alert->key]['sent'] ?? 0),
                    'blocked' => number_format($counts[$alert->key]['forbidden'] ?? 0),
                ]))
                ->color($on ? 'success' : 'gray');
        }

        return $stats;
    }
}
