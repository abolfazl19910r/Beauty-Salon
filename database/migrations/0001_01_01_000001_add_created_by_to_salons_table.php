<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `salons` is created before `users` (it's referenced by users.salon_id), so `created_by`
 * (a nullable FK to users.id) can only be added here, once users exists. سالن سیستم (پیش‌فرض،
 * id=1) هرگز نباید تعلیق/حذف بشه — چک در SuperAdminController، نه اینجا؛ این ستون فقط پرچم
 * ساده‌ی تعلیق دستی توسط سوپر ادمینه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
