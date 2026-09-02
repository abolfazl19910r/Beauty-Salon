<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ⭐ Customer identity redesign / SaaS multi-tenant (2026-08-30): every salon-owned
        // table's salon_id is NOT NULL (see BelongsToSalon), and it's only auto-filled when
        // CurrentSalon is set. Migrations create the default 'rasta' salon before seeders ever
        // run (see 2026_08_29_000103_backfill_default_salon_and_salon_id), so it's always here
        // to bind to. Without this, every seeder below that creates a Specialist, BeautyService,
        // Booking, customer User, etc. would fail the NOT NULL constraint immediately — this was
        // a documented, known gap ("seederهای CLI هنوز مشکل دارن") from when BelongsToSalon was
        // first introduced; this is that gap actually being closed.
        $salon = Salon::where('slug', 'rasta')->firstOrFail();
        app(CurrentSalon::class)->set($salon);

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,

            UserSeeder::class,

            CategorySeeder::class,
            BeautyServiceSeeder::class,

            SpecialistSeeder::class,
            SpecialistServiceSeeder::class,

            BookingSeeder::class,
            PaymentSeeder::class,

            DiscountCodeSeeder::class,
            DiscountUsageSeeder::class,
            LoyaltyBasicDataSeeder::class,
            RewardSeeder::class,
            LoyaltySimulationSeeder::class,

            GeneralContentSeeder::class,
            BlogSeeder::class,

            SupportTicketSeeder::class,
            ScheduledReportSeeder::class,

            NotificationSeeder::class,
            UserNotificationSeeder::class,
        ]);
    }
}
