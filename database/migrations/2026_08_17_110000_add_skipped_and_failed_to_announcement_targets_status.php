<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits what used to be lumped into "forbidden".
     *
     * skipped: never attempted, because the user is known to be unreachable.
     * failed:  attempted and failed for a reason that says nothing about the
     *          user (rate limit, Telegram outage, bad markup), so it must not
     *          be recorded as if they had blocked the bot.
     */
    public function up(): void
    {
        Schema::table('announcement_targets', function (Blueprint $table) {
            $table->enum('status', ['sent', 'deleted', 'forbidden', 'skipped', 'failed'])
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('announcement_targets')
            ->whereIn('status', ['skipped', 'failed'])
            ->update(['status' => 'forbidden']);

        Schema::table('announcement_targets', function (Blueprint $table) {
            $table->enum('status', ['sent', 'deleted', 'forbidden'])
                ->nullable()
                ->change();
        });
    }
};
