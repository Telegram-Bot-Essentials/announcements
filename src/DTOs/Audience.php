<?php

namespace TelegramBotEssentials\Announcements\DTOs;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * A group of users a package can describe as a constraint on the bot's
 * users: "has wallet credit", "never claimed the free test". An automatic
 * alert is sent to one, and an admin can aim a manual announcement at one.
 */
class Audience
{
    /**
     * @param  string  $key  prefix it with the package's name. Never rename
     *                       it: alerts and saved announcements refer to it.
     * @param  string|Closure(): string  $label  shown to the admin
     * @param  Closure(Builder<BotUser>): mixed  $apply  narrows the query to
     *                                                   the audience. It runs in the bot's context with no current user.
     * @param  bool|Closure(): bool  $active  an inactive audience is hidden
     *                                        and matches nobody
     */
    public function __construct(
        public string $key,
        public string|Closure $label,
        public Closure $apply,
        public bool|Closure $active = true,
    ) {}

    public function isActive(): bool
    {
        return $this->active instanceof Closure ? (bool) ($this->active)() : $this->active;
    }

    public function label(): string
    {
        return $this->label instanceof Closure ? (string) ($this->label)() : $this->label;
    }

    /**
     * @param  Builder<BotUser>  $query
     * @return Builder<BotUser>
     */
    public function applyTo(Builder $query): Builder
    {
        ($this->apply)($query);

        return $query;
    }
}
