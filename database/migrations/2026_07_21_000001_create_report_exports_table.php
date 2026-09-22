<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Fix (real، تأییدشده، کشف‌شده ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): بدون `salon_id`، سه مشکل واقعی
 * پیش میومد: ۱) `GeneratePdfReportJob` (یک queued job بدون HTTP context) بدون `CurrentSalon` هیچ
 * فیلتری اعمال نمی‌کرد — فایل خروجی دادهٔ همهٔ سالن‌ها رو قاطی برمی‌گردوند؛ ۲) لیست خروجی‌های
 * درخواست‌شده هیچ فیلتر سالنی نداشت؛ ۳) دانلود هیچ چک مالکیتی روی گزارش نداشت (یک ادمین سالن A
 * می‌تونست گزارش سالن B رو با حدس‌زدن id دانلود کنه). راه‌حل: `salon_id` + تریت `BelongsToSalon`
 * روی مدل `ReportExport` — index()/download() خودکار محدود به سالن جاری می‌شن،
 * GeneratePdfReportJob هم صریحاً از همین ستون CurrentSalon رو ست می‌کنه.
 *
 * `nullable()` عمداً — این پروژه هنوز فقط dev/local است، پس نیازی به backfill پیچیده نیست.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->nullable()->constrained('salons')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('format');       // 'pdf' | 'excel'
            $table->string('report_type');  // 'daily' | 'weekly' | 'monthly'
            $table->json('filters');        // start_date/end_date همون لحظه‌ی درخواست
            $table->string('status')->default('pending'); // pending | processing | ready | failed
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
