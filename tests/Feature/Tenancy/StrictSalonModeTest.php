<?php

namespace Tests\Feature\Tenancy;

use App\Exceptions\MissingSalonContextException;
use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Salon;
use App\Models\WalletSetting;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * حالت سخت‌گیر BelongsToSalon (۲۰۲۶-۱۰-۰۱). پیش از آن، بدون سالن جاری scope هیچ شرطی نمی‌گذاشت: با تست موقت دیده شد که
 * WalletSetting::get() و AdminWallet::getWallet() ردیف «اولین سالن» را برمی‌گرداندند و WalletSetting::query()->delete()
 * تنظیمات همه‌ی سالن‌ها را پاک می‌کرد.
 */
class StrictSalonModeTest extends TestCase
{
    use RefreshDatabase;

    private Salon $a;

    private Salon $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = app(CurrentSalon::class)->get();
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 111]);
        AdminWallet::getWallet();
        $this->b = Salon::factory()->create(['slug' => 'strict-mode-b']);
        app(CurrentSalon::class)->set($this->b);
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 222]);
        AdminWallet::getWallet();
        app(CurrentSalon::class)->clear();
    }

    public function test_a_salon_scoped_query_without_a_current_salon_throws_instead_of_reading_another_salon(): void
    {
        foreach ([fn () => WalletSetting::get(), fn () => AdminWallet::getWallet(), fn () => Booking::count(), fn () => Review::count(), fn () => WalletSetting::query()->delete()] as $i => $query) {
            try {
                $query();
                $this->fail("query #{$i} ran without a salon");
            } catch (MissingSalonContextException) {
            }
        }

        $this->assertSame(2, WalletSetting::withoutGlobalScopes()->count(), 'nothing was deleted');
    }

    public function test_all_salons_is_explicit_and_restores_the_previous_salon(): void
    {
        app(CurrentSalon::class)->set($this->b);

        $count = app(CurrentSalon::class)->allSalons(fn () => WalletSetting::count());

        $this->assertSame(2, $count);
        $this->assertSame($this->b->id, app(CurrentSalon::class)->id());
    }

    public function test_with_salon_reads_that_salon_and_restores_no_salon(): void
    {
        $min = app(CurrentSalon::class)->withSalon($this->b->id, fn () => (int) WalletSetting::get()->minimum_withdrawal_amount);

        $this->assertSame(222, $min);
        $this->assertNull(app(CurrentSalon::class)->id());
        $this->expectException(MissingSalonContextException::class);
        app(CurrentSalon::class)->allSalons(fn () => app(CurrentSalon::class)->withSalon(null, fn () => null)) ?? WalletSetting::get();
    }

    public function test_log_mode_is_only_an_emergency_switch_that_keeps_the_old_behaviour(): void
    {
        config(['tenancy.strict' => 'log']);
        Log::shouldReceive('warning')->once()->withArgs(fn ($message) => str_contains($message, 'without a current salon'));

        $this->assertSame(2, WalletSetting::count());
    }

    public function test_every_request_starts_without_a_salon_left_over_from_outside_it(): void
    {
        app(CurrentSalon::class)->set($this->b);

        $this->get('/')->assertOk();

        $this->assertNull(app(CurrentSalon::class)->id());
    }
}
