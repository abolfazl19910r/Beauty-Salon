<?php

namespace Tests\Feature\Booking;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Support\Booking\PurchaseTerms;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اطلاعات پیش از خرید و رسید ماندگار نوبت (بسته‌ی مجوز 5د؛ مواد ۳۳ تا ۳۵ قانون تجارت الکترونیکی). 🐞 پیش‌تر صفحه‌ی تأیید رزرو
 * هویت و نشانی ارائه‌دهنده و شرایط لغو و برگشت پیش‌پرداخت را نشان نمی‌داد (شرایط لغو هیچ‌جا به مشتری نشان داده نمی‌شد).
 */
class PurchaseTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->usePrepaymentMode(); // بسته‌ی 5ه: این تست‌ها رفتار «با پیش‌پرداخت» را می‌سنجند
        app(CurrentSalon::class)->get()->update(['address' => 'تهران، خیابان آزادی، پلاک ۵', 'phone' => '02144556677']);
    }

    private function assertTermsShown(string $html): void
    {
        $this->assertStringContainsString('data-purchase-terms', $html);
        $this->assertStringContainsString('ارائه‌دهنده‌ی خدمت', $html);
        $this->assertStringContainsString('تهران، خیابان آزادی، پلاک ۵', $html);
        $this->assertStringContainsString('02144556677', $html);
        $this->assertStringContainsString('شرایط لغو و برگشت پیش‌پرداخت', $html);
        $this->assertStringContainsString('۲۴ ساعت پیش از زمان نوبت', $html);
        $this->assertStringContainsString(route('complaints.create'), $html);
    }

    public function test_the_confirmation_page_shows_provider_cancellation_terms_and_complaints_before_payment(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $specialist = Specialist::factory()->create();
        $target = now()->addDay()->setTime(10, 0);
        SpecialistSchedule::factory()->create(['specialist_id' => $specialist->id, 'day_of_week' => $target->dayOfWeek, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);

        $html = $this->actingAs($this->user)->post(route('bookings.confirm'), [
            'service_id' => $service->id, 'specialist_id' => $specialist->id, 'booking_time' => $target->format('Y-m-d H:i:s'),
        ])->assertOk()->getContent();

        $this->assertTermsShown($html);
    }

    public function test_the_booking_page_keeps_the_same_terms_as_a_durable_receipt(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed', 'booking_time' => now()->addDays(3)]);

        $this->assertTermsShown($this->actingAs($this->user)->get(route('bookings.show', $booking))->assertOk()->getContent());
    }

    public function test_the_cancellation_text_follows_the_salons_own_settings(): void
    {
        $settings = WalletSetting::get();
        $settings->update(['cancellation_before_hours' => 48, 'customer_cancellation_fee_percentage' => 30]);

        $lines = implode("\n", PurchaseTerms::cancellation($settings->fresh()));
        $this->assertStringContainsString('کمتر از ۴۸ ساعت', $lines);
        $this->assertStringContainsString('۳۰٪', $lines);
        $this->assertStringContainsString('۳۰ دقیقه', $lines, 'مهلت پرداخت نوبت');

        $settings->update(['cancellation_before_hours' => 24]);
        $this->assertStringContainsString('کل پیش‌پرداخت به کیف پول', implode("\n", PurchaseTerms::cancellation($settings->fresh())),
            'وقتی جریمه در مهلت مجاز لغو رخ نمی‌دهد، وعده‌ی جریمه نمی‌آید');
    }

    public function test_the_app_quote_carries_the_same_terms(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $token = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()])->createToken('a', ['customer'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/customer/bookings/quote', ['service_id' => $service->id])
            ->assertOk()
            ->assertJsonPath('data.terms.provider.address', 'تهران، خیابان آزادی، پلاک ۵')
            ->assertJsonPath('data.terms.provider.phone', '02144556677')
            ->assertJsonCount(4, 'data.terms.cancellation');
    }
}
