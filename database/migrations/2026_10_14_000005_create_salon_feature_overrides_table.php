<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * استثنای امکانات هر سالن (بسته‌ی الف «پلن‌ها و امکانات»، تصمیم‌های ۲۰۲۶-۱۰-۱۴): مال یک سالن است ولی فقط سوپرادمین می‌نویسدش.
 * خود plans و plan_features، و ستون‌های پلن salons/invoices، در create همان جدول‌ها هستند (ادغام سوم migrationها، ۲۰۲۶-۱۰-۳۱؛ این
 * فایل قبلاً 2026_10_14_000005_create_plans_tables بود).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_feature_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->string('feature', 40);
            $table->boolean('enabled');
            $table->string('note', 255)->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['salon_id', 'feature']);
            $table->index(['feature', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_feature_overrides');
    }
};
