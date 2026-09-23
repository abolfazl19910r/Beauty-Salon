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
        // CurrentSalon is set. The default 'rasta' salon used to be created by a migration
        // (backfill_default_salon_and_salon_id, since removed as part of consolidating the
        // migrations that added salon_id — see Rasta_unified_prompt.md); it's created here
        // instead now, since a migration is schema-only and this is genuinely seed data. Without
        // this, every seeder below that creates a Specialist, BeautyService, Booking, customer
        // User, etc. would fail the NOT NULL constraint immediately.
        $salon = Salon::firstOrCreate(
            ['slug' => 'rasta'],
            [
                'name' => 'سالن زیبایی راستا',
                // ⭐ ۲۰۲۶-۰۹-۲۳: همون مقادیری که قبلاً در فوتر layouts/app هاردکد بود، حالا به‌عنوان
                // داده‌ی واقعی همین سالن دمو (هر سالن دیگه مقادیر خودش رو داره).
                'address' => 'تهران، خیابان ولیعصر',
                'phone' => '02112345678',
                'established_year' => now()->year - 9,
                'working_hours' => \App\Support\SalonWorkingHours::defaults(),
                // ⭐ ۲۰۲۶-۰۹-۲۴: سالن دمو همون مرچنت .env رو می‌گیره تا پرداخت‌های تستی کار کنن
                // (دیگه fallback خودکار به مرچنت پلتفرم وجود نداره).
                'zarinpal_merchant_id' => \App\Support\ZarinpalMerchant::normalize(config('services.zarinpal.merchant_id')),
                'max_specialists_count' => 100,
                'module_permissions' => null, // null = همه‌ی ماژول‌ها
                'subscription_type' => '12m',
                'subscription_started_at' => now(),
                'subscription_ends_at' => now()->addMonths(12),
                'is_suspended' => false,
            ]
        );
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
