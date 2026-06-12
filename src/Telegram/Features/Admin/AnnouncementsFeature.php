<?php

namespace TelegramBotEssentials\Announcements\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Essence\Exceptions\InvalidPageNumber;
use TelegramBotEssentials\Essence\Services\TelegramPaginator;
use TelegramBotEssentials\Essence\Telegram\TelegramResponse;

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

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => 'Create Announcement +',
                'callback_data' => encodeCallback(self::$type, 'createAnnouncement'),
            ])
        ]);


        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => "Label",
                'callback_data' => encodeCallback('x', 'y')
            ]),
            Keyboard::inlineButton([
                'text' => "Status",
                'callback_data' => encodeCallback('x', 'y')
            ])
        ]);

        foreach ($announcements as $announcement) {
            $replyMarkup->row([
                Keyboard::inlineButton([
                    'text' => $announcement->label,
                    'callback_data' => encodeCallback(self::$type, 'show', [$announcement->id, $page])
                ]),
                Keyboard::inlineButton([
                    'text' => $announcement->sent_at?->shortRelativeToNowDiffForHumans() ?? 'Not sent yet',
                    'callback_data' => encodeCallback(self::$type, 'show', [$announcement->id, $page])
                ]),
            ]);
        }


        $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $announcements->lastPage()));

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function show(Announcement $announcement, int $lastPage)
    {
        $text = "label: $announcement->label\r\nmessage: <blockquote expandable>" . htmlspecialchars($announcement->message) . "</blockquote>\r\nsent_at: $announcement->sent_at";

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => 'preview',
                'callback_data' => encodeCallback(self::$type, 'preview', [$announcement->id])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => 'change label',
                'callback_data' => encodeCallback(self::$type, 'change', [$announcement->id, 'label', $lastPage])
            ])),
            Keyboard::inlineButton(array_filter([
                'text' => 'change message',
                'callback_data' => encodeCallback(self::$type, 'change', [$announcement->id, 'message', $lastPage])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => 'back',
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage, 0])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }
}
