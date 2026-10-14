<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * درخواست‌های «دموی رایگان» صفحه‌ی فروش (لندینگ تازه، ۲۰۲۶-۱۰-۱۴). سطح پلتفرم، بدون salon_id: درخواست‌دهنده هنوز سالنی ندارد
 * و فقط سوپرادمین فهرست را می‌بیند (superadmin.demo-requests.*).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('salon_name', 150)->nullable();
            $table->string('phone', 11);
            $table->string('salon_type', 20)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
            $table->index('handled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
