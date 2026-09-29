<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            // nullable: اعلانی که گیرنده‌اش متخصص بدون حساب کاربری است کاربر مالک ندارد (UserNotification::ownerUserId()).
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
