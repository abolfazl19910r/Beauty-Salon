<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): شمار روزانه‌ی بازدید از QR سالن و QR هر متخصص (آدرس با ?src=qr). یک ردیف برای هر سالن/متخصص/روز؛
 * specialist_id صفر = QR خود سالن (null نه، تا کلید یکتا روی MySQL هم یکتا بماند). صفحه‌ی «QR و پوستر» ۳۰ روز اخیر را نشان می‌دهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->unsignedBigInteger('specialist_id')->default(0);
            $table->date('scan_date');
            $table->unsignedInteger('hits')->default(0);

            $table->unique(['salon_id', 'specialist_id', 'scan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scans');
    }
};
