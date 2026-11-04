<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // null = نقش سیستمی (کد با نامش چک می‌کند، فقط سوپرادمین تغییرش می‌دهد)؛ غیر null = نقش همان سالن.
            $table->foreignId('salon_id')->nullable()->constrained('salons')->cascadeOnDelete();
            $table->string('name');
            $table->string('label');
            $table->timestamps();
        });

        /*
         * مالک/منشی چندسالنی (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): نقشی که در یک سالن به کسی داده شده، در سالن دیگرش نباید کار کند. نقش‌های خود
         * سالن (roles.salon_id) با scope سراسری Role جدا هستند؛ نقش‌های سیستمی (roles.salon_id = null) را هر سالن می‌تواند بدهد، پس
         * role_user.salon_id: null = سراسری (سوپرادمین، کاربران تک‌سالنی)، عدد = فقط در همان سالن (User::effectiveRoles). به همین دلیل
         * کلید اصلی (user_id, role_id) نیست: شناسه + یکتایی (user_id, role_id, salon_id).
         * کلیدهای خارجی نام پیش‌فرض Laravel دارند (`role_user_{col}_foreign`)؛ تا بسته‌ی 5ح (۲۰۲۶-۱۱-۰۴) «role_user_scoped_*» بودند (از جدول
         * موقت migration قدیمی 2026_10_22_000001) — دیتابیس ساخته‌شده پیش از آن با migrate:fresh یکی می‌شود.
         */
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id');
            $table->foreignId('salon_id')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('salon_id')->references('id')->on('salons')->cascadeOnDelete();
            $table->unique(['user_id', 'role_id', 'salon_id'], 'role_user_user_role_salon_unique');
            $table->index(['user_id', 'salon_id'], 'role_user_user_salon_index');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
