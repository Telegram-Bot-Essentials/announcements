<?php

namespace TelegramBotEssentials\Announcements\DTOs;

use Closure;

/**
 * A number an admin can tune for one alert: "at least this much credit",
 * "after this many days". The alert's audience reads it with
 * `announcementAlerts()->param()`, and its text can show it as
 * `{$alert.<key>}`.
 */
class AlertParam
{
    /**
     * @param  string  $key  letters, digits and "_"
     * @param  string|Closure(): string  $label  shown to the admin
     * @param  (Closure(int): string)|null  $format  how the value reads in a
     *                                               text (money through the currency service); the bare number otherwise
     */
    public function __construct(
        public string $key,
        public string|Closure $label,
        public int $default,
        public string|Closure|null $description = null,
        public ?Closure $format = null,
    ) {}

    public function format(int $value): string
    {
        return $this->format === null ? (string) $value : (string) ($this->format)($value);
    }
}
