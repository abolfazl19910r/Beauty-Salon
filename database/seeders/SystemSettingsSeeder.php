<?php

namespace Database\Seeders;

use App\Models\Loyalty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {

        $loyaltyLevels = [
            [
                'name' => 'برنزی',
                'description' => 'سطح ابتدایی مشتریان وفادار',
                'points_required' => 100,
                'discount_percentage' => 5.00
            ],
            [
                'name' => 'نقره‌ای',
                'description' => 'سطح متوسط مشتریان وفادار',
                'points_required' => 300,
                'discount_percentage' => 10.00
            ],
            [
                'name' => 'طلایی',
                'description' => 'سطح بالای مشتریان وفادار',
                'points_required' => 500,
                'discount_percentage' => 15.00
            ],
            [
                'name' => 'پلاتینی',
                'description' => 'سطح ممتاز مشتریان وفادار',
                'points_required' => 1000,
                'discount_percentage' => 20.00
            ],
            [
                'name' => 'الماسی',
                'description' => 'بالاترین سطح مشتریان وفادار',
                'points_required' => 2000,
                'discount_percentage' => 25.00
            ]
        ];

        foreach ($loyaltyLevels as $level) {
            Loyalty::updateOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }
}
