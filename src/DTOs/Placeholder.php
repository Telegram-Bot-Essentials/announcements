<?php

namespace TelegramBotEssentials\Announcements\DTOs;

use Closure;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * A value a package offers to announcement texts, written `{$key}` by the
 * admin and filled in for each recipient when the message is sent.
 *
 * The package formats its own value (money through the currency service,
 * dates its own way): announcements only puts the text in place.
 */
class Placeholder
{
    /**
     * @param  string  $key  letters, digits, "_" and "."; prefix it with the
     *                       package's name (`wallet.balance`). Never rename it:
     *                       saved announcements refer to it.
     * @param  string|Closure(): string  $label  shown to the admin in the list
     * @param  Closure(BotUser): (string|int|float|null)  $resolve  the value
     *                                                              for that user. It runs on the queue in the sender's context, so
     *                                                              read from $user and never from wHook()->user().
     * @param  string  $fallback  used when the value is null or empty
     * @param  string|Closure(): string|null  $example  a sample value for the admin's list
     * @param  bool|Closure(): bool  $active  an inactive placeholder is hidden
     *                                        from the list and refused in new texts
     * @param  array<int, string>  $with  BotUser relations to eager load for a
     *                                    batch of recipients
     * @param  bool  $raw  the value is Telegram HTML and must not be escaped
     */
    public function __construct(
        public string $key,
        public string|Closure $label,
        public Closure $resolve,
        public string $fallback = '',
        public string|Closure|null $example = null,
        public bool|Closure $active = true,
        public array $with = [],
        public bool $raw = false,
    ) {}

    public function isActive(): bool
    {
        return $this->active instanceof Closure
            ? (bool) ($this->active)()
            : $this->active;
    }

    public function label(): string
    {
        return $this->label instanceof Closure ? (string) ($this->label)() : $this->label;
    }

    public function example(): ?string
    {
        $example = $this->example instanceof Closure ? (string) ($this->example)() : $this->example;

        return $example === '' ? null : $example;
    }

    /** The text to put in place of `{$key}` for this user, already safe for Telegram HTML. */
    public function valueFor(BotUser $user): string
    {
        $value = ($this->resolve)($user);
        $value = $value === null ? '' : (string) $value;

        if ($value === '') {
            $value = $this->fallback;
        }

        return $this->raw ? $value : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
