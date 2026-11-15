<?php

namespace TelegramBotEssentials\Announcements\Services;

use TelegramBotEssentials\Announcements\DTOs\Audience;

/**
 * The groups of users a package described, by key. A singleton like the
 * other registries: packages add theirs once per worker, from boot().
 */
class Audiences
{
    /** @var array<string, Audience> */
    private array $audiences = [];

    public function add(Audience $audience): self
    {
        $this->audiences[$audience->key] = $audience;

        return $this;
    }

    /**
     * The audiences usable right now, by key.
     *
     * @return array<string, Audience>
     */
    public function all(): array
    {
        return array_filter($this->audiences, fn (Audience $audience) => $audience->isActive());
    }

    public function get(?string $key): ?Audience
    {
        $audience = $key === null ? null : ($this->audiences[$key] ?? null);

        return $audience?->isActive() ? $audience : null;
    }
}
