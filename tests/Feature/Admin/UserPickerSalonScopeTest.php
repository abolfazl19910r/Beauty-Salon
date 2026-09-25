<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، ممیزی جداسازی سالن‌ها): جست‌وجوی مدیریت (صفحه، API و پیشنهادها)، فهرست کاربرِ فرم
 * ویرایش نوبت و فرم ساخت کد تخفیف، کاربرهای همه‌ی سالن‌ها رو با شماره تلفن برمی‌گردوند؛ ثبت/ویرایش نوبت و کد تخفیف
 * هم هر user_id موجود در پلتفرم رو می‌پذیرفت (نوبت یا کد تخفیف شخصی برای مشتری سالن دیگه).
 */
class UserPickerSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $ownCustomer;

    private User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        $salonA = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->ownCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id(), 'name' => 'ZZOWNCUSTOMER', 'phone' => '09120000001']);

        $salonB = Salon::factory()->create(['slug' => 'other-pickers']);
        app(CurrentSalon::class)->set($salonB);
        $this->otherCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonB->id, 'name' => 'ZZOTHERCUSTOMER', 'phone' => '09120000002']);
        User::factory()->create(['is_admin' => true, 'name' => 'ZZOTHEROWNER']);
        app(CurrentSalon::class)->set($salonA);
    }

    public function test_admin_search_page_api_and_suggestions_do_not_return_other_salons_users(): void
    {
        $this->actingAs($this->owner)->get(route('admin.search.index', ['q' => 'ZZ']))
            ->assertOk()->assertSee('ZZOWNCUSTOMER')->assertDontSee('ZZOTHER');

        $api = $this->actingAs($this->owner)->getJson(route('admin.search.api', ['q' => 'ZZ']))->assertOk();
        $this->assertStringContainsString('ZZOWNCUSTOMER', $api->getContent());
        $this->assertStringNotContainsString('ZZOTHER', $api->getContent());

        $byPhone = $this->actingAs($this->owner)->getJson(route('admin.search.api', ['q' => '0912000000']))->assertOk();
        $this->assertStringNotContainsString('ZZOTHER', $byPhone->getContent());
    }

    public function test_booking_edit_and_discount_code_forms_offer_only_this_salons_customers(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->ownCustomer->id]);

        $edit = $this->actingAs($this->owner)->get(route('admin.bookings.edit', $booking))->assertOk();
        $this->assertContains($this->ownCustomer->id, $edit->viewData('users')->pluck('id'));
        $this->assertNotContains($this->otherCustomer->id, $edit->viewData('users')->pluck('id'));

        $create = $this->actingAs($this->owner)->get(route('admin.discount-codes.create'))->assertOk();
        $this->assertContains($this->ownCustomer->id, $create->viewData('users')->pluck('id'));
        $this->assertNotContains($this->otherCustomer->id, $create->viewData('users')->pluck('id'));
    }

    public function test_a_booking_cannot_be_created_or_moved_to_another_salons_customer(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create();
        $payload = [
            'user_id' => $this->otherCustomer->id, 'service_id' => $service->id, 'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'source' => 'phone',
        ];

        $this->actingAs($this->owner)->post(route('admin.bookings.store'), $payload)->assertSessionHasErrors('user_id');
        $this->assertSame(0, Booking::withoutGlobalScopes()->where('user_id', $this->otherCustomer->id)->count());

        $booking = Booking::factory()->create(['user_id' => $this->ownCustomer->id, 'status' => 'pending']);
        $this->actingAs($this->owner)->put(route('admin.bookings.update', $booking), array_merge($payload, ['status' => 'pending']))
            ->assertSessionHasErrors('user_id');
        $this->assertSame($this->ownCustomer->id, $booking->fresh()->user_id);
    }

    public function test_a_personal_discount_code_cannot_be_issued_to_another_salons_customer(): void
    {
        $this->actingAs($this->owner)->post(route('admin.discount-codes.store'), [
            'code' => 'FOREIGN1', 'type' => 'fixed', 'amount' => 1000, 'max_uses' => 5, 'user_id' => $this->otherCustomer->id,
        ])->assertSessionHasErrors('user_id');

        $this->actingAs($this->owner)->post(route('admin.discount-codes.store'), [
            'code' => 'OWNCODE1', 'type' => 'fixed', 'amount' => 1000, 'max_uses' => 5, 'user_id' => $this->ownCustomer->id,
        ])->assertSessionHasNoErrors();
    }
}
