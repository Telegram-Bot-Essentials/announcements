<?php

namespace TelegramBotEssentials\Announcements;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Announcements\Console\Commands\SweepAlerts;
use TelegramBotEssentials\Announcements\Services\Alerts;
use TelegramBotEssentials\Announcements\Services\Audiences;
use TelegramBotEssentials\Announcements\Services\Placeholders;
use TelegramBotEssentials\Announcements\Support\UserPlaceholders;
use TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin\AnnouncementsQuery;
use TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Member\MuteQuery;
use TelegramBotEssentials\Announcements\Telegram\Commands\Member\MuteCommand;
use TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin\AnnouncementsAnswer;
use TelegramBotEssentials\Announcements\Web\AnnouncementsManagePlugin;
use TelegramBotEssentials\Essence\Exceptions\LogicException;

class TbeAnnouncementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tbe-announcements.php', 'tbe-announcements');

        $this->app->singleton(Placeholders::class);
        $this->app->singleton(Audiences::class);
        $this->app->singleton(Alerts::class);

        // Class names only: Filament is loaded only where the app installed it.
        webPanels()->manage(AnnouncementsManagePlugin::class);
    }

    /**
     * @throws LogicException
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerPublishing();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'tbe-announcements');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tbe-announcements');

        callbackQueryBus()->addCallbackQueries([
            AnnouncementsQuery::class,
            MuteQuery::class,
        ]);

        commandBus()->addCommands([
            MuteCommand::class,
        ]);

        stateAnswerBus()->addStateAnswers([
            AnnouncementsAnswer::class,
        ]);

        UserPlaceholders::register(announcementPlaceholders());

        announcementAlerts()->addGlobalSettings();

        if ($this->app->runningInConsole()) {
            $this->commands([SweepAlerts::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(SweepAlerts::class)->hourly();
        });
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tbe-announcements.php' => config_path('tbe-announcements.php'),
            ], 'tbe-announcements-config');

            $this->publishes([
                __DIR__.'/../lang' => resource_path('lang/vendor/tbe-announcements'),
            ], 'tbe-announcements-translations');
        }
    }
}
