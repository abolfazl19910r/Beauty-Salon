<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵ب: کارت «پیشنهاد ویژه» قالب «روزنامه‌ی سالن» فقط کدی را نشان می‌دهد که مدیر خودش «نمایش در سایت» زده است.
 * کد بدون مشتری لزوماً عمومی نیست (کد همکار/اینفلوئنسر)؛ پیش‌فرض خاموش تا هیچ کد موجودی ناخواسته منتشر نشود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->boolean('show_on_site')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropColumn('show_on_site');
        });
    }
};
