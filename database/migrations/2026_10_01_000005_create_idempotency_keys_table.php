<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کلید idempotency فرم‌ها (App\Support\Idempotency): دوبار ارسال همان فرم همان نتیجه‌ی اول را می‌دهد. یکتایی
 * (scope, owner_id, key) پشتوانه‌ی دیتابیسی است؛ ردیف‌ها بعد از ۲۴ ساعت پاک می‌شوند (model:prune).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 40);
            $table->unsignedBigInteger('owner_id');
            $table->string('key', 36);
            $table->string('fingerprint', 64);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'owner_id', 'key']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
