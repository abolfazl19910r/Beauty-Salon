<?php

namespace Tests\Feature\Specialist;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * پرسش ابوالفضل (۲۰۲۶-۱۱-۱۷): مالک سالن می‌تواند خودش را متخصص کند و ساعت کاری بگذارد تا مشتری از خودش هم نوبت بگیرد؟
 * «افزودن متخصص» با شماره‌ی خود مالک ← همان حساب کادر مالک به متخصص وصل می‌شود (AdminSpecialistService::matchAndPromoteUser).
 */
class OwnerAsSpecialistTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create([
            'is_admin' => true, 'user_type' => 'staff', 'salon_id' => $salon->id, 'phone' => '09121112233', 'name' => 'مریم مالک',
        ]);
        $salon->admins()->syncWithoutDetaching([$this->owner->id => ['role' => 'owner']]);
        $this->service = BeautyService::factory()->create();
    }

    private function addSelf(): Specialist
    {
        $this->actingAs($this->owner)->post(route('admin.specialists.store'), [
            'name' => 'مریم مالک', 'phone' => '09121112233', 'services' => [$this->service->id],
        ])->assertSessionHasNoErrors()->assertRedirect();

        return app(CurrentSalon::class)->allSalons(fn () => Specialist::query()->where('phone', '09121112233')->firstOrFail());
    }

    public function test_owner_can_add_themself_and_it_links_to_the_same_account(): void
    {
        $specialist = $this->addSelf();

        $this->assertSame($this->owner->id, $specialist->user_id, 'حساب جدا ساخته نمی‌شود؛ همان حساب مالک');
        $this->assertSame(1, User::query()->where('phone', '09121112233')->count());
        $this->assertTrue((bool) $this->owner->fresh()->is_admin, 'مالک بودنش دست نمی‌خورد');
        $this->assertTrue(app(CurrentSalon::class)->get()->isOwner($this->owner->fresh()));
    }

    public function test_owner_still_lands_in_the_admin_panel_and_can_also_open_the_specialist_panel(): void
    {
        $this->addSelf();
        $owner = $this->owner->fresh();

        $this->assertSame('/admin/dashboard', $owner->homePath(), 'ورود ← همچنان پنل مدیر');
        $this->actingAs($owner)->get('/admin/dashboard')->assertOk();
        $this->actingAs($owner)->get('/my-dashboard')->assertOk();
        $this->actingAs($owner)->get(route('specialist.schedule'))->assertOk();
    }

    public function test_customers_see_the_owner_as_a_bookable_specialist_with_slots(): void
    {
        $specialist = $this->addSelf();
        $day = now()->addDays(3);
        \App\Models\SpecialistSchedule::factory()->create([
            'specialist_id' => $specialist->id, 'day_of_week' => $day->dayOfWeek, 'is_active' => true,
            'start_time' => '09:00', 'end_time' => '17:00',
        ]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);

        $list = $this->actingAs($customer)->getJson(route('bookings.service-specialists', ['service' => $this->service->id]))->assertOk();
        $this->assertContains($specialist->id, collect($list->json('specialists') ?? $list->json())->pluck('id')->all());

        $slots = $this->actingAs($customer)->getJson(route('bookings.available-slots', ['specialist' => $specialist->id, 'date' => $day->format('Y-m-d')]));
        $slots->assertOk();
        $this->assertNotEmpty($slots->json('slots'));
    }
}
