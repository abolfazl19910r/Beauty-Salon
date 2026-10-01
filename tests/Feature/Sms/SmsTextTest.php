<?php

namespace Tests\Feature\Sms;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Support\Sms\SmsText;
use App\Support\SmsParts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * متن‌های کوتاه‌شده‌ی پیامک (تأییدشده‌ی ۲۰۲۶-۰۹-۳۰): بدون ایموجی؛ بیشتر ۱ قطعه؛ ثبت نوبت (کل + مانده)، نوبت جدید با لینک تایید و
 * تایید نوبت (با لینک آدرس) ۲ قطعه. با نام‌های معمولی، و با نام‌های بلند وقتی نام کوتاه پیامکی گذاشته شده.
 */
class SmsTextTest extends TestCase
{
    use RefreshDatabase;

    private function booking(string $salon, ?string $salonSms, string $service, ?string $serviceSms, string $customer, int $price): Booking
    {
        config(['services.sms_links.host' => 'rasta.ir']);
        $s = app(\App\Support\CurrentSalon::class)->get(); // خدمت و متخصص زیر BelongsToSalon همان سالن جاری‌اند
        $s->update(['name' => $salon, 'sms_name' => $salonSms]);
        $srv = BeautyService::factory()->create(['name' => $service, 'sms_name' => $serviceSms, 'price' => $price, 'salon_id' => $s->id]);
        $user = User::factory()->create(['name' => $customer, 'phone' => '09121234567', 'user_type' => 'customer', 'salon_id' => $s->id]);

        return Booking::factory()->create([
            'salon_id' => $s->id, 'service_id' => $srv->id, 'user_id' => $user->id,
            'specialist_id' => Specialist::factory()->create(['salon_id' => $s->id])->id,
            'prepayment_amount' => (int) ($price * 0.2), 'discount_amount' => 0,
            'booking_time' => now()->addDays(2)->setTime(15, 30),
        ]);
    }

    public static function names(): array
    {
        return [
            'typical names' => ['سالن ماهرو', null, 'کوتاهی مو', null, 'سارا احمدی', 450000],
            'long names with SMS short names' => ['سالن زیبایی رز سفید شمال تهران', 'رز سفید', 'کراتینه و احیای موی آسیب‌دیده', 'کراتینه', 'فاطمه‌سادات موسوی', 1250000],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('names')]
    public function test_each_text_stays_within_its_part_budget(string $salon, ?string $salonSms, string $service, ?string $serviceSms, string $customer, int $price): void
    {
        $b = $this->booking($salon, $salonSms, $service, $serviceSms, $customer, $price);

        $budget = [
            'ثبت نوبت (کل و مانده)' => [SmsText::bookingPending($b), 2],
            'تایید با لینک آدرس' => [SmsText::bookingConfirmed($b), 2],
            'نوبت جدید با لینک تایید' => [SmsText::newBookingForSpecialist($b, true), 2],
            'نوبت جدید، تایید خودکار' => [SmsText::newBookingForSpecialist($b, false), 1],
            'یادآوری مشتری' => [SmsText::reminderForCustomer($b), 1],
            'یادآوری متخصص' => [SmsText::reminderForSpecialist($b), 1],
            'لغو بدون بازگشت' => [SmsText::bookingCancelledForCustomer($b, 'بیماری متخصص'), 1],
            'لغو عدم پرداخت' => [SmsText::bookingCancelledUnpaid($b), 1],
            'لغو به متخصص' => [SmsText::cancelledForSpecialist($b, 'customer'), 1],
            'تغییر زمان مشتری' => [SmsText::rescheduledForCustomer($b), 1],
            'تغییر زمان متخصص' => [SmsText::rescheduledForSpecialist($b, now()->addDay()->setTime(12, 0)), 1],
            'درخواست نظر' => [SmsText::reviewRequest($b, 'rasta.ir/b/ab3xk9'), 1],
            'واریز برداشت' => [SmsText::withdrawalApproved(1333340, 'WD-ZEZBZOFX'), 1],
        ];

        foreach ($budget as $label => [$text, $parts]) {
            $this->assertLessThanOrEqual($parts, SmsParts::count($text), "{$label} ({$salon}): «{$text}»");
            $this->assertDoesNotMatchRegularExpression('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $text, "{$label} has an emoji");
        }
    }

    public function test_the_details_that_matter_are_in_their_message(): void
    {
        $b = $this->booking('سالن ماهرو', null, 'کوتاهی مو', null, 'سارا احمدی', 450000);
        $remaining = number_format($b->remaining_amount);

        $this->assertStringContainsString('450,000', SmsText::bookingPending($b));
        $this->assertStringContainsString($remaining, SmsText::bookingPending($b));
        $this->assertStringContainsString($remaining, SmsText::newBookingForSpecialist($b, true));
        $this->assertStringContainsString($remaining, SmsText::newBookingForSpecialist($b, false));
        $this->assertStringContainsString($remaining, SmsText::bookingConfirmed($b));
        $this->assertStringContainsString('rasta.ir/b/', SmsText::bookingConfirmed($b));
        $this->assertStringContainsString('۱۵ دقیقه زودتر', SmsText::reminderForCustomer($b));
        $this->assertStringContainsString('09121234567', SmsText::reminderForSpecialist($b));
        $this->assertStringContainsString('دلیل: بیماری متخصص', SmsText::bookingCancelledForCustomer($b, 'بیماری متخصص'));
        $this->assertStringContainsString('بازگشت 90,000 تومان (جریمه 10,000)', SmsText::bookingCancelledForCustomer($b, null, 90000, 10000));
    }
}
