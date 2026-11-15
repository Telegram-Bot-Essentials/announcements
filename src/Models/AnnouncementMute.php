<?php

namespace TelegramBotEssentials\Announcements\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/** A user who asked not to get automatic alerts from this bot. */
class AnnouncementMute extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
