<?php

namespace Tests\Unit\Logging;

use App\Support\Logging\PersonalData;
use PHPUnit\Framework\TestCase;

class PersonalDataTest extends TestCase
{
    public function test_iranian_mobiles_keep_only_the_prefix_and_last_four_digits(): void
    {
        $this->assertSame('0912***4567', PersonalData::mask('09121234567'));
        $this->assertSame('+98912***4567', PersonalData::mask('+989121234567'));
        $this->assertSame('0098912***4567', PersonalData::mask('00989121234567'));
        $this->assertSame('۰۹۱۲***۴۵۶۷', PersonalData::mask('۰۹۱۲۱۲۳۴۵۶۷'));
        $this->assertSame('{"mobile":"0935***0011","user_id":7}', PersonalData::mask('{"mobile":"09351230011","user_id":7}'));
        $this->assertSame("Duplicate entry '0912***4567-3' for key", PersonalData::mask("Duplicate entry '09121234567-3' for key"));
    }

    public function test_numbers_that_are_not_mobiles_stay_untouched(): void
    {
        foreach ([
            'amount 9121234567 rial',          // ۱۰ رقم بی پیشوند: مبلغ یا شناسه
            'ref 109121234567',                // ۱۲ رقم
            'authority A00000000000000000000000000009121234567',
            'user_id 09121234',                // کوتاه
            'tx 091212345678',                 // ۱۲ رقم
        ] as $text) {
            $this->assertSame($text, PersonalData::mask($text));
        }
    }

    public function test_iban_keeps_check_digits_and_last_four(): void
    {
        $this->assertSame('شبا IR06******************3456', PersonalData::mask('شبا IR062960000000100324203456'));
    }

    public function test_fingerprint_is_stable_and_never_the_secret(): void
    {
        $this->assertSame(PersonalData::fingerprint('abc'), PersonalData::fingerprint('abc'));
        $this->assertSame(12, strlen(PersonalData::fingerprint('abc')));
        $this->assertNull(PersonalData::fingerprint(null));
        $this->assertNull(PersonalData::phone(null));
    }
}
