<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی «ح»، ۳ (۲۰۲۶-۱۰-۱۹): ثبت‌نام با «خرید» پیامک خوش‌آمد را تا نتیجه‌ی پرداخت نگه می‌دارد (App\Services\SalonSignup\SalonWelcome).
 * پر = هنوز نرفته؛ اولین مسیری که خالی‌اش کند (نتیجه‌ی پرداخت یا مهلت پشتیبان) پیامک را می‌فرستد — همین یک‌بار بودن را تضمین می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->timestamp('welcome_sms_pending_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('welcome_sms_pending_at');
        });
    }
};
