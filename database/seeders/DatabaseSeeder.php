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
            BookingSeeder::class,
            LoyaltySeeder::class,
            ContentSeeder::class,
            SupportTicketSeeder::class,
            ScheduledReportSeeder::class,
        ]);
    }
}
