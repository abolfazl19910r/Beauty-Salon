<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ تسویه‌ی خودکار کیف پول متخصص از حساب زرین‌پالِ خودِ سالن (۲۰۲۶-۰۹-۲۴، تصمیم ابوالفضل).
 * قبلاً ZarinpalPayoutService با مرچنت و API key **پلتفرم** تسویه می‌کرد، در حالی که درآمد متخصص از
 * پرداخت مشتری‌ها به حساب **سالن** واریز شده. text (نه string) چون مقدار با cast 'encrypted' ذخیره
 * می‌شه و طول ciphertext از طول توکن خیلی بیشتره.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->text('zarinpal_payout_api_key')->nullable()->after('zarinpal_merchant_id');
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn('zarinpal_payout_api_key');
        });
    }
};
