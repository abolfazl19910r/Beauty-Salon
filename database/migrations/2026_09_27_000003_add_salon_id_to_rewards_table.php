<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جوایز وفاداری مال هر سالن جداست (تصمیم ۲۰۲۶-۰۹-۲۷). ستون nullable است چون ردیف‌های قبلی صاحب مشخصی ندارند؛
 * Reward::BelongsToSalon ردیف بی‌سالن را برای هیچ سالنی نشان نمی‌دهد، و دستور tenancy:repair-legacy-rows آن‌ها را به
 * یک سالن می‌دهد (migration فقط schema است).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salon_id');
        });
    }
};
