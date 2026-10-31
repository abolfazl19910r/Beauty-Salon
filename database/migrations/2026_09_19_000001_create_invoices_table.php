<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب» — به‌ازای هر خرید/تمدید اشتراک یک ردیف اینجا
 * ثبت می‌شود، چه از مسیر آنلاین زرین‌پال (payment_method='online') چه از مسیر دستی سوپر ادمین
 * (payment_method='manual' — همان تمدید دستی قدیمی فاز ۱، حالا هم در همین جدول ثبت می‌شود تا
 * تاریخچه‌ی کامل و یکپارچه باشد). به بخش InvoiceService نگاه کن.
 *
 * salon_id از trait استاندارد BelongsToSalon (نه این migration) استفاده می‌کند — درست مثل بقیه‌ی
 * جدول‌های salon-owned این پروژه — یعنی پنل ادمین هر سالن فقط فاکتورهای خودش را می‌بیند و پنل
 * سوپر ادمین (بدون CurrentSalon) همه را.
 *
 * period_start/period_end عمداً nullable هستند: فقط وقتی فاکتور واقعاً paid می‌شود پر می‌شوند
 * (لحظه‌ی ساخت فاکتور pending آنلاین، دوره‌ی واقعی هنوز معلوم نیست چون ممکن است پرداخت هیچ‌وقت
 * تکمیل نشود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->enum('subscription_type', ['1m', '3m', '6m', '12m']);
            // پلن‌ها (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴): renewal = خرید/تمدید یک بازه · upgrade = مابه‌التفاوت ارتقای وسط دوره (پایان دوره ثابت)
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('kind', 20)->default('renewal');
            $table->json('plan_snapshot')->nullable();
            $table->unsignedBigInteger('amount'); // تومان — هم‌الگو با prepayment_amount نوبت‌ها
            // ⭐ پرداخت قسطی و «اعتبار پلتفرم» (۲۰۲۶-۱۰-۳۱، راه «ب» سقف ۱۰۰ میلیون تومانی زرین‌پال): credit_applied = تومان از اعتبار
            // پلتفرم (هنگام ساخت رزرو می‌شود)، paid_amount = جمع قسط‌های پرداخت‌شده، partial_expires_at = ۷ روز بعد از اولین قسط
            $table->unsignedBigInteger('credit_applied')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedTinyInteger('installments')->default(1);
            $table->unsignedTinyInteger('installments_paid')->default(0);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('payment_method', ['online', 'manual'])->default('online');
            $table->string('authority')->nullable(); // authority زرین‌پال (فقط مسیر آنلاین)
            $table->string('ref_id')->nullable(); // کد پیگیری واقعی زرین‌پال بعد از تأیید
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('partial_expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
