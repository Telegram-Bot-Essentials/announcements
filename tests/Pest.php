<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use TelegramBotEssentials\Announcements\Tests\TestCase;
use TelegramBotEssentials\Announcements\Tests\WebTestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(WebTestCase::class, RefreshDatabase::class)->in('Web');
