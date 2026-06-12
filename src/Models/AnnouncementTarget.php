<?php

namespace TelegramBotEssentials\Announcements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use TelegramBotEssentials\Announcements\Database\Factories\AnnouncementTargetFactory;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

class AnnouncementTarget extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'is_deleted' => 'boolean',
        'is_forbidden' => 'boolean',
    ];

    public static function newFactory(): AnnouncementTargetFactory
    {
        return AnnouncementTargetFactory::new();
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class);
    }
}
