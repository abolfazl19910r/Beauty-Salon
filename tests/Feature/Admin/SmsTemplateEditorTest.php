<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\SalonSmsTemplate;
use App\Models\ShortLink;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use App\Support\Sms\SmsTemplates;
use App\Support\Sms\SmsText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * متن پیامک قابل تنظیم هر سالن (تصمیم ۲۰۲۶-۱۰-۱۴): متن یک سالن فقط پیامک‌های همان سالن را عوض می‌کند، جای‌خالی ضروری و
 * ناشناخته رد می‌شوند و «پیش‌فرض» ردیف را پاک می‌کند.
 */
class SmsTemplateEditorTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salonA;

    private Salon $salonB;

    private User $ownerA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->ownerA = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->salonB = Salon::factory()->create(['slug' => 'other-sms']);
    }

    private function booking(Salon $salon): Booking
    {
        app(CurrentSalon::class)->set($salon);
        $service = BeautyService::factory()->create(['price' => 1000000, 'salon_id' => $salon->id]);
        $b = Booking::factory()->create([
            'salon_id' => $salon->id, 'service_id' => $service->id,
            'user_id' => User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id])->id,
            'specialist_id' => Specialist::factory()->create(['salon_id' => $salon->id])->id,
            'booking_time' => Carbon::parse('2026-10-20 10:30:00'), 'status' => 'confirmed', 'payment_status' => 'paid',
            'prepayment_amount' => 200000, 'discount_amount' => 0,
        ]);
        app(CurrentSalon::class)->set($this->salonA);

        return $b;
    }

    public function test_page_lists_every_template_with_its_placeholders(): void
    {
        $page = $this->actingAs($this->ownerA)->get(route('admin.sms-templates.index'))->assertOk();

        foreach (SmsTemplates::all() as $t) {
            $page->assertSee($t['label']);
        }
        $page->assertSee('{مانده}', false);
    }

    public function test_salon_text_is_used_for_that_salon_only(): void
    {
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_pending'), [
            'body' => "رزرو شما برای {تاریخ}   ساعت {ساعت} ثبت شد.\r\nکل {کل} - مانده {مانده}",
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('salon_sms_templates', ['salon_id' => $this->salonA->id, 'key' => 'booking_pending']);
        $this->assertSame("رزرو شما برای 1405/07/28 ساعت 10:30 ثبت شد.\nکل 1,000,000 - مانده 800,000", SmsText::bookingPending($this->booking($this->salonA)));
        $this->assertStringStartsWith('نوبت 1405/07/28 ساعت 10:30 ثبت شد، منتظر تایید.', SmsText::bookingPending($this->booking($this->salonB)));
    }

    public function test_missing_required_and_unknown_placeholders_are_rejected(): void
    {
        $this->actingAs($this->ownerA)
            ->from(route('admin.sms-templates.index'))
            ->put(route('admin.sms-templates.update', 'booking_pending'), ['body' => 'نوبت {تاریخ} {ساعت} کل {کل} {کد_ملی}'])
            ->assertRedirect(route('admin.sms-templates.index'))
            ->assertSessionHasErrorsIn('sms_booking_pending', 'body');

        $errors = session('errors')->getBag('sms_booking_pending')->get('body');
        $this->assertContains('جای‌خالی {کد_ملی} برای این پیامک تعریف نشده است.', $errors);
        $this->assertContains('جای‌خالی {مانده} ضروری است و باید در متن بماند.', $errors);
        $this->assertSame(0, SalonSmsTemplate::count());
    }

    public function test_too_long_or_empty_text_is_rejected(): void
    {
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => '   '])
            ->assertSessionHasErrorsIn('sms_booking_completed', 'body');
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => str_repeat('ا', SmsTemplates::MAX_LENGTH + 1)])
            ->assertSessionHasErrorsIn('sms_booking_completed', 'body');
        $this->assertSame(0, SalonSmsTemplate::count());
    }

    public function test_reset_and_saving_the_default_remove_the_salon_row(): void
    {
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => 'ممنون که آمدید! {سالن}']);
        $this->assertSame(1, SalonSmsTemplate::count());

        $this->actingAs($this->ownerA)->delete(route('admin.sms-templates.reset', 'booking_completed'))->assertRedirect();
        $this->assertSame(0, SalonSmsTemplate::count());
        $this->assertSame(SmsTemplates::get('booking_completed')['default'], \App\Support\Sms\SmsTemplateStore::body($this->salonA->id, 'booking_completed'));

        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => 'ممنون! {سالن}']);
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => SmsTemplates::get('booking_completed')['default']]);
        $this->assertSame(0, SalonSmsTemplate::count());
    }

    public function test_a_link_left_out_of_the_salon_text_is_never_created(): void
    {
        $b = $this->booking($this->salonA);
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'booking_confirmed'), ['body' => 'نوبت {تاریخ} {ساعت} تایید شد؛ مانده {مانده}.']);

        $before = ShortLink::count();
        $this->assertSame('نوبت 1405/07/28 10:30 تایید شد؛ مانده 800,000.', SmsText::bookingConfirmed($b));
        $this->assertSame($before, ShortLink::count());
    }

    public function test_unknown_key_is_404_and_customers_cannot_edit(): void
    {
        $this->actingAs($this->ownerA)->put(route('admin.sms-templates.update', 'otp_login'), ['body' => 'x'])->assertNotFound();

        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salonA->id]);
        $this->actingAs($customer)->get(route('admin.sms-templates.index'))->assertForbidden();
        $this->actingAs($customer)->put(route('admin.sms-templates.update', 'booking_completed'), ['body' => 'x {سالن}'])->assertForbidden();
    }
}
