<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * لینک کوتاه پیامک‌ها (۲۰۲۶-۰۹-۳۰): لینک کامل (مثلاً صفحه‌ی نظر با توکن ۶۴ حرفی) به‌تنهایی یک قطعه پیامک می‌گرفت.
 * دامنه/b/کد۶حرفی → redirect به آدرس کامل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->text('target_url');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
