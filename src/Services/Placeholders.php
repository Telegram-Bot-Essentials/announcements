<?php

namespace TelegramBotEssentials\Announcements\Services;

use InvalidArgumentException;
use TelegramBotEssentials\Announcements\DTOs\Placeholder;
use TelegramBotEssentials\Essence\Models\BotUser;
use Throwable;

/**
 * The `{$key}` values announcement texts can use. A singleton like the other
 * registries: packages add theirs once per worker, from boot().
 */
class Placeholders
{
    private const PATTERN = '/\{\$([A-Za-z0-9_.]+)\}/';

    /** @var array<string, Placeholder> */
    private array $placeholders = [];

    public function add(Placeholder $placeholder): self
    {
        if (preg_match('/^[A-Za-z0-9_.]+$/', $placeholder->key) !== 1) {
            throw new InvalidArgumentException(sprintf('Placeholder key "%s" may only hold letters, digits, "_" and ".".', $placeholder->key));
        }

        $this->placeholders[$placeholder->key] = $placeholder;

        return $this;
    }

    /**
     * The placeholders an admin can use right now, by key.
     *
     * @return array<string, Placeholder>
     */
    public function all(): array
    {
        return array_filter($this->placeholders, fn (Placeholder $placeholder) => $placeholder->isActive());
    }

    public function get(string $key): ?Placeholder
    {
        $placeholder = $this->placeholders[$key] ?? null;

        return $placeholder?->isActive() ? $placeholder : null;
    }

    /**
     * The keys written in a text, in order of appearance.
     *
     * @return array<int, string>
     */
    public function keysIn(?string $text): array
    {
        if ($text === null || preg_match_all(self::PATTERN, $text, $matches) === false) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }

    /**
     * The keys in a text that nothing can fill in: never registered, or
     * switched off. $extraKeys are values the caller fills in itself.
     *
     * @param  array<int, string>  $extraKeys
     * @return array<int, string>
     */
    public function unknownIn(?string $text, array $extraKeys = []): array
    {
        return array_values(array_filter(
            $this->keysIn($text),
            fn (string $key) => $this->get($key) === null && ! in_array($key, $extraKeys, true),
        ));
    }

    /**
     * The BotUser relations the placeholders of a text read, to eager load
     * for a batch of recipients.
     *
     * @return array<int, string>
     */
    public function relationsFor(?string $text): array
    {
        $relations = [];
        foreach ($this->keysIn($text) as $key) {
            $relations = [...$relations, ...($this->get($key)->with ?? [])];
        }

        return array_values(array_unique($relations));
    }

    /**
     * The text with every placeholder filled in for this user. It never
     * throws: a key nothing can fill in becomes an empty string, and one whose
     * package failed becomes its fallback, so one broken value does not stop
     * a broadcast.
     *
     * @param  array<string, string>  $values  ready-made values by key, which
     *                                         win over a registered placeholder; put in as given, so escape them
     */
    public function render(string $text, BotUser $user, array $values = []): string
    {

        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($user, &$values): string {
            $key = $match[1];

            return $values[$key] ??= $this->valueOf($key, $user);
        }, $text);
    }

    private function valueOf(string $key, BotUser $user): string
    {
        $placeholder = $this->get($key);
        if ($placeholder === null) {
            return '';
        }

        try {
            return $placeholder->valueFor($user);
        } catch (Throwable $exception) {
            report($exception);

            return $placeholder->raw ? $placeholder->fallback : htmlspecialchars($placeholder->fallback, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        }
    }
}
