<?php

namespace TelegramBotEssentials\Announcements\Telegram\Features\Admin;

use Telegram\Bot\Keyboard\Keyboard;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementTarget;
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
                'callback_data' => encodeCallback(self::$type, 'createAnnouncement', [$page]),
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
        $lastPage = max(1, $lastPage);

        $total = $announcement->targets()->count();
        $sent = $announcement->targets()->where('status', 'sent')->count();
        $pending = $announcement->targets()->whereNull('status')->count();
        $deleted = $announcement->targets()->where('status', 'deleted')->count();
        $forbidden = $announcement->targets()->where('status', 'forbidden')->count();

        $text = __('tbe-announcements::announcements.main.text.show', [
            'label' => $announcement->label,
            'message' => htmlspecialchars($announcement->message_text ?: __('tbe-announcements::announcements.main.values.noMessage')),
            'sentAt' => $announcement->sent_at?->shortRelativeToNowDiffForHumans()
                ?? __('tbe-announcements::announcements.main.values.notSentYet'),
            'method' => self::methodLabel($announcement->method),
            'total' => $total,
            'sent' => $sent,
            'pending' => $pending,
            'deleted' => $deleted,
            'forbidden' => $forbidden,
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
                'callback_data' => encodeCallback(self::$type, 'sendingAnnouncement', [$announcement->id])
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

    /**
     * @throws InvalidPageNumber
     */
    public static function sendingAnnouncement(Announcement $announcement, int $page = 1, int $currentPage = 0): TelegramResponse
    {
        $text = __('tbe-announcements::announcements.main.text.sendingAnnouncement', [
            'label' => $announcement->label,
        ]);
        $replyMarkup = Keyboard::make()->inline();

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.applyFilters'),
                'callback_data' => encodeCallback(self::$type, 'soon', [$announcement->id])
            ]),
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.reloadTargetUsers'),
                'callback_data' => encodeCallback(self::$type, 'reloadTargetUsers', [$announcement->id, $page])
            ])
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.startSendingMessages'),
                'callback_data' => encodeCallback(self::$type, 'startSendingMessages', [$announcement->id])
            ]),
        ]);

        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe-announcements::announcements.main.keys.deleteSentMessages'),
                'callback_data' => encodeCallback(self::$type, 'deleteSentMessages', [$announcement->id])
            ]),
        ]);

        $announcementTargets = $announcement->targets()->paginate(perPage: 10, page: $page);

        TelegramPaginator::validatePageNumber($page, $currentPage, $announcementTargets);

        if (AnnouncementTarget::count() > 0) {
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

            foreach ($announcementTargets as $announcementTarget) {
                $button = self::targetStatusButtonState($announcementTarget);
                $replyMarkup->row([
                    Keyboard::inlineButton([
                        'text' => empty($announcementTarget->botUser->telegramUser->full_name) ? '???' : $announcementTarget->botUser->telegramUser->full_name,
                        'callback_data' => encodeCallback(self::$type, 'show', [$announcementTarget->id, $page])
                    ]),
                    Keyboard::inlineButton([
                        'text' => $button['text'],
                        'style' => $button['style'],
                        'callback_data' => encodeCallback(self::$type, $button['action'], [$announcementTarget->id, $page]),
                    ]),
                ]);
            }

            $replyMarkup->row(TelegramPaginator::makeNavigationButtonsRow(self::$type, $page, $announcementTargets->lastPage(), 'sendingAnnouncement', customPageMethod: 'sendingSetPage', extraParams: [$announcement->id]));
        }


        $replyMarkup->row([
            Keyboard::inlineButton([
                'text' => __('tbe::general.keys.back'),
                'callback_data' => encodeCallback(self::$type, 'show', [$announcement->id])
            ])
        ]);

        return new TelegramResponse(
            text: $text,
            replyMarkup: $replyMarkup,
            parseMode: 'HTML'
        );
    }

    protected static function targetStatusButtonState(AnnouncementTarget $announcementTarget): array
    {
        return match ($announcementTarget->status) {
            'sent' => [
                'text' => __('tbe-announcements::announcements.main.keys.targetStatus.sent'),
                'style' => 'danger',
                'action' => 'announcementTargetDelete',
            ],
            'deleted' => [
                'text' => __('tbe-announcements::announcements.main.keys.targetStatus.deleted'),
                'style' => 'success',
                'action' => 'announcementTargetSend',
            ],
            'forbidden' => [
                'text' => __('tbe-announcements::announcements.main.keys.targetStatus.forbidden'),
                'style' => 'primary',
                'action' => 'announcementTargetSend',
            ],
            default => [
                'text' => __('tbe-announcements::announcements.main.keys.targetStatus.pending'),
                'style' => 'success',
                'action' => 'announcementTargetSend',
            ],
        };
    }

    public static function makeProgressBar(int $percent, int $width = 15): string
    {
        $completed = (int) round(($percent / 100) * $width);
        $remaining = $width - $completed;

        return str_repeat('█', $completed) . str_repeat('░', $remaining);
    }

    public static function getSendingProgressText(Announcement $announcement): string
    {
        $total = $announcement->targets()->count();
        $sent = $announcement->targets()->where('status', 'sent')->count();
        $forbidden = $announcement->targets()->where('status', 'forbidden')->count();
        $processed = $sent + $forbidden;

        $percent = $total > 0 ? (int) round(($processed / $total) * 100) : 0;
        $progressBar = self::makeProgressBar($percent);

        $isFinished = $processed >= $total;

        return __('tbe-announcements::announcements.main.text.sendingProgressTemplate', [
            'status' => $isFinished
                ? __('tbe-announcements::announcements.main.status.sendingCompleted')
                : __('tbe-announcements::announcements.main.status.sendingInProgress'),
            'label' => $announcement->label,
            'percent' => $percent,
            'progressBar' => $progressBar,
            'total' => $total,
            'sent' => $sent,
            'pending' => max(0, $total - $processed),
            'forbidden' => $forbidden,
            'footer' => $isFinished
                ? __('tbe-announcements::announcements.main.status.sendingFooterCompleted')
                : __('tbe-announcements::announcements.main.status.sendingFooterProgress'),
        ]);
    }

    public static function getDeletingProgressText(Announcement $announcement): string
    {
        $total = $announcement->targets()->count();
        $deleted = $announcement->targets()->where('status', 'deleted')->count();
        $forbidden = $announcement->targets()->where('status', 'forbidden')->count();
        $pending = $announcement->targets()->where('status', 'sent')->count();
        $processed = $total - $pending;

        $percent = $total > 0 ? (int) round(($processed / $total) * 100) : 0;
        $progressBar = self::makeProgressBar($percent);

        $isFinished = $pending === 0;

        return __('tbe-announcements::announcements.main.text.deletingProgressTemplate', [
            'status' => $isFinished
                ? __('tbe-announcements::announcements.main.status.deletingCompleted')
                : __('tbe-announcements::announcements.main.status.deletingInProgress'),
            'label' => $announcement->label,
            'percent' => $percent,
            'progressBar' => $progressBar,
            'total' => $total,
            'deleted' => $deleted,
            'pending' => $pending,
            'forbidden' => $forbidden,
            'footer' => $isFinished
                ? __('tbe-announcements::announcements.main.status.deletingFooterCompleted')
                : __('tbe-announcements::announcements.main.status.deletingFooterProgress'),
        ]);
    }
}
