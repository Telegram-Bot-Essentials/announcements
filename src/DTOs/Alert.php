<?php

namespace TelegramBotEssentials\Announcements\DTOs;

use Closure;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * A message the bot sends on its own to the users of an audience: "you
 * have credit to spend", "you never took your free test". The package that
 * knows the subject registers it; an admin switches it on per bot, and may
 * reword it and tune its numbers.
 */
class Alert
{
    /**
     * @param  string  $key  prefix it with the package's name. Never rename
     *                       it: it is how "this user already got it" is remembered.
     * @param  string|Closure(): string  $label  shown to the admin
     * @param  string  $audience  key of the audience it is sent to
     * @param  Closure(): string  $text  the default wording, in Telegram HTML,
     *                                   with `{$placeholders}` and `{$alert.<param>}`
     * @param  array<int, AlertParam>  $params  numbers the admin can tune
     * @param  int  $repeatDays  default for "send again after this many days
     *                           while the user still matches"; 0 sends once ever
     * @param  int  $priority  when a user matches several alerts at once the
     *                         lowest goes first, and the rest wait for the cap to pass
     * @param  (Closure(BotUser): array<int, array<int, array<string, mixed>>>)|null  $keyboard
     *                                                                                           rows of inline buttons under the message
     * @param  bool|Closure(): bool  $active  an inactive alert is hidden and never sent
     */
    public function __construct(
        public string $key,
        public string|Closure $label,
        public string $audience,
        public Closure $text,
        public array $params = [],
        public int $repeatDays = 0,
        public int $priority = 100,
        public ?Closure $keyboard = null,
        public bool|Closure $active = true,
        public string|Closure|null $description = null,
    ) {}

    public function isActive(): bool
    {
        return $this->active instanceof Closure ? (bool) ($this->active)() : $this->active;
    }

    public function label(): string
    {
        return $this->label instanceof Closure ? (string) ($this->label)() : $this->label;
    }

    public function defaultText(): string
    {
        return (string) ($this->text)();
    }

    public function param(string $key): ?AlertParam
    {
        foreach ($this->params as $param) {
            if ($param->key === $key) {
                return $param;
            }
        }

        return null;
    }

    /** The key as one segment of a settings path: dots would nest it. */
    public function slug(): string
    {
        return str_replace('.', '_', $this->key);
    }
}
