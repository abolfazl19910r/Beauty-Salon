<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class DiscountCodeSeeder extends Seeder
{
    public function run(): void
    {
        DiscountCode::firstOrCreate(
            ['code' => 'WELCOME20'],
            [
                'type' => 'percentage',
                'amount' => 20,
                'max_uses' => 50,
                'is_active' => true,
                'expires_at' => now()->addMonths(3),
            ]
        );

        DiscountCode::firstOrCreate(
            ['code' => 'FIXED50'],
            [
                'type' => 'fixed',
                'amount' => 50000,
                'max_uses' => 100,
                'is_active' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );

        DiscountCode::factory()->expired()->create(['code' => 'OLDCODE']);
        DiscountCode::factory()->full()->create(['code' => 'FULLUSAGE', 'max_uses' => 5]);
        DiscountCode::factory(10)->create();

        $firstUser = User::where('is_admin', false)->first();
        if ($firstUser) {
            DiscountCode::factory(3)->percentage(15)->personal($firstUser)->create([
                'max_uses' => 1,
            ]);
        }
    }
}
