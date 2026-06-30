<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)
                ->nullable()
                ->default(null)
                ->after('auto_confirm_bookings')
                ->comment('نرخ کمیسیون اختصاصی (%). null = استفاده از تنظیمات global');
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
