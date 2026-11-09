<?php

namespace TelegramBotEssentials\Announcements;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use TelegramBotEssentials\Announcements\Telegram\CallbackQueries\Admin\AnnouncementsQuery;
use TelegramBotEssentials\Announcements\Telegram\StateAnswers\Admin\AnnouncementsAnswer;
use TelegramBotEssentials\Announcements\Web\AnnouncementsManagePlugin;
use TelegramBotEssentials\Essence\Exceptions\LogicException;

class TbeAnnouncementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tbe-announcements.php', 'tbe-announcements');

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
        ]);

        stateAnswerBus()->addStateAnswers([
            AnnouncementsAnswer::class,
        ]);
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
