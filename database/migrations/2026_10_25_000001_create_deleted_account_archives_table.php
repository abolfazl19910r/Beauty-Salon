<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هویت و IP حساب‌های حذف‌شده (بسته‌ی مجوز 5د، ۲۰۲۶-۱۰-۲۵): ماده‌ی ۶۶۸ آیین دادرسی کیفری — اطلاعات کاربر و IP دست‌کم شش ماه
 * پس از خاتمه‌ی اشتراک. جدا از users، رمزنگاری‌شده (APP_KEY)، فقط ابرمدیر؛ retention:prune بعد از purge_after پاک می‌کند.
 * user_id و salon_id عمداً بی کلید خارجی: ردیف کاربر ممکن است حذف شده باشد و بایگانی باید بماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_account_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('salon_id')->nullable()->index();
            $table->string('user_type', 32)->nullable();
            $table->text('name');                      // encrypted
            $table->text('phone');                     // encrypted
            $table->char('phone_hash', 64)->index();   // HMAC برای جست‌وجو بی رمزگشایی
            $table->timestamp('account_created_at')->nullable();
            $table->longText('ip_history')->nullable(); // encrypted:array — [{ip, first_seen, last_seen, count, user_agent}]
            $table->string('deletion_ip', 45)->nullable();
            $table->text('deletion_user_agent')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('outcome', 16)->nullable(); // deleted | anonymized
            $table->timestamp('deleted_at')->nullable();
            // ⭐ nullable (مطابق قرارداد subscription_ends_at در salons): در MySQL/MariaDB با
            // explicit_defaults_for_timestamp=OFF، ستون TIMESTAMP NOT NULL بی‌ DEFAULT که اولین
            // TIMESTAMP جدول نباشد پیش‌فرض ضمنی '0000-00-00' می‌گیرد و با NO_ZERO_DATE خطای
            // 1067 می‌دهد. app همیشه مقدار می‌گذارد (deleted_at + مدت نگهداری، ماده‌ی ۶۶۸) و
            // ردیف بی purge_after را retention:prune بعد از مدت نگهداری از created_at پاک می‌کند (نه زودتر از قانون، نه هرگز).
            $table->timestamp('purge_after')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_account_archives');
    }
};
