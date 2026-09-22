<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 1): rather than a unique
 * `admin_user_id` column on `salons` (which phase 2's "چند ادمین روی یک سالن" would later have
 * to migrate away from live data), the salon↔admin relationship is a pivot table from day one.
 * Phase 1 enforces "at most one 'owner' per salon" purely at the application layer
 * (SuperAdminService::createSalonWithAdmin()) — no DB constraint for that rule — specifically so
 * phase 2 can lift the rule and add a UI for it without touching this schema again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained('salons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'staff'])->default('owner');
            $table->timestamps();
            $table->unique(['salon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_admins');
    }
};
