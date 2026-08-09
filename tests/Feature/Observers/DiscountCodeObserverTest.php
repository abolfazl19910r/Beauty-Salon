<?php

namespace Tests\Feature\Observers;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCodeObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_code_is_automatically_deactivated_once_it_reaches_max_uses(): void
    {
        $code = DiscountCode::factory()->create(['max_uses' => 3, 'used_count' => 2, 'is_active' => true]);

        $code->update(['used_count' => 3]);

        $this->assertFalse((bool) $code->fresh()->is_active);
    }

    public function test_code_remains_active_while_below_max_uses(): void
    {
        $code = DiscountCode::factory()->create(['max_uses' => 3, 'used_count' => 1, 'is_active' => true]);

        $code->update(['used_count' => 2]);

        $this->assertTrue((bool) $code->fresh()->is_active);
    }

    public function test_code_with_zero_max_uses_is_never_auto_deactivated(): void
    {
        // max_uses is a NOT NULL column on discount_codes — 0 (falsy) is how "unlimited" is
        // represented, per DiscountCode::isValid()'s `$this->max_uses &&` guard.
        $code = DiscountCode::factory()->create(['max_uses' => 0, 'used_count' => 0, 'is_active' => true]);

        $code->update(['used_count' => 500]);

        $this->assertTrue((bool) $code->fresh()->is_active);
    }

    public function test_auto_deactivation_does_not_touch_updated_at_timestamp_meaningfully_via_events(): void
    {
        // saveQuietly() is used deliberately so this internal deactivation doesn't re-trigger the
        // observer's updated() hook recursively.
        $code = DiscountCode::factory()->create(['max_uses' => 1, 'used_count' => 0, 'is_active' => true]);

        $code->update(['used_count' => 1]);

        $this->assertFalse((bool) $code->fresh()->is_active);
    }
}
