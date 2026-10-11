<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سهم کیف پولِ پرداخت ترکیبی (کیف پول + درگاه) روی خود تراکنش (۲۰۲۶-۱۰-۱۱).
 *
 * قبلاً این مبلغ فقط در session مشتری بود: اگر مشتری از صفحه‌ی بانک برنمی‌گشت، نوبت خودکار لغو می‌شد و سهم
 * کیف پول هیچ‌وقت برنمی‌گشت؛ اپ موبایل هم اصلاً session ندارد. wallet_state: held (کم شده، منتظر نتیجه) ·
 * settled (بخشی از پرداخت موفق نوبت) · released (به کیف پول برگشت).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->decimal('wallet_toman', 15, 2)->default(0)->after('fee_rial');
            $table->string('wallet_state', 10)->nullable()->after('wallet_toman');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['wallet_toman', 'wallet_state']);
        });
    }
};
