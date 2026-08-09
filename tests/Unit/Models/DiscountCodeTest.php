<?php

namespace Tests\Unit\Models;

use App\Models\DiscountCode;
use Tests\TestCase;

class DiscountCodeTest extends TestCase
{
    private function makeCode(array $overrides = []): DiscountCode
    {
        return new DiscountCode(array_merge([
            'code' => 'TEST123',
            'type' => 'percentage',
            'amount' => 10,
            'max_uses' => 10,
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => null,
            'user_id' => null,
        ], $overrides));
    }

    public function test_active_code_within_limits_is_valid(): void
    {
        $this->assertTrue($this->makeCode()->isValid());
    }

    public function test_inactive_code_is_invalid(): void
    {
        $this->assertFalse($this->makeCode(['is_active' => false])->isValid());
    }

    public function test_fully_used_code_is_invalid(): void
    {
        $this->assertFalse($this->makeCode(['used_count' => 10, 'max_uses' => 10])->isValid());
    }

    public function test_code_used_beyond_max_uses_is_invalid(): void
    {
        // Defensive: even if used_count somehow exceeds max_uses (e.g. a stale race), isValid()
        // must still report false, not just for the exact-equal case.
        $this->assertFalse($this->makeCode(['used_count' => 11, 'max_uses' => 10])->isValid());
    }

    public function test_code_with_uses_remaining_is_valid(): void
    {
        $this->assertTrue($this->makeCode(['used_count' => 9, 'max_uses' => 10])->isValid());
    }

    public function test_expired_code_is_invalid(): void
    {
        $this->assertFalse($this->makeCode(['expires_at' => now()->subDay()])->isValid());
    }

    public function test_code_with_future_expiry_is_valid(): void
    {
        $this->assertTrue($this->makeCode(['expires_at' => now()->addDay()])->isValid());
    }

    public function test_code_with_no_expiry_is_valid(): void
    {
        $this->assertTrue($this->makeCode(['expires_at' => null])->isValid());
    }

    public function test_code_with_string_expiry_date_is_parsed_correctly(): void
    {
        // isValid() explicitly handles the case where expires_at wasn't cast yet (raw string).
        $code = $this->makeCode();
        $code->setRawAttributes(array_merge($code->getAttributes(), ['expires_at' => now()->subDay()->toDateTimeString()]), true);

        $this->assertFalse($code->isValid());
    }

    public function test_public_code_can_be_used_by_any_user(): void
    {
        $code = $this->makeCode(['user_id' => null]);

        $this->assertTrue($code->canBeUsedBy(1));
        $this->assertTrue($code->canBeUsedBy(999));
        $this->assertTrue($code->canBeUsedBy(null));
    }

    public function test_personal_code_can_only_be_used_by_its_owner(): void
    {
        $code = $this->makeCode(['user_id' => 42]);

        $this->assertTrue($code->canBeUsedBy(42));
        $this->assertFalse($code->canBeUsedBy(43));
        $this->assertFalse($code->canBeUsedBy(null));
    }

    public function test_is_for_specific_user(): void
    {
        $this->assertTrue($this->makeCode(['user_id' => 5])->isForSpecificUser());
        $this->assertFalse($this->makeCode(['user_id' => null])->isForSpecificUser());
    }
}
