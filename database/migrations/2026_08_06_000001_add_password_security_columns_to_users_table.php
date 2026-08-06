<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * password_changed_at: از قبل توسط SecurityController خونده می‌شد ولی هیچ‌وقت
     * ستونی براش وجود نداشت (و هیچ‌جا هم نوشته نمی‌شد).
     *
     * password_strength_score: باگ واقعی کشف‌شده حین این فاز — امتیاز امنیتی قبلاً
     * قدرت رمز عبور رو مستقیم از روی HASH ذخیره‌شده (نه خود رمز) دوباره محاسبه می‌کرد؛
     * چون هش bcrypt همیشه ۶۰ کاراکتری و پر از حروف بزرگ/کوچک/عدد/کاراکتر خاصه، این
     * محاسبه همیشه تقریباً بیشترین امتیاز ممکن رو می‌داد، فارغ از قدرت واقعی رمز کاربر.
     * راه‌حل درست: امتیاز فقط یک‌بار، لحظه‌ی ثبت‌نام/تغییر رمز (وقتی خود رمز خام هنوز
     * در دسترسه، قبل از هش شدن) محاسبه و همینجا ذخیره می‌شه؛ دیگه هیچ‌وقت از روی هش
     * بازسازی نمی‌شه.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->unsignedTinyInteger('password_strength_score')->nullable()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'password_strength_score']);
        });
    }
};
