<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ (۲۰۲۶-۰۹-۲۵). درگاه‌های پرداخت هر سالن؛ هر سالن می‌تونه چندتا داشته
 * باشه (تصمیم ابوالفضل: انتخاب مشتری + جایگزینی خودکار). credentials با cast 'encrypted:array'.
 * fee_percent / fee_fixed_toman: کارمزد درگاه که به مبلغ مشتری اضافه می‌شه (تصمیم ابوالفضل) —
 * ستون‌ها از الان هستن، محاسبه در مرحله‌ی ۱ همراه با صفحه‌ی انتخاب درگاه فعال می‌شه (فعلاً ۰).
 *
 * داده‌ی موجود: هر سالنی که zarinpal_merchant_id داره یک ردیف zarinpal می‌گیره، تا رفتار فعلی دقیقاً
 * همون بمونه. تا وقتی UI مدیریت درگاه‌ها (مرحله‌ی ۱) نیومده، همون فیلد «کد پذیرنده‌ی زرین‌پال» این
 * ردیف رو sync می‌کنه (App\Models\Salon::booted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->string('driver', 32);
            $table->string('label')->nullable();
            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(1);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->unsignedInteger('fee_fixed_toman')->default(0);
            $table->timestamps();

            $table->index(['salon_id', 'is_active', 'priority']);
        });

        $encrypter = app('encrypter');
        foreach (DB::table('salons')->whereNotNull('zarinpal_merchant_id')->where('zarinpal_merchant_id', '!=', '')->get(['id', 'zarinpal_merchant_id']) as $salon) {
            DB::table('salon_payment_gateways')->insert([
                'salon_id' => $salon->id,
                'driver' => 'zarinpal',
                'label' => 'زرین‌پال',
                'credentials' => $encrypter->encrypt(json_encode(['merchant_id' => $salon->zarinpal_merchant_id]), false),
                'is_active' => true,
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_payment_gateways');
    }
};
