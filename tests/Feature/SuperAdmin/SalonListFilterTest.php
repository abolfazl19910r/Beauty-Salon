<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Invoice;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

/**
 * ⭐ جستجو و فیلتر لیست سالن‌های سوپرادمین (۲۰۲۶-۰۹-۲۴).
 */
class SalonListFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach($role);
        app(CurrentSalon::class)->clear();

        $active = Salon::factory()->create(['name' => 'سالن فعال', 'slug' => 'active-one', 'subscription_type' => '6m',
            'subscription_started_at' => now()->subMonths(2), 'subscription_ends_at' => now()->addMonths(4), 'max_specialists_count' => 2]);
        Specialist::factory()->count(2)->create(['salon_id' => $active->id]);
        Invoice::factory()->paid()->create(['salon_id' => $active->id]);
        $owner = User::factory()->create(['name' => 'مریم مالک', 'phone' => '09125551234', 'is_admin' => true]);
        $active->admins()->attach($owner->id, ['role' => 'owner']);

        Salon::factory()->create(['name' => 'سالن آزمایشی', 'slug' => 'trial-one', 'subscription_type' => '1m',
            'subscription_ends_at' => now()->addDays(10), 'trial_ends_at' => now()->addDays(10)]);
        Salon::factory()->create(['name' => 'سالن رو به انقضا', 'slug' => 'soon-one', 'subscription_type' => '1m', 'subscription_ends_at' => now()->addDays(3)]);
        Salon::factory()->create(['name' => 'سالن منقضی', 'slug' => 'expired-one', 'subscription_type' => '1m', 'subscription_ends_at' => now()->subDay()]);
        Salon::factory()->create(['name' => 'سالن تعلیقی', 'slug' => 'suspended-one', 'subscription_type' => '1m', 'is_suspended' => true, 'subscription_ends_at' => now()->addMonth()]);
    }

    private function slugs(array $query): array
    {
        return collect($this->actingAs($this->superAdmin)->get(route('superadmin.salons.index', $query))
            ->assertOk()->viewData('salons')->items())->pluck('slug')
            // سالن پیش‌فرض 'rasta' که TestCase::setUp می‌سازه، جزو داده‌ی این تست نیست
            ->reject(fn ($slug) => $slug === 'rasta')->sort()->values()->all();
    }

    public function test_search_by_name_slug_and_owner(): void
    {
        $this->assertSame(['active-one'], $this->slugs(['q' => 'فعال']));
        $this->assertSame(['trial-one'], $this->slugs(['q' => 'trial-o']));
        $this->assertSame(['active-one'], $this->slugs(['q' => 'مریم']));
        $this->assertSame(['active-one'], $this->slugs(['q' => '09125551234']));
    }

    public function test_status_filters(): void
    {
        $this->assertSame(['suspended-one'], $this->slugs(['status' => 'suspended']));
        $this->assertSame(['expired-one'], $this->slugs(['status' => 'expired']));
        $this->assertSame(['trial-one'], $this->slugs(['status' => 'trial']));
        $this->assertSame(['soon-one'], $this->slugs(['status' => 'expiring_soon']));
        $this->assertNotContains('expired-one', $this->slugs(['status' => 'active']));
        $this->assertNotContains('suspended-one', $this->slugs(['status' => 'active']));
    }

    public function test_plan_and_specialist_quota_filters(): void
    {
        $this->assertSame(['active-one'], $this->slugs(['subscription_type' => '6m']));
        $this->assertSame(['active-one'], $this->slugs(['quota' => 'full']));
        $this->assertNotContains('active-one', $this->slugs(['quota' => 'available']));
    }

    public function test_jalali_date_ranges_on_start_and_end_columns(): void
    {
        $from = Jalalian::fromCarbon(now()->addMonths(3))->format('Y/m/d');
        $this->assertSame(['active-one'], $this->slugs(['ends_from' => $from]));

        $to = Jalalian::fromCarbon(now()->subMonth())->format('Y/m/d');
        $this->assertSame(['active-one'], $this->slugs(['started_to' => $to]));
    }

    public function test_sort_by_nearest_expiry(): void
    {
        $this->assertSame('expired-one', $this->actingAs($this->superAdmin)
            ->get(route('superadmin.salons.index', ['sort' => 'ends_asc']))->viewData('salons')->items()[0]->slug);
    }

    public function test_trial_badge_and_invalid_filters(): void
    {
        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.index'))->assertSee('آزمایشی (');
        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.index', ['status' => 'bogus']))->assertSessionHasErrors('status');
    }
}
