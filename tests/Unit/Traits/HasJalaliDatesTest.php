<?php

namespace Tests\Unit\Traits;

use App\Traits\HasJalaliDates;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ⭐ Regression test for a critical bug discovered in test-writing session 6
 * (2026-08-16): Jalalian::toCarbon() returns a plain \Carbon\Carbon (the base
 * nesbot/carbon class it imports directly), not \Illuminate\Support\Carbon.
 * Since Illuminate\Support\Carbon *extends* \Carbon\Carbon, returning the base
 * class instance from a method declared to return Illuminate\Support\Carbon threw
 * a real TypeError on every successful parse. This was invisible everywhere except
 * the two call sites using the fail-fast parseJalaliOrFail() variant, because the
 * non-throwing parseJalali() has a broad catch(\Throwable) that silently swallowed
 * the very same TypeError.
 */
class HasJalaliDatesTest extends TestCase
{
    private function subject(): object
    {
        return new class
        {
            use HasJalaliDates;

            public function callParseJalali(?string $value, string $format = 'Y/m/d', ?string $context = null): ?Carbon
            {
                return $this->parseJalali($value, $format, $context);
            }

            public function callParseJalaliOrFail(string $value, string $format = 'Y/m/d'): Carbon
            {
                return $this->parseJalaliOrFail($value, $format);
            }
        };
    }

    public function test_parse_jalali_or_fail_returns_a_real_illuminate_carbon_instance_not_the_base_carbon_class(): void
    {
        $result = $this->subject()->callParseJalaliOrFail('1404/01/15');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame(2025, $result->year);
        $this->assertSame(4, $result->month);
        $this->assertSame(4, $result->day);
    }

    public function test_parse_jalali_or_fail_throws_a_catchable_exception_not_a_bare_type_error_on_invalid_input(): void
    {
        $this->expectException(\Throwable::class);

        $this->subject()->callParseJalaliOrFail('not-a-real-date');
    }

    public function test_parse_jalali_non_throwing_variant_also_returns_a_real_illuminate_carbon_instance(): void
    {
        $result = $this->subject()->callParseJalali('1404/01/15');

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_parse_jalali_returns_null_on_invalid_input_instead_of_leaking_a_type_error(): void
    {
        $this->assertNull($this->subject()->callParseJalali('garbage', context: 'test'));
    }
}
