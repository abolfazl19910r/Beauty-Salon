<?php

namespace Tests\Feature\Models;

use App\Models\Specialist;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistCommissionRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_own_commission_rate_when_set(): void
    {
        WalletSetting::first()->update(['admin_commission_percentage' => 10]);
        $specialist = Specialist::factory()->create(['commission_rate' => 15]);

        $this->assertSame(15.0, $specialist->getEffectiveCommissionRate());
    }

    public function test_falls_back_to_global_setting_when_own_rate_is_null(): void
    {
        WalletSetting::first()->update(['admin_commission_percentage' => 12]);
        $specialist = Specialist::factory()->create(['commission_rate' => null]);

        $this->assertSame(12.0, $specialist->getEffectiveCommissionRate());
    }

    public function test_own_commission_rate_of_zero_is_respected_not_treated_as_unset(): void
    {
        // 0 is a valid, deliberate commission rate (specialist keeps 100%) — must not be confused
        // with "not set" (which would incorrectly fall back to the global percentage).
        WalletSetting::first()->update(['admin_commission_percentage' => 10]);
        $specialist = Specialist::factory()->create(['commission_rate' => 0]);

        $this->assertSame(0.0, $specialist->getEffectiveCommissionRate());
    }

    public function test_falls_back_to_default_ten_percent_when_no_wallet_setting_row_exists(): void
    {
        WalletSetting::query()->delete();
        $specialist = Specialist::factory()->create(['commission_rate' => null]);

        $this->assertSame(10.0, $specialist->getEffectiveCommissionRate());
    }
}
