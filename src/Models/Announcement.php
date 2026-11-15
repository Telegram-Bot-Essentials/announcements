<?php

namespace TelegramBotEssentials\Announcements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Telegram\Bot\Objects\Message;
use TelegramBotEssentials\Announcements\Database\Factories\AnnouncementFactory;
use TelegramBotEssentials\Essence\Exceptions\LogicException;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class Announcement extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    /**
     * Whether the text is filled in for each recipient. Copies and forwards
     * re-send Telegram's own message, which cannot be changed.
     */
    public function usesPlaceholders(): bool
    {
        return $this->method === 'html' && announcementPlaceholders()->keysIn($this->message_text) !== [];
    }

    /**
     * With $recipient, an HTML announcement's placeholders are filled in for
     * that user; without, the text goes out as written.
     *
     * @throws LogicException
     */
    public function sendTo(int $chatId, ?BotUser $recipient = null): Message
    {
        switch ($this->method) {
            case 'copy':
                dependsOn($this->message_id);
                dependsOn($this->from_chat_id);

                return wHook()->api()->copyMessage([
                    'chat_id' => $chatId,
                    'from_chat_id' => $this->from_chat_id,
                    'message_id' => $this->message_id,
                ]);
                break;
            case 'forward':
                dependsOn($this->message_id);
                dependsOn($this->from_chat_id);

                return wHook()->api()->forwardMessage([
                    'chat_id' => $chatId,
                    'from_chat_id' => $this->from_chat_id,
                    'message_id' => $this->message_id,
                ]);
                break;
            case 'html':
                dependsOn(
                    $this->message_text,
                    __('tbe-announcements::announcements.main.text.messageRequiredForHtml')
                );

                return wHook()->api()->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $recipient === null
                        ? $this->message_text
                        : announcementPlaceholders()->render($this->message_text ?? '', $recipient),
                    'parse_mode' => 'HTML',
                    'reply_markup' => replyKeyboard(),
                ]);
                break;
            default:
                throw new LogicException("Unsupported announcement method: {$this->method}");
        }
    }
}
