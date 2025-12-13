<?php

namespace Database\Seeders;

use App\Models\DiscountUsage;
use Illuminate\Database\Seeder;

class DiscountUsageSeeder extends Seeder
{
    public function run(): void
    {
        DiscountUsage::factory(10)->create();

    }
}
