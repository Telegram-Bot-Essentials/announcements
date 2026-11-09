<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Announcements\Tests;

use TelegramBotEssentials\Announcements\TbeAnnouncementsServiceProvider;
use TelegramBotEssentials\Essence\Testing\WebTestCase as EssenceWebTestCase;

abstract class WebTestCase extends EssenceWebTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            TbeAnnouncementsServiceProvider::class,
        ]);
    }
}
