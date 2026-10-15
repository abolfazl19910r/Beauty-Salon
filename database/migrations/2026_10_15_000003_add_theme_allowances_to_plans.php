<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ دسترسی به قالب‌ها بر اساس پلن و بازه (تصمیم ابوالفضل ۲۰۲۶-۱۰-۱۵؛ عددها «علاوه بر پیش‌فرض»):
 *   پلن          ۱ و ۳ ماهه   ۶ ماهه   ۱۲ ماهه   قالب پنل مدیر
 *   آزمایشی         ۰           ۰         ۰           ۰
 *   پایه            ۰           ۱         ۲           ۰
 *   حرفه‌ای          ۴           ۵         ۶           ۱
 *   کامل            ۴           ۶         ۱۲ (همه)    ۱
 *   کلینیک          ۱۲          ۱۲        ۱۲          ۳ (همه)
 * پلن‌های دیگر ۰. همه از «قالب‌ها»ی پنل ابرمدیر قابل ویرایش‌اند.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'trial' => [0, 0, 0, 0],
        'basic' => [0, 1, 2, 0],
        'pro' => [4, 5, 6, 1],
        'full' => [4, 6, 12, 1],
        'clinic' => [12, 12, 12, 3],
    ];

    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('theme_site_count')->default(0)->after('trial_days');
            $table->unsignedTinyInteger('theme_site_count_6m')->default(0)->after('theme_site_count');
            $table->unsignedTinyInteger('theme_site_count_12m')->default(0)->after('theme_site_count_6m');
            $table->unsignedTinyInteger('theme_admin_count')->default(0)->after('theme_site_count_12m');
        });

        foreach (self::DEFAULTS as $key => [$base, $six, $twelve, $admin]) {
            DB::table('plans')->where('key', $key)->update([
                'theme_site_count' => $base, 'theme_site_count_6m' => $six, 'theme_site_count_12m' => $twelve, 'theme_admin_count' => $admin,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m', 'theme_admin_count']);
        });
    }
};
