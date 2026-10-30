<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «اتصال حساب زرین‌پال» هر سالن (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰) — OAuth 2.0 Authorization Code + PKCE (`/apiDocs/connect.html`).
 * توکن دسترسی رمزشده (APP_KEY)؛ refresh_token در این روش مستند نیست. terminals = آخرین فهرست درگاه‌های حساب (بی مرچنت)،
 * requested_terminal = نتیجه‌ی TerminalAdd وقتی درگاهی با دامنه‌ی سالن نبود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zarinpal_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('access_token');                  // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->string('terminal_id', 32)->nullable(); // درگاه هم‌دامنه‌ی سالن که مرچنتش برداشته شد
            $table->json('terminals')->nullable();
            $table->json('requested_terminal')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zarinpal_connections');
    }
};
