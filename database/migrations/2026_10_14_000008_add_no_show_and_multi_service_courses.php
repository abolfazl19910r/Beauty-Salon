<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * کارهای باز بسته‌ی ب (۲۰۲۶-۱۰-۱۴، ادامه ۷):
 * - وضعیت تازه‌ی نوبت «نیامد» (no_show): پیش‌پرداخت مال سالن، جلسه‌ی دوره می‌سوزد. ستون مولد active_slot_key دست نمی‌خورد
 *   (فقط cancelled آزاد می‌کند؛ «نیامد» همیشه گذشته است).
 * - بسته‌ی چندخدمتی: هر جلسه همه‌ی خدمت‌ها با هم در یک نوبت؛ bookings.duration_minutes مدت آن نوبت (جمع خدمت‌ها) است و جای
 *   مدت خدمت اصلی را در سنجش هم‌پوشانی و ساعت‌های آزاد می‌گیرد (null = مدت خدمت، مثل همیشه).
 * - پلن «کلینیک» عمومی (تصمیم قیمت): فقط اگر کسی آن را ویرایش نکرده باشد (created_at = updated_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('pending','confirmed','cancelled','pending_payment','completed','no_show') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('bookings', function (Blueprint $table) {
                $table->enum('status', ['pending', 'confirmed', 'cancelled', 'pending_payment', 'completed', 'no_show'])->default('pending')->change();
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('service_id');
            $table->dateTime('no_show_at')->nullable();
            $table->foreignId('no_show_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('course_packages', function (Blueprint $table) {
            $table->json('extra_service_ids')->nullable()->after('service_id');
        });

        Schema::table('customer_courses', function (Blueprint $table) {
            $table->json('extra_service_ids')->nullable()->after('service_id');
            $table->unsignedSmallInteger('session_duration')->nullable()->after('extra_service_ids');
        });

        DB::table('plans')->where('key', 'clinic')->where('is_public', false)->whereColumn('created_at', 'updated_at')
            ->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('customer_courses', function (Blueprint $table) {
            $table->dropColumn(['extra_service_ids', 'session_duration']);
        });
        Schema::table('course_packages', function (Blueprint $table) {
            $table->dropColumn('extra_service_ids');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('no_show_by');
            $table->dropColumn(['duration_minutes', 'no_show_at']);
        });
        // وضعیت no_show در enum می‌ماند (برگرداندنش نوبت‌های «نیامد» را خراب می‌کند)
    }
};
