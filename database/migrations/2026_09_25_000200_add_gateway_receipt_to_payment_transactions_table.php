<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ مرحله‌ی ۲ چند درگاه — درگاه مستقیم بانک سامان (سپ).
 *
 * طبق مستند رسمی سپ، «رسید دیجیتالی» (RefNum) هر چند بار که برای VerifyTransaction فرستاده بشه دوباره
 * تایید می‌شه و جلوگیری از مصرف دوباره‌ی آن کاملاً به عهده‌ی پذیرنده است. این ستون رسید هر تراکنش رو
 * نگه می‌داره و index یکتای (driver, gateway_receipt) تضمین می‌کنه یک رسید هرگز به دو تراکنش وصل نشه —
 * حتی با دو callback هم‌زمان (App\Payments\GatewayReceipt::claim). NULL در index یکتا تکراری حساب نمی‌شه،
 * پس ردیف‌های قبلی و درگاه‌های دیگه دست‌نخورده می‌مونن.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('gateway_receipt', 100)->nullable()->after('ref_id');
            $table->unique(['driver', 'gateway_receipt'], 'payment_transactions_driver_receipt_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_transactions_driver_receipt_unique');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('gateway_receipt');
        });
    }
};
