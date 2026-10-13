<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * نوع «expired» برای ردیف‌های انقضای امتیاز باشگاه مشتریان (LoyaltyLedger، تصمیم ۲۰۲۶-۱۰-۱۱ گزینه‌ی «الف»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->enum('type', ['earned', 'spent', 'expired'])->default('earned')->change();
        });
    }

    public function down(): void
    {
        // ردیف‌های انقضا در نوع قدیمی جا ندارند: «spent» می‌شوند تا موجودی همان بماند
        DB::table('loyalty_points')->where('type', 'expired')->update(['type' => 'spent']);

        Schema::table('loyalty_points', function (Blueprint $table) {
            $table->enum('type', ['earned', 'spent'])->default('earned')->change();
        });
    }
};
