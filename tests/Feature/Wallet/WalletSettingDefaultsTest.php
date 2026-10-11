<?php

namespace Tests\Feature\Wallet;

use App\Models\Salon;
use App\Models\WalletSetting;
use App\Services\Booking\BookingService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سالن تازه ردیف wallet_settings ندارد؛ اولین WalletSetting::get() آن را می‌سازد (۲۰۲۶-۱۰-۱۱). باگ: مدل ساخته‌شده
 * پیش‌فرض‌های دیتابیس را نداشت و در همان درخواست پیش‌پرداخت/کمیسیون/جریمه صفر می‌شد.
 */
class WalletSettingDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_read_in_a_new_salon_already_has_the_database_defaults(): void
    {
        $salon = Salon::factory()->create();
        app(CurrentSalon::class)->set($salon);
        $this->assertSame(0, WalletSetting::query()->count());

        $settings = WalletSetting::get();

        $this->assertSame(30.0, (float) $settings->prepayment_percentage);
        $this->assertSame(50000.0, (float) $settings->minimum_prepayment_amount);
        $this->assertSame(10.0, (float) $settings->admin_commission_percentage);
    }

    public function test_the_first_booking_quote_of_a_new_salon_asks_for_a_prepayment(): void
    {
        app(CurrentSalon::class)->set(Salon::factory()->create());

        $this->assertSame(60000.0, (float) app(BookingService::class)->calculatePrepayment(200000.0)['original_amount']);
    }
}
