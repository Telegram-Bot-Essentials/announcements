<?php

namespace TelegramBotEssentials\Announcements\Services;

use Carbon\CarbonInterface;
use Closure;
use TelegramBotEssentials\Announcements\DTOs\Alert;
use TelegramBotEssentials\Settings\DTOs\Setting;
use TelegramBotEssentials\Settings\Enums\SettingType;

/**
 * The automatic alerts known to the bot, by key, and what an admin chose
 * for each. A singleton like the other registries: packages register
 * theirs once per worker, from boot().
 *
 * Everything an admin can change lives in settings, under
 * `announcements.alerts`, so both the bot and the web panel can edit it.
 */
class Alerts
{
    public const SETTINGS = 'announcements.alerts';

    /** @var array<string, Alert> */
    private array $alerts = [];

    public function register(Alert $alert): self
    {
        $this->alerts[$alert->key] = $alert;
        $this->addSettings($alert);

        return $this;
    }

    /**
     * The alerts usable right now, the most urgent first.
     *
     * @return array<string, Alert>
     */
    public function all(): array
    {
        $alerts = array_filter($this->alerts, fn (Alert $alert) => $alert->isActive());
        uasort($alerts, fn (Alert $a, Alert $b) => $a->priority <=> $b->priority);

        return $alerts;
    }

    /** Whether any package registered an alert at all, active or not. */
    public function any(): bool
    {
        return $this->alerts !== [];
    }

    public function get(?string $key): ?Alert
    {
        $alert = $key === null ? null : ($this->alerts[$key] ?? null);

        return $alert?->isActive() ? $alert : null;
    }

    /** Whether an admin switched this alert on for the current bot. */
    public function enabled(Alert $alert): bool
    {
        return (bool) settings()->get($this->settingKey($alert, 'status'));
    }

    /** The admin's wording, or the package's when they left it alone. */
    public function text(Alert $alert): string
    {
        $custom = settings()->get($this->settingKey($alert, 'text'));

        return is_string($custom) && trim($custom) !== '' ? $custom : $alert->defaultText();
    }

    /** Days before a user who still matches gets it again; 0 means once ever. */
    public function repeatDays(Alert $alert): int
    {
        return $this->number($this->settingKey($alert, 'repeat_days'), $alert->repeatDays);
    }

    /** What the admin set for one of an alert's numbers, for its audience to read. */
    public function param(string $alertKey, string $param): int
    {
        $alert = $this->alerts[$alertKey] ?? null;
        $definition = $alert?->param($param);
        if ($alert === null || $definition === null) {
            return 0;
        }

        return $this->number($this->settingKey($alert, $param), $definition->default);
    }

    /**
     * An alert's numbers as its text shows them, by placeholder key
     * (`alert.<param>`).
     *
     * @return array<string, string>
     */
    public function values(Alert $alert): array
    {
        $values = [];
        foreach ($alert->params as $param) {
            $values['alert.'.$param->key] = $param->format($this->param($alert->key, $param->key));
        }

        return $values;
    }

    /** Hours in which a user gets at most one automatic alert; 0 lifts the cap. */
    public function capHours(): int
    {
        return $this->number(self::SETTINGS.'.cap_hours', 24);
    }

    /** Whether alerts may go out now: users are not messaged at night. */
    public function inSendingHours(CarbonInterface $now): bool
    {
        $from = min(23, $this->number(self::SETTINGS.'.send_from_hour', 10));
        $to = min(24, $this->number(self::SETTINGS.'.send_to_hour', 22));

        if ($from === $to) {
            return true;
        }

        return $from < $to
            ? $now->hour >= $from && $now->hour < $to
            : $now->hour >= $from || $now->hour < $to;
    }

    public function settingKey(Alert $alert, string $name): string
    {
        return self::SETTINGS.'.'.$alert->slug().'.'.$name;
    }

    /** The switches every bot has, whatever alerts are installed. */
    public function addGlobalSettings(): void
    {
        $label = fn (string $key): Closure => fn (): string => self::lang('settings.'.$key);

        settings()->addSetting(new Setting(
            key: 'announcements',
            label: $label('announcements'),
            type: SettingType::DIRECTORY,
        ));
        settings()->addSetting(new Setting(
            key: self::SETTINGS,
            label: $label('alerts'),
            type: SettingType::DIRECTORY,
            description: $label('alerts_description'),
        ));
        settings()->addSetting(new Setting(
            key: self::SETTINGS.'.cap_hours',
            label: $label('cap_hours'),
            type: SettingType::NUMBER,
            default: 24,
            description: $label('cap_hours_description'),
            rules: 'integer|min:0|max:8760',
        ));
        settings()->addSetting(new Setting(
            key: self::SETTINGS.'.send_from_hour',
            label: $label('send_from_hour'),
            type: SettingType::NUMBER,
            default: 10,
            description: $label('send_hours_description'),
            rules: 'integer|min:0|max:23',
        ));
        settings()->addSetting(new Setting(
            key: self::SETTINGS.'.send_to_hour',
            label: $label('send_to_hour'),
            type: SettingType::NUMBER,
            default: 22,
            description: $label('send_hours_description'),
            rules: 'integer|min:0|max:24',
        ));
    }

    private function addSettings(Alert $alert): void
    {
        $label = fn (string $key): Closure => fn (): string => self::lang('settings.'.$key);

        settings()->addSetting(new Setting(
            key: self::SETTINGS.'.'.$alert->slug(),
            label: fn (): string => $alert->label(),
            type: SettingType::DIRECTORY,
            description: $alert->description,
        ));
        settings()->addSetting(new Setting(
            key: $this->settingKey($alert, 'status'),
            label: $label('alert_status'),
            type: SettingType::CHECKBOX,
            default: false,
            description: $label('alert_status_description'),
        ));
        settings()->addSetting(new Setting(
            key: $this->settingKey($alert, 'text'),
            label: $label('alert_text'),
            type: SettingType::TEXT,
            description: fn (): string => self::lang('settings.alert_text_description', [
                'default' => $alert->defaultText(),
                'placeholders' => implode(', ', array_map(
                    fn (string $key) => '{$'.$key.'}',
                    [...array_keys($this->values($alert)), ...array_keys(announcementPlaceholders()->all())],
                )),
            ]),
            // "-" puts the package's own wording back.
            beforeSet: fn (mixed $value): mixed => is_string($value) && trim($value) === '-' ? null : $value,
            rules: fn (): array => ['max:3500', function (string $attribute, mixed $value, Closure $fail) use ($alert): void {
                $unknown = announcementPlaceholders()->unknownIn(is_string($value) ? $value : null, array_keys($this->values($alert)));
                if ($unknown !== []) {
                    $fail(self::lang('settings.unknown_placeholders', [
                        'keys' => implode(', ', array_map(fn (string $key) => '{$'.$key.'}', $unknown)),
                    ]));
                }
            }],
        ));
        settings()->addSetting(new Setting(
            key: $this->settingKey($alert, 'repeat_days'),
            label: $label('alert_repeat_days'),
            type: SettingType::NUMBER,
            default: $alert->repeatDays,
            description: $label('alert_repeat_days_description'),
            rules: 'integer|min:0|max:3650',
        ));

        foreach ($alert->params as $param) {
            settings()->addSetting(new Setting(
                key: $this->settingKey($alert, $param->key),
                label: $param->label,
                type: SettingType::NUMBER,
                default: $param->default,
                description: $param->description,
                rules: 'integer|min:0',
            ));
        }
    }

    private function number(string $setting, int $default): int
    {
        $value = settings()->get($setting);

        return is_numeric($value) ? max(0, (int) $value) : $default;
    }

    /** @param  array<string, string>  $replace */
    private static function lang(string $key, array $replace = []): string
    {
        $text = __('tbe-announcements::alerts.'.$key, $replace);

        return is_string($text) ? $text : $key;
    }
}
