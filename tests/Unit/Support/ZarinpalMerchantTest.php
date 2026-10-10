<?php

namespace Tests\Unit\Support;

use App\Support\ZarinpalMerchant;
use PHPUnit\Framework\TestCase;

/**
 * رگرسیون: seeder جای‌نگهدار «your-merchant-id» از ‎.env را به‌عنوان مرچنت سالن دمو ذخیره می‌کرد و
 * هر پرداخت سندباکس با خطای ‎-9 زرین‌پال رد می‌شد.
 */
class ZarinpalMerchantTest extends TestCase
{
    public function test_valid_or_null_keeps_only_real_merchant_ids(): void
    {
        $this->assertSame('00000000-0000-0000-0000-000000000000', ZarinpalMerchant::validOrNull(' 00000000-0000-0000-0000-000000000000 '));
        $this->assertSame('abcdef12-3456-7890-abcd-ef1234567890', ZarinpalMerchant::validOrNull('ABCDEF12-3456-7890-ABCD-EF1234567890'));

        foreach (['your-merchant-id', '00000000-0000-0000-0000-00000000000', '', null] as $invalid) {
            $this->assertNull(ZarinpalMerchant::validOrNull($invalid), var_export($invalid, true));
        }
    }
}
