<?php

namespace Tests\Unit;

use App\Support\Iban;
use PHPUnit\Framework\TestCase;

class IbanTest extends TestCase
{
    private const VALID = 'IR820540102680020817909002';

    public function test_known_valid_iranian_ibans_pass(): void
    {
        $this->assertTrue(Iban::isValid(self::VALID));
        $this->assertTrue(Iban::isValid('IR062960000000100324200001'));
        $this->assertTrue(Iban::isValid('ir82 0540 1026 8002 0817 9090 02'));
    }

    public function test_wrong_shape_fails(): void
    {
        foreach ([null, '', 'IR', '820540102680020817909002', 'DE820540102680020817909002', 'IR82054010268002081790900', 'IR8205401026800208179090021', 'IR82054010268002081790900A'] as $iban) {
            $this->assertFalse(Iban::isValid($iban), var_export($iban, true));
        }
    }

    public function test_every_single_digit_typo_is_caught(): void
    {
        for ($i = 2; $i < strlen(self::VALID); $i++) {
            foreach (range(0, 9) as $digit) {
                if ((string) $digit === self::VALID[$i]) {
                    continue;
                }
                $typo = substr_replace(self::VALID, (string) $digit, $i, 1);
                $this->assertFalse(Iban::isValid($typo), $typo);
            }
        }
    }

    public function test_every_adjacent_transposition_is_caught(): void
    {
        for ($i = 2; $i < strlen(self::VALID) - 1; $i++) {
            if (self::VALID[$i] === self::VALID[$i + 1]) {
                continue;
            }
            $typo = self::VALID;
            [$typo[$i], $typo[$i + 1]] = [$typo[$i + 1], $typo[$i]];
            $this->assertFalse(Iban::isValid($typo), $typo);
        }
    }

    public function test_from_bban_builds_a_valid_iban(): void
    {
        $this->assertSame(self::VALID, Iban::fromBban('0540102680020817909002'));

        mt_srand(1234);
        for ($n = 0; $n < 500; $n++) {
            $bban = '';
            for ($d = 0; $d < 22; $d++) {
                $bban .= mt_rand(0, 9);
            }
            $this->assertTrue(Iban::isValid(Iban::fromBban($bban)), $bban);
        }
    }

    public function test_from_bban_rejects_a_bban_of_the_wrong_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Iban::fromBban('123');
    }
}
