<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ اطلاعات تماس و فعالیت هر سالن (۲۰۲۶-۰۹-۲۳) — دقیقاً مثل name، هر کدوم مال همون سالن و از
 * بقیه جدا. قبلاً فوتر لایوت مشتری آدرس/تلفن/ساعات کاری رو به‌صورت متن ثابت هاردکد داشت و
 * همه‌ی سالن‌ها یک آدرس و یک تلفن نشون می‌دادن. همه nullable: سالن‌های ساخته‌شده توسط
 * سوپرادمین یا قدیمی‌تر هنوز این‌ها رو ندارن و ویوها در اون حالت خط مربوط رو اصلاً نشون نمی‌دن.
 *
 * established_year (نه «تعداد سال سابقه»): فرم عدد سال‌های سابقه رو می‌گیره، ولی سال شروع
 * فعالیت (میلادی) ذخیره می‌شه تا «سال تجربه» هر سال خودکار یکی زیاد بشه و کهنه نشه
 * (Salon::experienceYears()).
 *
 * working_hours: JSON با کلیدهای روز هفته‌ی Carbon (۰=یکشنبه … ۶=شنبه، همون قرارداد
 * specialist_schedules.day_of_week) → {"open":"09:00","close":"21:00"} یا null = تعطیل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('bio');
            $table->string('phone', 20)->nullable()->after('address');
            $table->unsignedSmallInteger('established_year')->nullable()->after('phone');
            $table->json('working_hours')->nullable()->after('established_year');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'established_year', 'working_hours']);
        });
    }
};
