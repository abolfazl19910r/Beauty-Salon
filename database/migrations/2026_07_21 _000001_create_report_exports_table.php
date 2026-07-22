<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('format');       // 'pdf' | 'excel'
            $table->string('report_type');  // 'daily' | 'weekly' | 'monthly'
            $table->json('filters');        // start_date/end_date همون لحظه‌ی درخواست
            $table->string('status')->default('pending'); // pending | processing | ready | failed
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
