<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ بسته‌ی «و» (تصمیم ابوالفضل ۲۰۲۶-۱۰-۱۵، پیش‌نمایش «قالب‌های ماهرو»):
 *
 * ۱) دسترسی قالب‌ها از «چند قالب» به «چند چیدمان × چند پالت» (۴ × ۴ = ۱۶، جدا از طلایی ماهرو):
 *      پلن        ۱ و ۳ ماهه    ۶ ماهه     ۱۲ ماهه     پنل مدیر
 *      آزمایشی       ۰            —          —          ۰
 *      پایه          ۰           ۱×۱        ۲×۱          ۰
 *      حرفه‌ای        ۲×۲          ۳×۲        ۴×۲          ۱
 *      کامل          ۴×۲          ۴×۳        ۴×۴ (همه)    ۲
 *      کلینیک        ۴×۴          ۴×۴        ۴×۴          ۳ (همه)
 *    ستون‌های theme_site_count* (پچ ۰۰۹۱) جای خود را به theme_layouts و theme_palettes (هر بازه) می‌دهند.
 * ۲) انتخاب سالن: salons.site_theme («چیدمان:پالت» یا mahru) و salons.admin_theme (side/rail/top/dense)؛ null = پیش‌فرض.
 * ۳) چهار پلن عمومی متفاوت (پایه / حرفه‌ای / کامل / کلینیک): «پایه» و «حرفه‌ای» عمومی می‌شوند؛ «کامل» = حرفه‌ای + ارسال دستی به
 *    مشتری‌های قدیمی، ۱۲ متخصص، ۳٬۰۰۰ پیامک، ماهانه ۱٫۹ میلیون؛ ترتیب نمایش پایه ← حرفه‌ای ← کامل ← کلینیک. این‌ها فقط روی
 *    ردیف‌هایی اعمال می‌شوند که هنوز همان مقدار پیش‌فرض پچ ۰۰۷۴ را دارند (ویرایش ابرمدیر بازنویسی نمی‌شود).
 */
return new class extends Migration
{
    /** [layouts, palettes, layouts6, palettes6, layouts12, palettes12, admin] */
    private const GRID = [
        'trial' => [0, 0, 0, 0, 0, 0, 0],
        'basic' => [0, 0, 1, 1, 2, 1, 0],
        'pro' => [2, 2, 3, 2, 4, 2, 1],
        'full' => [4, 2, 4, 3, 4, 4, 2],
        'clinic' => [4, 4, 4, 4, 4, 4, 3],
    ];

    private const FIELDS = ['theme_layouts', 'theme_palettes', 'theme_layouts_6m', 'theme_palettes_6m', 'theme_layouts_12m', 'theme_palettes_12m', 'theme_admin_count'];

    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('theme_layouts')->default(0)->after('theme_admin_count');
            $table->unsignedTinyInteger('theme_palettes')->default(0)->after('theme_layouts');
            $table->unsignedTinyInteger('theme_layouts_6m')->default(0)->after('theme_palettes');
            $table->unsignedTinyInteger('theme_palettes_6m')->default(0)->after('theme_layouts_6m');
            $table->unsignedTinyInteger('theme_layouts_12m')->default(0)->after('theme_palettes_6m');
            $table->unsignedTinyInteger('theme_palettes_12m')->default(0)->after('theme_layouts_12m');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m']);
        });

        foreach (self::GRID as $key => $values) {
            DB::table('plans')->where('key', $key)->update(array_combine(self::FIELDS, $values));
        }

        Schema::table('salons', function (Blueprint $table) {
            $table->string('site_theme', 40)->nullable()->after('logo_dark_tone');
            $table->string('admin_theme', 20)->nullable()->after('site_theme');
        });

        $this->fourPublicPlans();
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['site_theme', 'admin_theme']);
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('theme_site_count')->default(0)->after('trial_days');
            $table->unsignedTinyInteger('theme_site_count_6m')->default(0)->after('theme_site_count');
            $table->unsignedTinyInteger('theme_site_count_12m')->default(0)->after('theme_site_count_6m');
        });
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['theme_layouts', 'theme_palettes', 'theme_layouts_6m', 'theme_palettes_6m', 'theme_layouts_12m', 'theme_palettes_12m']);
        });
    }

    private function fourPublicPlans(): void
    {
        $now = now();

        // پایه و حرفه‌ای عمومی می‌شوند (فقط اگر ابرمدیر هنوز عمومی/غیرعمومی‌شان را دست نزده: همان پیش‌فرض false)
        DB::table('plans')->whereIn('key', ['basic', 'pro'])->where('is_public', false)->where('is_active', true)
            ->update(['is_public' => true, 'updated_at' => $now]);

        DB::table('plans')->where('key', 'pro')->where('description', 'همه‌ی امکانات موجود.')
            ->update(['description' => 'همه‌ی امکانات اصلی سالن، با ۱۰ متخصص و ۲٬۰۰۰ پیامک در ماه.', 'updated_at' => $now]);

        DB::table('plans')->where('key', 'clinic')->where('description', 'حرفه‌ای + دوره‌های درمانی و ارسال دستی به مشتری‌های قدیمی.')
            ->update(['description' => 'کامل + دوره‌های درمانی چندجلسه‌ای، ۱۵ متخصص و ۴٬۰۰۰ پیامک در ماه.', 'updated_at' => $now]);

        $full = DB::table('plans')->where('key', 'full')->first();
        if ($full === null) {
            return;
        }

        $updates = [];
        // قیمت فقط اگر هنوز همان پیش‌فرض config (.env) پچ ۰۰۷۴ است
        $oldPrices = ['price_1m' => 1500000, 'price_3m' => 4150000, 'price_6m' => 7650000, 'price_12m' => 13850000];
        $configPrices = (array) config('billing.subscription_prices');
        $isOld = fn (string $column, string $period) => (int) $full->{$column} === (int) $oldPrices[$column]
            || (int) $full->{$column} === (int) ($configPrices[$period] ?? -1);
        if ($isOld('price_1m', '1m') && $isOld('price_3m', '3m') && $isOld('price_6m', '6m') && $isOld('price_12m', '12m')) {
            $updates += ['price_1m' => 1900000, 'price_3m' => 5250000, 'price_6m' => 9700000, 'price_12m' => 17550000];
        }
        if ((int) $full->included_specialists === (int) config('billing.included_specialists', 10)) {
            $updates['included_specialists'] = 12;
        }
        if ((int) $full->sms_quota_per_month === (int) config('billing.sms_quota_per_month', 2000)) {
            $updates['sms_quota_per_month'] = 3000;
        }
        if ($full->description === 'همه‌ی امکانات امروز؛ پلن سالن‌های پیش از پلن‌بندی.') {
            $updates['description'] = 'حرفه‌ای + ارسال دستی به مشتری‌های قدیمی، ۱۲ متخصص و ۳٬۰۰۰ پیامک در ماه.';
        }
        if ((int) $full->sort === 10) {
            $updates['sort'] = 35;
        }
        if ($updates) {
            DB::table('plans')->where('id', $full->id)->update($updates + ['updated_at' => $now]);
        }

        // «ارسال دستی به مشتری‌های قدیمی» حالا فرق «کامل» با «حرفه‌ای» است
        $hasManual = DB::table('plan_features')->where('plan_id', $full->id)->where('feature', 'winback_manual')->exists();
        if (! $hasManual) {
            DB::table('plan_features')->insert(['plan_id' => $full->id, 'feature' => 'winback_manual', 'created_at' => $now, 'updated_at' => $now]);
        }

        if (class_exists(\App\Services\Plans\FeatureResolver::class)) {
            app(\App\Services\Plans\FeatureResolver::class)->forgetPlan((int) $full->id);
        }
    }
};
