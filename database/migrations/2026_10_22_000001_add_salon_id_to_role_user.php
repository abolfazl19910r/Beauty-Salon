<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * مالک/منشی چندسالنی (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): نقشی که در یک سالن به کسی داده شده، در سالن دیگرش نباید کار کند.
 *
 * نقش‌های خود سالن (roles.salon_id) از قبل با scope سراسری Role جدا بودند؛ ولی نقش‌های سیستمی (roles.salon_id = null) را هر سالن
 * می‌تواند بدهد و role_user نمی‌گفت در کدام سالن داده شده. حالا role_user.salon_id: null = سراسری (سوپرادمین، ردیف‌های قدیمی
 * کاربرانِ تک‌سالنی)، عدد = فقط در همان سالن (User::effectiveRoles). کلید اصلی (user_id, role_id) جلوی دادن یک نقش در دو سالن را
 * می‌گرفت، پس جدول با شناسه و یکتایی (user_id, role_id, salon_id) از نو ساخته و داده‌ها منتقل می‌شوند (روی هر سه دیتابیس یکسان).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user_scoped', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unique(['user_id', 'role_id', 'salon_id'], 'role_user_user_role_salon_unique');
            $table->index(['user_id', 'salon_id'], 'role_user_user_salon_index');
        });

        DB::table('role_user_scoped')->insertUsing(['user_id', 'role_id'], DB::table('role_user')->select(['user_id', 'role_id']));

        Schema::drop('role_user');
        Schema::rename('role_user_scoped', 'role_user');
    }

    public function down(): void
    {
        Schema::create('role_user_flat', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        DB::table('role_user_flat')->insertUsing(['user_id', 'role_id'], DB::table('role_user')->select(['user_id', 'role_id'])->distinct());

        Schema::drop('role_user');
        Schema::rename('role_user_flat', 'role_user');
    }
};
