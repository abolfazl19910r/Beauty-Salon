<?php

namespace Tests\Unit\Services\Discount;

use App\Models\DiscountCode;
use App\Services\Discount\DiscountCalculator;
use Tests\TestCase;

/**
 * DiscountCalculator is documented as "the only source of discount calculation in the entire
 * project" (R-DiscountLogic) — a formula bug here silently affects every discount code, in every
 * flow (booking creation, applyDiscountCode, preview). These tests exercise every branch of the
 * formula independently of the database.
 */
class DiscountCalculatorTest extends TestCase
{
    private DiscountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DiscountCalculator;
    }

    public function test_percentage_discount_is_calculated_correctly(): void
    {
        $code = new DiscountCode(['type' => 'percentage', 'amount' => 10, 'max_amount' => null]);

        $result = $this->calculator->calculate($code, 100000);

        $this->assertSame(10000.0, $result['discount_amount']);
        $this->assertSame(90000.0, $result['final_amount']);
    }

    public function test_fixed_discount_is_calculated_correctly(): void
    {
        $code = new DiscountCode(['type' => 'fixed', 'amount' => 15000, 'max_amount' => null]);

        $result = $this->calculator->calculate($code, 100000);

        $this->assertSame(15000.0, $result['discount_amount']);
        $this->assertSame(85000.0, $result['final_amount']);
    }

    public function test_percentage_discount_is_capped_by_max_amount(): void
    {
        // 50% of 500,000 = 250,000, but max_amount caps it at 30,000.
        $code = new DiscountCode(['type' => 'percentage', 'amount' => 50, 'max_amount' => 30000]);

        $result = $this->calculator->calculate($code, 500000);

        $this->assertSame(30000.0, $result['discount_amount']);
        $this->assertSame(470000.0, $result['final_amount']);
    }

    public function test_fixed_discount_is_capped_by_max_amount(): void
    {
        $code = new DiscountCode(['type' => 'fixed', 'amount' => 100000, 'max_amount' => 40000]);

        $result = $this->calculator->calculate($code, 500000);

        $this->assertSame(40000.0, $result['discount_amount']);
    }

    public function test_discount_never_exceeds_base_amount_even_without_max_amount_cap(): void
    {
        // Fixed discount of 200,000 on a base amount of only 50,000 — the discount must be
        // clamped to the base amount so final_amount never goes negative.
        $code = new DiscountCode(['type' => 'fixed', 'amount' => 200000, 'max_amount' => null]);

        $result = $this->calculator->calculate($code, 50000);

        $this->assertSame(50000.0, $result['discount_amount']);
        $this->assertEquals(0.0, $result['final_amount']);
    }

    public function test_discount_never_exceeds_base_amount_when_max_amount_cap_is_higher(): void
    {
        // max_amount (80,000) is higher than the base amount (50,000) — the base-amount clamp
        // must still apply as the final safety net.
        $code = new DiscountCode(['type' => 'fixed', 'amount' => 200000, 'max_amount' => 80000]);

        $result = $this->calculator->calculate($code, 50000);

        $this->assertSame(50000.0, $result['discount_amount']);
        $this->assertEquals(0.0, $result['final_amount']);
    }

    public function test_percentage_discount_of_zero_base_amount_is_zero(): void
    {
        $code = new DiscountCode(['type' => 'percentage', 'amount' => 20, 'max_amount' => null]);

        $result = $this->calculator->calculate($code, 0);

        $this->assertSame(0.0, $result['discount_amount']);
        $this->assertEquals(0.0, $result['final_amount']);
    }

    public function test_hundred_percent_discount_zeroes_the_final_amount(): void
    {
        $code = new DiscountCode(['type' => 'percentage', 'amount' => 100, 'max_amount' => null]);

        $result = $this->calculator->calculate($code, 75000);

        $this->assertSame(75000.0, $result['discount_amount']);
        $this->assertEquals(0.0, $result['final_amount']);
    }
}
