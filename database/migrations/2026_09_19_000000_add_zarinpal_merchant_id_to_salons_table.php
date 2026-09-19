<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ فاز ۲ از ۲، مورد ۹ («مرچنت آیدی مجزا برای هر سالن») — به بخش «۹. مرچنت آیدی مجزا برای هر
 * سالن (زرین‌پال)» در Rasta_unified_prompt.md نگاه کن. هر سالن می‌تواند merchant_id مخصوص خودش
 * را برای پرداخت‌های پیش‌پرداخت نوبت/شارژ کیف‌پول (پول مشتری → سالن) تنظیم کند تا آن پول مستقیم
 * به حساب زرین‌پال خودِ سالن واریز شود، نه حساب مشترک پلتفرم.
 *
 * عمداً nullable: سالن تازه‌ساخته تا وقتی خودش merchant_id واقعی‌اش را نزد سوپر ادمین ثبت نکرده،
 * باید بلافاصله کار کند — PaymentService در نبود این مقدار به merchant_id سراسری
 * (config('services.zarinpal.merchant_id')) برمی‌گردد. این ستون ربطی به پرداخت خرید/تمدید
 * اشتراک (سالن → پلتفرم) ندارد؛ آن مسیر همیشه از merchant_id سراسری استفاده می‌کند — به
 * SubscriptionPaymentService نگاه کن.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('zarinpal_merchant_id')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('zarinpal_merchant_id');
        });
    }
};
