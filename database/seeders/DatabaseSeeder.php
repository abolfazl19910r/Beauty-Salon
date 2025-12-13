<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
