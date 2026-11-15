<?php

namespace TelegramBotEssentials\Announcements\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use TelegramBotEssentials\Announcements\DTOs\Alert;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Announcements\Models\AnnouncementMute;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

/**
 * One pass over a bot's automatic alerts: for each one switched on, find
 * who is due and queue it for them as an announcement of its own, so the
 * delivery tracking, the chart and retraction all work as for any other.
 *
 * Runs in the bot's context (wHook()), with no current user.
 */
class AlertSweep
{
    public function __construct(
        private readonly Alerts $alerts,
        private readonly Audiences $audiences,
        private readonly Broadcasts $broadcasts,
    ) {}

    /** @return int how many messages were queued */
    public function run(Bot $bot, ?CarbonInterface $now = null): int
    {
        $now ??= now();
        if (! $this->alerts->inSendingHours($now)) {
            return 0;
        }

        $queued = 0;
        foreach ($this->alerts->all() as $alert) {
            if ($this->alerts->enabled($alert)) {
                $queued += $this->send($bot, $alert, $now);
            }
        }

        return $queued;
    }

    /**
     * Who would get this alert now: in its audience, reachable, not muted,
     * not sent it already (or not within its repeat period), and not sent
     * any other alert within the cap.
     *
     * @return Builder<BotUser>
     */
    public function due(Bot $bot, Alert $alert, CarbonInterface $now): Builder
    {
        $query = BotUser::query()
            ->where('bot_users.bot_id', $bot->getKey())
            ->reachable()
            ->notSuspended();

        $audience = $this->audiences->get($alert->audience);
        if ($audience === null) {
            return $query->whereRaw('1 = 0');
        }
        $audience->applyTo($query);

        $query->whereNotIn('bot_users.id', AnnouncementMute::query()->select('bot_user_id'));

        $repeatDays = $this->alerts->repeatDays($alert);
        $query->whereNotExists(function (QueryBuilder $sent) use ($alert, $repeatDays, $now) {
            $this->delivered($sent)->where('announcement_targets.alert_key', $alert->key);
            if ($repeatDays > 0) {
                $sent->where('announcement_targets.created_at', '>', $now->copy()->subDays($repeatDays));
            }
        });

        $capHours = $this->alerts->capHours();
        if ($capHours > 0) {
            $query->whereNotExists(fn (QueryBuilder $sent) => $this->delivered($sent)
                ->whereNotNull('announcement_targets.alert_key')
                ->where('announcement_targets.created_at', '>', $now->copy()->subHours($capHours)));
        }

        return $query;
    }

    private function send(Bot $bot, Alert $alert, CarbonInterface $now): int
    {
        $userIds = $this->due($bot, $alert, $now)->pluck('bot_users.id')->all();
        if ($userIds === []) {
            return 0;
        }

        $announcement = Announcement::query()->create([
            'bot_id' => $bot->getKey(),
            'bot_user_id' => null,
            'method' => 'html',
            'message_text' => $this->alerts->text($alert),
            'label' => $alert->label(),
            'alert_key' => $alert->key,
        ]);

        foreach (array_chunk($userIds, 500) as $chunk) {
            DB::table('announcement_targets')->insertOrIgnore(array_map(fn (mixed $userId) => [
                'bot_id' => $bot->getKey(),
                'announcement_id' => $announcement->getKey(),
                'bot_user_id' => $userId,
                'alert_key' => $alert->key,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        return $this->broadcasts->send($announcement);
    }

    /**
     * Targets of the user in the outer query that count as "they got it":
     * delivered (even if retracted since), or queued and on its way. A
     * failed or skipped one does not, so it is tried again next pass.
     */
    private function delivered(QueryBuilder $targets): QueryBuilder
    {
        return $targets->select(DB::raw('1'))
            ->from('announcement_targets')
            ->whereColumn('announcement_targets.bot_user_id', 'bot_users.id')
            ->where(fn (QueryBuilder $status) => $status
                ->whereNull('announcement_targets.status')
                ->orWhereIn('announcement_targets.status', ['sent', 'deleted']));
    }
}
