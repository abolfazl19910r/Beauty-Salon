<?php

namespace Tests\Feature\Payment;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ انتخاب درگاه توسط مشتری در صفحه‌ی پرداخت نوبت و شارژ کیف پول (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵).
 */
class CustomerGatewayChoiceTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->salon->paymentGateways()->delete();
    }

    private function booking(User $user): Booking
    {
        return Booking::factory()->create([
            'user_id' => $user->id,
            'service_id' => BeautyService::factory()->create(['price' => 200000])->id,
            'specialist_id' => Specialist::factory()->create()->id,
            'payment_status' => 'unpaid',
            'status' => 'pending_payment',
            'prepayment_amount' => 60000,
        ]);
    }

    public function test_payment_page_lists_active_gateways_in_order_with_their_fee(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'vandar', 'label' => 'پرداخت سریع', 'credentials' => ['api_key' => 'k'], 'priority' => 2, 'fee_percent' => 1]);
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
        $this->salon->paymentGateways()->create(['driver' => 'asanpardakht', 'credentials' => ['merchant_config_id' => '1', 'username' => 'u', 'password' => 'p'], 'priority' => 3, 'is_active' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payment.show', $this->booking($user)))
            ->assertOk()
            ->assertSee('انتخاب درگاه پرداخت')
            ->assertSeeInOrder(['زیبال', 'بدون کارمزد', 'پرداخت سریع', 'کارمزد: 600 تومان'])
            ->assertDontSee('آسان پرداخت')
            ->assertSee('اگر درگاه انتخابی در دسترس نباشد')
            ->assertDontSee('درگاه امن زرین‌پال');
    }

    public function test_payment_page_has_no_picker_when_the_salon_has_no_gateway(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('payment.show', $this->booking($user)))
            ->assertOk()->assertDontSee('انتخاب درگاه پرداخت');
    }

    public function test_wallet_top_up_page_shows_the_picker_with_fee_data(): void
    {
        $this->salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1, 'fee_percent' => 0.5, 'fee_fixed_toman' => 300]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('wallet.charge'))
            ->assertOk()
            ->assertSee('انتخاب درگاه پرداخت')
            ->assertSee('data-fee-percent="0.5" data-fee-fixed="300"', false)
            ->assertSee('کارمزد درگاه / مبلغ پرداختی');
    }
}
