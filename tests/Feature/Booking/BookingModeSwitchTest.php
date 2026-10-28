<?php

namespace Tests\Feature\Booking;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletSetting;
use App\Notifications\Booking\BookingNotification;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * صفحه‌ی «نحوه‌ی رزرو» و تغییر حالت (بسته‌ی 5ه، تصمیم‌های ۱، ۲، ۷ و ۸).
 */
class BookingModeSwitchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    /** @var list<string> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        // اولین is_admin ساخته‌شده خودکار owner سالن جاری می‌شود (UserFactory::configure)
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $text) {
                $this->sms[] = $text;

                return true;
            });
            $mock->shouldIgnoreMissing(true);
        });
    }

    private function awaitingPayment(array $extra = []): Booking
    {
        $service = BeautyService::factory()->create(['price' => 300000]);

        return Booking::factory()->create(array_merge([
            'service_id' => $service->id, 'specialist_id' => Specialist::factory()->create()->id,
            'user_id' => User::factory()->create(['phone' => '0912'.random_int(1000000, 9999999)])->id,
            'status' => 'pending_payment', 'payment_status' => 'unpaid', 'prepayment_required' => true,
            'prepayment_amount' => 90000, 'discount_amount' => 10000, 'booking_time' => now()->addDays(3)->setTime(11, 0),
        ], $extra));
    }

    public function test_owner_sees_both_modes_with_their_explanations(): void
    {
        $this->actingAs($this->owner)->get(route('admin.booking-policy.edit'))->assertOk()
            ->assertSee('بدون پیش‌پرداخت (پرداخت کامل در سالن)')
            ->assertSee('با پیش‌پرداخت آنلاین')
            ->assertSee('احتمال رزرو فیک و نیامدن بیشتر است')
            ->assertSee('کیف پول متخصص از این نوبت‌ها شارژ نمی‌شود');
    }

    public function test_a_salon_staff_member_who_is_not_the_owner_cannot_change_it(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        app(CurrentSalon::class)->get()->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);
        app(CurrentSalon::class)->get()->admins()->updateExistingPivot($staff->id, ['role' => 'staff']);

        $this->actingAs($staff)->put(route('admin.booking-policy.mode'), ['payment_mode' => 'none'])->assertForbidden();
    }

    public function test_prepayment_needs_an_active_gateway(): void
    {
        app(CurrentSalon::class)->get()->paymentGateways()->update(['is_active' => false]);

        $this->actingAs($this->owner)->put(route('admin.booking-policy.mode'), [
            'payment_mode' => 'prepay', 'prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000,
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'درگاه'));

        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);
    }

    public function test_prepayment_needs_a_non_zero_amount(): void
    {
        $this->actingAs($this->owner)->put(route('admin.booking-policy.mode'), [
            'payment_mode' => 'prepay', 'prepayment_percentage' => 0, 'minimum_prepayment_amount' => 0,
        ])->assertSessionHas('error');

        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);
    }

    public function test_switching_to_prepayment_saves_the_amounts_and_leaves_existing_bookings_alone(): void
    {
        $old = Booking::factory()->withoutPrepayment()->create(['status' => 'pending', 'booking_time' => now()->addDays(2)]);

        $this->actingAs($this->owner)->put(route('admin.booking-policy.mode'), [
            'payment_mode' => 'prepay', 'prepayment_percentage' => 25, 'minimum_prepayment_amount' => 40000,
        ])->assertRedirect(route('admin.booking-policy.edit'))->assertSessionHas('success');

        $this->assertSame(BookingPolicy::MODE_PREPAY, BookingPolicy::current()->payment_mode);
        $this->assertSame(25.0, (float) WalletSetting::get()->prepayment_percentage);
        $this->assertSame(40000.0, (float) WalletSetting::get()->minimum_prepayment_amount);

        $old->refresh();
        $this->assertFalse($old->prepayment_required, 'پیش‌پرداخت عقب‌افتاده خواسته نمی‌شود');
        $this->assertSame('pending', $old->status);
        $this->assertFalse($old->isPayable());
    }

    public function test_switching_to_no_prepayment_moves_awaiting_payment_bookings_to_awaiting_confirmation(): void
    {
        $this->usePrepaymentMode();
        $waiting = $this->awaitingPayment();
        $paid = $this->awaitingPayment(['status' => 'confirmed', 'payment_status' => 'paid', 'booking_time' => now()->addDays(4)->setTime(11, 0)]);

        $this->actingAs($this->owner)->put(route('admin.booking-policy.mode'), ['payment_mode' => 'none'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, '۱ نوبت'));

        $waiting->refresh();
        $this->assertSame('pending', $waiting->status);
        $this->assertFalse($waiting->prepayment_required);
        $this->assertSame(0.0, (float) $waiting->prepayment_amount);
        $this->assertSame(290000.0, $waiting->remaining_amount, 'کل منهای تخفیف در سالن');
        $this->assertFalse($waiting->isPayable());

        $this->assertTrue($paid->fresh()->prepayment_required, 'نوبت پرداخت‌شده برچسبش می‌ماند');
        $this->assertSame('confirmed', $paid->fresh()->status);

        $customerSms = array_values(array_filter($this->sms, fn ($t) => str_contains($t, 'دیگر پیش‌پرداخت لازم ندارد')));
        $this->assertCount(1, $customerSms);
        $this->assertStringContainsString('290,000', $customerSms[0]);
        $this->assertStringNotContainsString('پرداخت کنید', $customerSms[0], 'هیچ پیامی درخواست پرداخت نمی‌کند');
        Notification::assertSentTo($waiting->specialist, BookingNotification::class);
        Notification::assertNotSentTo($paid->specialist, BookingNotification::class);
    }
}
