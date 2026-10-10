<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کلیدهای یکتای داده‌ی سالن‌ها تا حالا در کل جدول یکتا بودند، نه در هر سالن (۲۰۲۶-۱۰-۱۱).
 *
 * سالن دوم نمی‌توانست دسته‌بندی/خدمت/دسته و پست وبلاگ هم‌نام با سالن دیگر بسازد (slug تکراری)،
 * کد تخفیفی که سالن دیگر داشت (مثلاً NOROOZ) را بسازد، یا تنظیمات باشگاه مشتریان خودش را ذخیره کند.
 * هر کدام حالا (salon_id, ستون) یکتاست. داده‌ی موجود قبلاً در کل جدول یکتا بوده، پس قید جدید
 * روی هیچ دیتابیس موجودی نمی‌شکند.
 */
return new class extends Migration
{
    /** @var array<string, string> جدول => ستون */
    private array $keys = [
        'categories' => 'slug',
        'beauty_services' => 'slug',
        'blog_categories' => 'slug',
        'blog_posts' => 'slug',
        'discount_codes' => 'code',
        'loyalty_settings' => 'key',
    ];

    public function up(): void
    {
        foreach ($this->keys as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                $blueprint->dropUnique("{$table}_{$column}_unique");
                $blueprint->unique(['salon_id', $column], "{$table}_salon_{$column}_unique");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $column) {
                // MySQL ایندکس خودکارِ کلید خارجی salon_id را با ایندکس ترکیبی جایگزین کرده؛ اول
                // ایندکس جدا برای کلید خارجی، بعد حذف ترکیبی (وگرنه خطای 1553)
                $blueprint->index('salon_id', "{$table}_salon_id_fk_index");
                $blueprint->dropUnique("{$table}_salon_{$column}_unique");
                $blueprint->unique($column, "{$table}_{$column}_unique");
            });
        }
    }
};
