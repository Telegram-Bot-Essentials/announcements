<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Announcements\Tests;

use TelegramBotEssentials\Announcements\TbeAnnouncementsServiceProvider;
use TelegramBotEssentials\Essence\Testing\TestCase as EssenceTestCase;

abstract class TestCase extends EssenceTestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            TbeAnnouncementsServiceProvider::class,
        ]);
    }
}
