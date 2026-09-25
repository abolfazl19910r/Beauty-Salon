<?php

namespace Tests\Feature\Loyalty;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۲۰۲۶-۰۹-۲۷: جوایز وفاداری مال هر سالن جداست. جدول rewards ستون salon_id نداشت: مدیر هر سالن جوایز همه‌ی
 * سالن‌ها رو می‌دید، ویرایش و حذف می‌کرد و برای مشتری فعال می‌کرد، و مشتری هر سالن جوایز همه‌ی سالن‌ها رو می‌دید و
 * با امتیاز همین سالن دریافت می‌کرد.
 */
class RewardSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salonA;

    private User $owner;

    private User $customer;

    private Reward $own;

    private Reward $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salonA = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salonA->id]);
        LoyaltyPoint::factory()->create(['user_id' => $this->customer->id, 'booking_id' => null, 'points' => 5000, 'type' => 'earned']);
        $this->own = Reward::factory()->create(['title' => 'ZZOWNREWARD', 'required_points' => 10, 'is_active' => true]);

        app(CurrentSalon::class)->set(Salon::factory()->create(['slug' => 'other-rewards']));
        $this->other = Reward::factory()->create(['title' => 'ZZOTHERREWARD', 'required_points' => 10, 'is_active' => true]);
        app(CurrentSalon::class)->set($this->salonA);
    }

    public function test_a_new_reward_belongs_to_the_salon_it_was_created_in(): void
    {
        $this->assertSame($this->salonA->id, $this->own->salon_id);
        $this->assertNotSame($this->salonA->id, $this->other->salon_id);
    }

    public function test_admin_sees_only_this_salons_rewards(): void
    {
        $this->actingAs($this->owner)->get(route('admin.loyalty.index'))
            ->assertOk()->assertSee('ZZOWNREWARD')->assertDontSee('ZZOTHERREWARD');
    }

    public function test_admin_cannot_view_change_delete_or_grant_another_salons_reward(): void
    {
        $this->actingAs($this->owner)->get(route('admin.loyalty.rewards.show', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->get(route('admin.loyalty.rewards.edit', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->put(route('admin.loyalty.rewards.update', $this->other), [
            'title' => 'HACKED', 'required_points' => 1, 'discount_type' => 'fixed', 'discount_amount' => 1000,
        ])->assertNotFound();
        $this->actingAs($this->owner)->delete(route('admin.loyalty.rewards.destroy', $this->other))->assertNotFound();
        $this->actingAs($this->owner)->post(route('admin.loyalty.rewards.redeem', $this->other), ['user_id' => $this->customer->id])
            ->assertNotFound();

        $fresh = Reward::withoutGlobalScopes()->find($this->other->id);
        $this->assertSame('ZZOTHERREWARD', $fresh->title);
        $this->assertSame(0, $fresh->used_count);
    }

    public function test_customer_sees_and_redeems_only_this_salons_rewards(): void
    {
        $this->actingAs($this->customer)->get(route('loyalty.index'))
            ->assertOk()->assertSee('ZZOWNREWARD')->assertDontSee('ZZOTHERREWARD');

        $this->actingAs($this->customer)->post(route('loyalty.redeem', $this->other))->assertNotFound();
        $this->assertSame(0, Reward::withoutGlobalScopes()->find($this->other->id)->used_count);

        $this->actingAs($this->customer)->post(route('loyalty.redeem', $this->own))->assertRedirect(route('loyalty.index'));
        $this->assertSame(1, $this->own->fresh()->used_count);
    }
}
