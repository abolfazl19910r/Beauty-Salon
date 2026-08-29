<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 1): adds a nullable
 * salon_id to every table that owns data specific to one salon. Left nullable() here on
 * purpose — the next migration (2026_08_29_000103_backfill_default_salon_and_salon_id)
 * backfills every existing row to the default salon and only THEN makes the column NOT NULL,
 * so this step never risks failing on tables that already have rows.
 *
 * Table list verified directly against this project's actual migrations (grep across
 * database/migrations/*.php) rather than assumed — two tables were added to the original plan
 * documented in Rasta_unified_prompt.md after finding them this way:
 *   - `admin_wallet` — AdminWallet::getWallet() is a true singleton (self::first()); without
 *     salon_id, commission earnings from every salon would pool into one shared row.
 *   - `wallet_settings` — WalletSetting::first() has the exact same singleton pattern.
 * `discount_usages`, `loyalty_points`, `rewards`, and `loyalties` were deliberately NOT given
 * their own salon_id — they're always reachable through an already-scoped parent
 * (discount_codes / specialists / users), so denormalizing salon_id onto them would add
 * write-time upkeep for no query-isolation benefit. `bookings` is the one exception: it gets
 * salon_id directly (denormalized) rather than only via specialist_id, purely for query
 * performance on the admin bookings list/reports — matching the reasoning already documented
 * for that table in Rasta_unified_prompt.md.
 */
return new class extends Migration
{
    private const TABLES = [
        'specialists',
        'beauty_services',
        'categories',
        'blog_posts',
        'blog_categories',
        'gallery_images',
        'announcements',
        'discount_codes',
        'loyalty_settings',
        'wallet_settings',
        'admin_wallet',
        'bookings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('salon_id')->nullable()->after('id')->constrained('salons')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('salon_id');
            });
        }
    }
};
