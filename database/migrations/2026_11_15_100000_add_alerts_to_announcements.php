<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

return new class extends Migration
{
    /**
     * An automatic alert is an announcement nobody wrote: it has no author,
     * and names the alert that made it. Its targets carry the alert too, so
     * "did this user already get it" is one indexed lookup.
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignIdFor(BotUser::class)->nullable()->change();
            $table->string('alert_key')->nullable()->index();
            $table->string('audience_key')->nullable();
            $table->boolean('include_muted')->default(true);
        });

        Schema::table('announcement_targets', function (Blueprint $table) {
            $table->string('alert_key')->nullable();
            $table->index(['bot_user_id', 'alert_key', 'created_at'], 'announcement_targets_alert_lookup');
        });

        Schema::create('announcement_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(BotUser::class)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bot_id', 'bot_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_mutes');

        Schema::table('announcement_targets', function (Blueprint $table) {
            $table->dropIndex('announcement_targets_alert_lookup');
            $table->dropColumn('alert_key');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['alert_key', 'audience_key', 'include_muted']);
        });
    }
};
