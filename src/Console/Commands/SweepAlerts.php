<?php

namespace TelegramBotEssentials\Announcements\Console\Commands;

use Illuminate\Console\Command;
use TelegramBotEssentials\Announcements\Services\Alerts;
use TelegramBotEssentials\Announcements\Services\AlertSweep;
use TelegramBotEssentials\Essence\Models\Bot;
use Throwable;

class SweepAlerts extends Command
{
    protected $signature = 'tbe:announcements:sweep-alerts';

    protected $description = 'Send the automatic alerts that are due, for every bot';

    public function handle(Alerts $alerts, AlertSweep $sweep): void
    {
        // Asked before any bot is entered: whether an alert is active is a per-bot question.
        if (! $alerts->any()) {
            return;
        }

        $queued = 0;

        Bot::query()->each(function (Bot $bot) use ($sweep, &$queued) {
            // Each bot has its own settings and token, so each runs as itself;
            // one bot's broken audience must not stop the others.
            try {
                wHook()->runInContext($bot, null, null, null, function () use ($sweep, $bot, &$queued) {
                    $queued += $sweep->run($bot);
                });
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        $this->info("Queued {$queued} alert message(s).");
    }
}
