<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        Reward::firstOrCreate(
            ['required_points' => 1000],
            [
                'title' => '50,000 تومان تخفیف رزرو',
                'description' => 'کوپن تخفیف برای رزرو بعدی در ازای 1000 امتیاز',
                'discount_type' => 'fixed',
                'discount_amount' => 50000,
                'is_active' => true,
                'max_uses' => null,
            ]
        );

        Reward::firstOrCreate(
            ['required_points' => 3000],
            [
                'title' => '40% تخفیف ویژه برای سرویس‌ها',
                'description' => '40% تخفیف برای یک سرویس انتخابی',
                'discount_type' => 'percentage',
                'discount_amount' => 40,
                'is_active' => true,
                'max_uses' => 50,
            ]
        );

        Reward::factory(5)->create();
    }
}
