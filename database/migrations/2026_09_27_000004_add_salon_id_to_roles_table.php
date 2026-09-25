<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نقش‌ها مختص هر سالن (تصمیم ۲۰۲۶-۰۹-۲۷). salon_id = null یعنی نقش سیستمی (admin، specialist، super-admin و …) که کد
 * با نامش چک می‌کند و فقط مدیر پلتفرم تغییرش می‌دهد؛ نقش‌های ساخت سالن salon_id دارند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salon_id');
        });
    }
};
