<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each row represents a notifiable "event" in the project (e.g. "New appointment registration for a client",
     * "Confirmation of withdrawal for a specialist"). The admin can independently determine for each event whether
     * an SMS should be sent, an in-app notification (database) should be registered, and/or a message should be sent via the bot
     * Telegram/Yes. Rows are lazy (the first time an event occurs) with
     * default values ​​— no manual seeding is required.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('label')->nullable();
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            $table->boolean('telegram_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
