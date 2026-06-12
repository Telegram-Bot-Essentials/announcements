<?php

namespace TelegramBotEssentials\Announcements\Telegram\Features\Admin;

use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Billing\Models\Invoice;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;
use Telegram\Bot\Keyboard\Keyboard;

class AnnouncementsFeature
{
    static string $type = 'ANNOUNCEMENTS';

    // TODO: Implement static functions for generating bot messages

    /**
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $text = 'menu';

        $replyMarkup = Keyboard::make()
            ->inline();

        $announcements = Announcement::query()->orderByDesc('id')->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $announcements);


        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $announcements->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
