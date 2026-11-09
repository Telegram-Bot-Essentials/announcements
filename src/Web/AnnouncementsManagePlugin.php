<?php

namespace TelegramBotEssentials\Announcements\Web;

use Filament\Contracts\Plugin;
use Filament\Panel;
use TelegramBotEssentials\Announcements\Web\Manage\AnnouncementResource;

class AnnouncementsManagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'tbe-announcements-manage';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AnnouncementResource::class]);
    }

    public function boot(Panel $panel): void {}
}
