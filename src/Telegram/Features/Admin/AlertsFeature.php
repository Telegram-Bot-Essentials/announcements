<?php

namespace TelegramBotEssentials\Announcements\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Announcements\Models\AnnouncementMute;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

class AlertsFeature
{
    /**
     * Every automatic alert with whether it is on and how it did. Changing
     * one is done in settings, which the web panel shares.
     */
    public static function menu(int $lastPage = 1): TelegramResponse
    {
        $counts = self::counts();

        $lines = [];
        $replyMarkup = Keyboard::make()->inline();

        foreach (array_values(announcementAlerts()->all()) as $index => $alert) {
            $lines[] = __('tbe-announcements::alerts.admin.text.line', [
                'state' => announcementAlerts()->enabled($alert) ? '✅' : '❌',
                'label' => htmlspecialchars($alert->label()),
                'sent' => number_format($counts[$alert->key]['sent'] ?? 0),
                'blocked' => number_format($counts[$alert->key]['forbidden'] ?? 0),
            ]);

            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => '👁 '.$alert->label(),
                    'callback_data' => encodeCallback(AnnouncementsFeature::$type, 'alertPreview', [$index]),
                ]),
            ]);
        }

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(AnnouncementsFeature::$type, 'start', [$lastPage, 0]),
            ]),
        ]);

        $text = __('tbe-announcements::alerts.admin.text.menu')
            ."\n\n".implode("\n\n", $lines)
            ."\n\n".__('tbe-announcements::alerts.admin.text.muted', [
                'count' => number_format(AnnouncementMute::query()->count()),
            ]);

        return new TelegramResponse(text: $text, replyMarkup: $replyMarkup, parseMode: 'HTML');
    }

    /**
     * How many users each alert reached ("sent", retracted copies included)
     * and how many had blocked the bot ("forbidden"), by alert key.
     *
     * @return array<string, array{sent: int, forbidden: int}>
     */
    public static function counts(): array
    {
        $rows = AnnouncementTarget::query()->toBase()
            ->whereNotNull('alert_key')
            ->whereIn('status', ['sent', 'deleted', 'forbidden'])
            ->selectRaw('alert_key, status, count(*) as total')
            ->groupBy('alert_key', 'status')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            if (! is_string($row->alert_key)) {
                continue;
            }
            $counts[$row->alert_key] ??= ['sent' => 0, 'forbidden' => 0];
            $counts[$row->alert_key][$row->status === 'forbidden' ? 'forbidden' : 'sent'] += is_numeric($row->total) ? (int) $row->total : 0;
        }

        return $counts;
    }
}
