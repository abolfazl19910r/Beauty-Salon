<?php

namespace Tests\Feature\Sms;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Links\ShortLinkService;
use App\Support\Sms\SmsText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * متن‌های پیش‌فرض قالب‌ها (۲۰۲۶-۱۰-۱۴) باید دقیقاً همان خروجی SmsText پیش از قالب‌پذیر شدن باشند.
 * fixtures/sms-template-defaults.json از اجرای همین فهرست روی کد قبلی (develop@ca2dbd7) گرفته شده است.
 */
class SmsTemplateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private static function outputs(): array
    {
        Carbon::setTestNow('2026-10-14 09:00:00');
        $salon = app(\App\Support\CurrentSalon::class)->get();
        $salon->update(['name' => 'سالن زیبایی رزگلد', 'sms_name' => 'رزگلد']);
        $service = BeautyService::factory()->create(['name' => 'رنگ و لایت کامل', 'sms_name' => 'رنگ و لایت', 'price' => 1200000, 'salon_id' => $salon->id]);
        $user = User::factory()->create(['name' => 'مریم رضایی', 'phone' => '09121234567', 'user_type' => 'customer', 'salon_id' => $salon->id]);
        $b = Booking::factory()->create([
            'salon_id' => $salon->id, 'user_id' => $user->id, 'service_id' => $service->id,
            'specialist_id' => Specialist::factory()->create(['salon_id' => $salon->id])->id,
            'booking_time' => Carbon::parse('2026-10-20 10:30:00'), 'status' => 'confirmed', 'payment_status' => 'paid',
            'prepayment_amount' => 240000, 'discount_amount' => 110000,
        ]);
        app()->instance(ShortLinkService::class, new class
        {
            public function shorten(string $url, $expires = null): string
            {
                return 'http://x/b/LINK';
            }
        });
        $expires = Carbon::parse('2026-12-21');

        return [
            'bookingPending' => SmsText::bookingPending($b),
            'bookingConfirmed' => SmsText::bookingConfirmed($b),
            'bookingCompleted' => SmsText::bookingCompleted($b),
            'cancelledPlain' => SmsText::bookingCancelledForCustomer($b),
            'cancelledFull' => SmsText::bookingCancelledForCustomer($b, 'بیماری متخصص', 300000, 50000),
            'cancelledUnpaid' => SmsText::bookingCancelledUnpaid($b),
            'reminderCustomer' => SmsText::reminderForCustomer($b),
            'rescheduled' => SmsText::rescheduledForCustomer($b),
            'rescheduledAwaiting' => SmsText::rescheduledForCustomer($b, true),
            'reviewRequest' => SmsText::reviewRequest($b, 'http://x/b/REV'),
            'newBooking' => SmsText::newBookingForSpecialist($b, false),
            'newBookingApproval' => SmsText::newBookingForSpecialist($b, true),
            'reminderSpecialist' => SmsText::reminderForSpecialist($b),
            'cancelledSpecialist' => SmsText::cancelledForSpecialist($b),
            'cancelledSpecialistFull' => SmsText::cancelledForSpecialist($b, 'customer', 50000),
            'rescheduledSpecialist' => SmsText::rescheduledForSpecialist($b, Carbon::parse('2026-10-19 16:00:00')),
            'paymentReceived' => SmsText::paymentReceived($b, 350000, 'درگاه'),
            'pointsEarned' => SmsText::pointsEarned(120, 1540, $salon),
            'pointsNoSalon' => SmsText::pointsEarned(120, 1540, null),
            'rewardRedeemed' => SmsText::rewardRedeemed('GIFT7', 'کوتاهی رایگان برای همیشه عزیزان', $expires, $salon),
            'rewardNoExpiry' => SmsText::rewardRedeemed('GIFT7', 'کوتاهی', null, $salon),
            'discountIssued' => SmsText::discountCodeIssued('NOROOZ', '20٪', $expires, $salon),
            'discountUsedUp' => SmsText::discountCodeUsedUp('NOROOZ', $salon),
        ];
    }

    public function test_default_texts_are_exactly_the_old_ones(): void
    {
        $expected = json_decode(file_get_contents(__DIR__.'/fixtures/sms-template-defaults.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($expected, self::outputs());
    }
}
