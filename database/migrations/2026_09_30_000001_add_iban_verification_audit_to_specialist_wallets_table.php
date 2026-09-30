<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialist_wallets', function (Blueprint $table) {
            $table->foreignId('iban_verified_by')->nullable()->after('iban_verified')->constrained('users')->nullOnDelete();
            $table->timestamp('iban_verified_at')->nullable()->after('iban_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('specialist_wallets', function (Blueprint $table) {
            $table->dropForeign(['iban_verified_by']);
            $table->dropColumn(['iban_verified_by', 'iban_verified_at']);
        });
    }
};
