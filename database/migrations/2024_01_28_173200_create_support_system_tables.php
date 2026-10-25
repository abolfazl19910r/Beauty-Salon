<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->string('category');
            // ⭐ بسته‌ی مجوز 5د: شکایت (مشتری از سالن یا پلتفرم، یا سالن از پلتفرم) با مهلت پاسخ ۷۲ ساعته‌ی اینماد
            $table->string('kind', 16)->default('support'); // support | complaint
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index(['category', 'created_at']);
            $table->index(['kind', 'first_responded_at', 'response_due_at']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_staff_reply')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
