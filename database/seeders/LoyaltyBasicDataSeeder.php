<?php

namespace Database\Seeders;

use App\Models\Loyalty;
use App\Models\LoyaltySetting;
use App\Models\Reward;
use Illuminate\Database\Seeder;

class LoyaltyBasicDataSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'برنزی', 'points_required' => 0, 'discount_percentage' => 0],
            ['name' => 'نقره‌ای', 'points_required' => 500, 'discount_percentage' => 5],
            ['name' => 'طلایی', 'points_required' => 1500, 'discount_percentage' => 10],
            ['name' => 'پلاتینی', 'points_required' => 3000, 'discount_percentage' => 15],
            ['name' => 'الماسی', 'points_required' => 5000, 'discount_percentage' => 20],
        ];

        foreach ($levels as $level) {
            Loyalty::updateOrCreate(['name' => $level['name']], $level);
        }

        $settings = [
            ['key' => 'points_per_currency', 'value' => '10000', 'description' => 'هر 10 هزار تومان 1 امتیاز'],
            ['key' => 'min_points_redemption', 'value' => '100', 'description' => 'حداقل امتیاز جهت استفاده'],
        ];

        foreach ($settings as $setting) {
            LoyaltySetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        $rewards = [
            [
                'title' => 'تخفیف ۵۰ هزار تومانی',
                'description' => 'قابل استفاده برای تمام خدمات',
                'required_points' => 500,
                'discount_type' => 'fixed',
                'discount_amount' => 50000,
            ],
            [
                'title' => 'تخفیف ۱۰ درصدی',
                'description' => 'تا سقف ۲۰۰ هزار تومان',
                'required_points' => 1000,
                'discount_type' => 'percentage',
                'discount_amount' => 10,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::firstOrCreate(['title' => $reward['title']], $reward);
        }
    }
}
