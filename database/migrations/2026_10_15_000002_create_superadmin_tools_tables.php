<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ ابزارهای ابرمدیر (۲۰۲۶-۱۰-۱۵):
 * - superadmin_audit_logs: «گزارش کارهای ابرمدیر» — هر تغییر دستی (سالن، اشتراک، پلن، اعتبار پیامک، مالک، ورود اضطراری، …)
 *   با نام انجام‌دهنده (عکس لحظه‌ای، اگر کاربر بعداً حذف شد)، سالن، شرح، تغییرها و IP.
 * - platform_announcements: «اطلاعیه‌ی سراسری» — بنر در پنل مدیر و/یا متخصص همه‌ی سالن‌ها با بازه‌ی نمایش.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superadmin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action', 64)->index();
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('level', 16)->default('info');        // info | warning | critical
            $table->string('audience', 16)->default('admins');   // admins | specialists | all
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('superadmin_audit_logs');
    }
};
