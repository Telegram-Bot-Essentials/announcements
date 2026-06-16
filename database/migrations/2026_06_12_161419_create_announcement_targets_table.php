<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TelegramBotEssentials\Announcements\Models\Announcement;
use TelegramBotEssentials\Essence\Models\Bot;
use TelegramBotEssentials\Essence\Models\BotUser;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Bot::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Announcement::class)->constrained()->cascadeOnDelete();

            $table->foreignIdFor(BotUser::class)->constrained()->cascadeOnDelete();
            $table->enum('status', ['sent', 'deleted', 'forbidden'])->nullable();
            $table->bigInteger('message_id')->nullable();

            $table->unique(['announcement_id', 'bot_user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
