<?php

namespace Tests\Feature\Admin;

use App\Models\LoyaltyPoint;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، ممیزی جداسازی سالن‌ها): مدیریت امتیاز وفاداری کاربرهای همه‌ی سالن‌ها رو جست‌وجو
 * می‌کرد، با ?user_id امتیاز و تاریخچه‌ی هر کاربری رو نشون می‌داد و به هر کاربری امتیاز اضافه/کم می‌کرد؛ شمارنده‌های
 * داشبورد وفاداری (کل امتیاز، تعداد کاربر) هم برای کل پلتفرم بود. loyalty_points ستون salon_id نداره؛ امتیاز همیشه
 * مال یک مشتریه و مشتری salon_id داره.
 */
class LoyaltyPointsSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $ownCustomer;

    private User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();
        $salonA = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->ownCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonA->id, 'name' => 'ZZOWNCUSTOMER']);
        LoyaltyPoint::factory()->create(['user_id' => $this->ownCustomer->id, 'booking_id' => null, 'points' => 100, 'type' => 'earned']);

        $salonB = Salon::factory()->create(['slug' => 'other-loyalty']);
        app(CurrentSalon::class)->set($salonB);
        $this->otherCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonB->id, 'name' => 'ZZOTHERCUSTOMER']);
        LoyaltyPoint::factory()->create(['user_id' => $this->otherCustomer->id, 'booking_id' => null, 'points' => 900, 'type' => 'earned', 'description' => 'ZZOTHERHISTORY']);
        app(CurrentSalon::class)->set($salonA);
    }

    public function test_loyalty_dashboard_counts_only_this_salons_points(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.loyalty.index'))->assertOk();

        $this->assertSame(100, $response->viewData('totalActivePoints'));
        $this->assertSame(1, $response->viewData('totalPointUsers'));
    }

    public function test_points_search_and_user_view_stay_inside_the_salon(): void
    {
        $search = $this->actingAs($this->owner)->get(route('admin.loyalty.points.index', ['search' => 'ZZ']))->assertOk();
        $this->assertSame([$this->ownCustomer->id], $search->viewData('users')->pluck('id')->all());

        $foreign = $this->actingAs($this->owner)->get(route('admin.loyalty.points.index', ['user_id' => $this->otherCustomer->id]))
            ->assertOk()->assertDontSee('ZZOTHERHISTORY');
        $this->assertNull($foreign->viewData('selectedUser'));
    }

    public function test_points_cannot_be_added_to_or_deducted_from_another_salons_customer(): void
    {
        $this->actingAs($this->owner)->post(route('admin.loyalty.points.add', $this->otherCustomer), ['points' => 50, 'description' => 'x'])
            ->assertNotFound();
        $this->actingAs($this->owner)->post(route('admin.loyalty.points.deduct', $this->otherCustomer), ['points' => 50, 'description' => 'x'])
            ->assertNotFound();

        $this->assertSame(900, (int) LoyaltyPoint::where('user_id', $this->otherCustomer->id)->sum('points'));
    }

    public function test_own_customer_points_still_work(): void
    {
        $this->actingAs($this->owner)->get(route('admin.loyalty.points.index', ['user_id' => $this->ownCustomer->id]))->assertOk();
        $this->actingAs($this->owner)->post(route('admin.loyalty.points.add', $this->ownCustomer), ['points' => 50, 'description' => 'x'])
            ->assertRedirect();

        $this->assertSame(150, (int) LoyaltyPoint::where('user_id', $this->ownCustomer->id)->sum('points'));
    }
}
