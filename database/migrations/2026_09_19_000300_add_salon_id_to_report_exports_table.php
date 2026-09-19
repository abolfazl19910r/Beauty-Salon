<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Fix (real، تأییدشده، کشف‌شده ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): جدول `report_exports` از اول
 * (2026_07_21) اصلاً `salon_id` نداشت، با اینکه هر خروجی گزارش دقیقاً متعلق به یک سالنه. سه
 * مشکل واقعی از همین یک ستون گم‌شده نشأت می‌گرفت:
 *   1. `GeneratePdfReportJob` یک queued job است — هیچ HTTP request/middleware ندارد، پس
 *      `CurrentSalon` در اون context هیچ‌وقت ست نمی‌شد؛ `AdminReportService::buildExportData()`
 *      (که روی Eloquent query های salon-scoped مثل `Booking::where(...)` ساخته شده) بدون
 *      `CurrentSalon` هیچ فیلتری اعمال نمی‌کرد — یعنی فایل PDF/Excel خروجی، دادهٔ همهٔ سالن‌ها
 *      رو با هم قاطی برمی‌گردوند.
 *   2. `AdminReportExportController::index()` (لیست خروجی‌های درخواست‌شده) هیچ فیلتر سالنی
 *      نداشت — کامنت خودش می‌گفت «عمداً محدود به فقط درخواست‌های خودم نیست» با این مقصود که
 *      همهٔ ادمین‌های *همون سالن* باید هم رو ببینن، ولی چون هیچ فیلتر سالنی اصلاً وجود نداشت،
 *      عملاً همهٔ سالن‌های پلتفرم با هم مخلوط می‌شدن.
 *   3. `AdminReportExportController::download()` هیچ چک مالکیتی روی `{reportExport}` نداشت —
 *      یعنی یک ادمین از سالن A می‌تونست با حدس‌زدن/افزایش‌دادن id، گزارش مالی سالن B رو دانلود
 *      کنه (یک باگ کنترل دسترسی واقعی، نه فقط دادهٔ اشتباه).
 *
 * راه‌حل: افزودن `salon_id` + تریت `BelongsToSalon` روی مدل `ReportExport` — دقیقاً همون الگوی
 * از قبل جاافتادهٔ این پروژه (global scope خودکار + auto-fill روی creating()) که هر سه مشکل
 * بالا رو یک‌جا حل می‌کنه: index() خودکار فقط سالن جاری رو می‌بینه، download() از طریق implicit
 * route-model-binding با global scope خودکار ۴۰۴ می‌ده برای سالن دیگه (نه یک چک دستی جدا)، و
 * GeneratePdfReportJob با ست‌کردن صریح CurrentSalon از همین ستون (چون خودش HTTP context نداره)
 * مشکل ۱ رو حل می‌کنه.
 *
 * `nullable()` عمداً — این پروژه هنوز فقط dev/local است (production واقعی هنوز deploy نشده)، پس
 * نیازی به backfill پیچیده نیست؛ ردیف‌های احتمالی قدیمی بدون سالن (اگه باشن) با global scope
 * («بدون CurrentSalon = بدون فیلتر») همچنان از پنل سوپرادمین قابل‌مشاهده می‌مونن، فقط دیگه از
 * پنل ادمین یک سالن خاص دیده نمی‌شن — که دقیقاً رفتار امن‌تریه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('admin_user_id')->constrained('salons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salon_id');
        });
    }
};
