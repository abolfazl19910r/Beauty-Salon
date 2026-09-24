<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ بخش ۲ (۲۰۲۶-۰۹-۲۵): دفتر واحد همه‌ی تراکنش‌های درگاه (پیش‌پرداخت و
 * باقی‌مانده‌ی نوبت، شارژ کیف پول، خرید اشتراک). دلیل اصلی: درگاه‌های بانکی و PayPing v3 نتیجه رو با
 * **POST cross-site** برمی‌گردونن و کوکی session (SameSite=Lax) در اون درخواست فرستاده نمی‌شه؛ پس
 * «کدوم درگاه/کدوم مبلغ» نباید از session خونده بشه. آدرس بازگشت هر درگاه حالا
 * /payments/return/{public_id} است که از روی همین ردیف درگاه و مبلغ رو می‌دونه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gateway_id')->nullable()->constrained('salon_payment_gateways')->nullOnDelete();
            $table->string('driver', 32);
            $table->string('purpose', 32);
            $table->nullableMorphs('payable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_rial');
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->string('token')->nullable()->index();
            $table->string('ref_id')->nullable();
            $table->string('card_pan')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('callback_url');
            $table->json('start_response')->nullable();
            $table->json('verify_response')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['salon_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
