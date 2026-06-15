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

    /**
     * @throws InvalidPageNumber
     */
    public static function menu(int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $announcements = Announcement::query()->orderByDesc('id')->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $announcements);

        $text = Announcement::count() === 0
            ? __('tbe-announcements::announcements.main.text.menu_empty')
            : __('tbe-announcements::announcements.main.text.menu');

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.create'),
                'callback_data' => encodeCallback(self::$type, 'createAnnouncement', [$currentPage]),
            ])
        ]);

        if (Announcement::count() == 0) {
            return new TelegramResponse(
                text: $text,
                replyMarkup: $replyMarkup,
                parseMode: 'HTML'
            );
        }

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.columnLabel'),
                'callback_data' => encodeCallback('x', 'y')
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.columnStatus'),
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
                    'text' => $announcement->sent_at?->shortRelativeToNowDiffForHumans()
                        ?? __('tbe-announcements::announcements.main.keys.notSentYet'),
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

    public static function show(Announcement $announcement, int $lastPage = 1): TelegramResponse
    {
        $text = __('tbe-announcements::announcements.main.text.show', [
            'label' => $announcement->label,
            'message' => htmlspecialchars($announcement->message_text ?: __('tbe-announcements::announcements.main.values.noMessage')),
            'sentAt' => $announcement->sent_at?->shortRelativeToNowDiffForHumans()
                ?? __('tbe-announcements::announcements.main.values.notSentYet'),
            'method' => self::methodLabel($announcement->method),
        ]);

        $replyMarkup = Keyboard::make()
            ->inline();

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-announcements::announcements.main.keys.preview'),
                'callback_data' => encodeCallback(self::$type, 'preview', [$announcement->id])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-announcements::announcements.main.keys.send'),
                'callback_data' => encodeCallback(self::$type, 'soon', [$announcement->id])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-announcements::announcements.main.keys.changeLabel'),
                'callback_data' => encodeCallback(self::$type, 'change', [$announcement->id, 'label', $lastPage])
            ])),
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-announcements::announcements.main.keys.method', [
                    'method' => self::methodLabel($announcement->method),
                ]),
                'callback_data' => encodeCallback(self::$type, 'changeMethod', [$announcement->id, $announcement->method, $lastPage])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton(array_filter([
                'text' => __('tbe-announcements::announcements.main.keys.setMessage'),
                'callback_data' => encodeCallback(self::$type, 'setMessage', [$announcement->id, $lastPage])
            ]))
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'start', [$lastPage, 0])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    public static function methodLabel(string $method): string
    {
        return __('tbe-announcements::announcements.main.methods.' . $method);
    }
}
