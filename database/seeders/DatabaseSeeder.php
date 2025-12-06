<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,

            CategorySeeder::class,
            BeautyServiceSeeder::class,

            SpecialistSeeder::class,
            SpecialistServiceSeeder::class,

            BookingSeeder::class,

            SystemSettingsSeeder::class,
            LoyaltySeeder::class,

            ContentSeeder::class,

            SupportTicketSeeder::class,

            ScheduledReportSeeder::class,

            NotificationSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
