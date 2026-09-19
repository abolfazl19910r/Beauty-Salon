<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰): همون نشستی که برندینگ هاردکد («راستا» در ~۱۳ ویو) رو فیکس کرد
 * ($currentSalonName در ViewComposer) صراحتاً مستند کرد که متن‌های بازاریابی اطراف نام (مثل
 * «با سال‌ها تجربه»، «بهترین خدمات زیبایی») همچنان generic و مشترک بین همه‌ی سالن‌ها می‌مونن —
 * چون Salon model هیچ ستونی برای این محتوا نداشت. این migration اون کمبود رو رفع می‌کنه.
 *
 * دو ستون، هر دو nullable (یک سالن تازه‌ساخته می‌تونه اینا رو خالی بذاره — ViewComposer یک
 * fallback عمومی معقول برمی‌گردونه، نه خطا):
 *   - tagline: یک جمله‌ی کوتاه (مثل subtitle صفحه‌ی خدمات) — حداکثر ۲۵۵ کاراکتر کافیه.
 *   - bio: پاراگراف معرفی سالن (بخش «درباره‌ی ما» در صفحه‌ی اصلی) — text چون می‌تونه چندجمله‌ای باشه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->text('bio')->nullable()->after('tagline');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'bio']);
        });
    }
};
