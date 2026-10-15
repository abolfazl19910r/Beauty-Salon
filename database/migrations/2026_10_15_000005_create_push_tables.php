<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پوش اپ‌ها (بسته‌ی ۴، ۲۰۲۶-۱۰-۱۵).
 *
 * - push_devices: گیرنده‌ی پوش هر نشست اپ؛ به ردیف personal_access_tokens گره خورده (cascade) تا خروج، ابطال توکن یا
 *   انقضای ۹۰ روزه پوش همان گوشی را هم قطع کند (بسته‌ی ۱).
 * - push_messages: یک پیام منطقی به یک نفر، با متن پیامک پشتیبانش (همان لحظه ساخته می‌شود تا متن اختصاصی سالن بماند)،
 *   مهلت «بدون رسید → پیامک» و نتیجه.
 * - push_deliveries: ارسال همان پیام به هر دستگاه و رسید هر دستگاه (سلامت دستگاه = دو ارسال آخر بی‌رسید).
 * - notification_settings.push_enabled: ستون «اپ» تنظیمات اعلان هر سالن.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('personal_access_token_id')->unique()->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->foreignId('salon_id')->nullable()->constrained('salons')->nullOnDelete();
            $table->string('app', 16);                 // customer | staff
            $table->string('provider', 16)->default('fcm'); // fcm | ntfy
            $table->string('token', 512);
            $table->char('token_hash', 64)->index();   // یکتایی توکن بین حساب‌ها (ستون ۵۱۲ کاراکتری ایندکس یکتا نمی‌گیرد)
            $table->string('app_version', 32)->nullable();
            $table->string('manufacturer', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->timestamp('last_receipt_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->string('disabled_reason', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'disabled_at']);
        });

        Schema::create('push_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('salon_id')->nullable()->constrained('salons')->nullOnDelete();
            $table->string('app', 16);
            $table->string('event_key', 80)->nullable();
            $table->string('urgency', 16);             // urgent | normal | info
            $table->string('mode', 16);                // shadow | live (حالت پلتفرم در لحظه‌ی ارسال)
            $table->string('title', 120);
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('sms_phone', 20)->nullable();
            $table->text('sms_text')->nullable();
            $table->string('sms_feature', 32)->nullable();
            $table->unsignedTinyInteger('sms_parts')->default(0);
            $table->timestamp('fallback_due_at')->nullable();
            $table->timestamp('acked_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->string('sms_reason', 16)->nullable(); // shadow | no_receipt | unhealthy | outage
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['fallback_due_at', 'acked_at', 'sms_sent_at']);
        });

        Schema::create('push_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('push_message_id');
            $table->foreign('push_message_id')->references('id')->on('push_messages')->cascadeOnDelete();
            $table->foreignId('push_device_id')->constrained('push_devices')->cascadeOnDelete();
            $table->string('status', 16)->default('queued'); // queued | accepted | failed | invalid
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('error', 190)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('acked_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->unique(['push_message_id', 'push_device_id']);
            $table->index(['push_device_id', 'id']);
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('push_enabled')->default(true)->after('telegram_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn('push_enabled');
        });
        Schema::dropIfExists('push_deliveries');
        Schema::dropIfExists('push_messages');
        Schema::dropIfExists('push_devices');
    }
};
