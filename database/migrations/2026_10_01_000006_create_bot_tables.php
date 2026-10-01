<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربات پلتفرم (تصمیم ۲۰۲۶-۱۰-۰۱): یک ربات بله و یک ربات تلگرام برای کل پلتفرم. هر کاربر گفت‌وگوی خودش را با
 * «/start <کد یک‌بارمصرف>» وصل می‌کند؛ اعلان‌ها فقط به گفت‌وگوهای وصل‌شده‌ی همان کاربر می‌روند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('messenger', 10); // bale | telegram
            $table->string('chat_id', 64);
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete(); // سالنی که اتصال از آن ساخته شد
            $table->timestamps();

            $table->unique(['messenger', 'chat_id']);
            $table->unique(['user_id', 'messenger']);
        });

        Schema::create('bot_link_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_link_codes');
        Schema::dropIfExists('bot_links');
    }
};
