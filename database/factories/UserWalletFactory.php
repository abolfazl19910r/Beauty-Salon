<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ⭐ Discovered missing in test-writing session 6 (2026-08-16): UserWallet already had
 * HasFactory but no factory file existed on disk — the exact same recurring pattern
 * documented repeatedly throughout this project (Permission, BlogCategory,
 * Announcement, GalleryImage, Review, ReportExport all had the same gap).
 */
class UserWalletFactory extends Factory
{
    protected $model = UserWallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0,
            'total_deposited' => 0,
            'total_spent' => 0,
        ];
    }
}
