<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * داشبورد سوپرادمین همه‌ی سالن‌ها و مدیرانشان را بار می‌کرد تا چهار عدد بشمارد و ۵ سالن آخر را نشان دهد (۱۰۰۰ سالن: ~۲۰۰ms و
 * رشد خطی؛ اندازه‌گیری ۲۰۲۶-۰۹-۳۰). شمارش‌ها در SQL؛ فقط ۵ سالن آخر ساخته می‌شوند.
 */
class DashboardLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_come_from_sql_and_only_the_five_recent_salons_are_loaded(): void
    {
        $this->freezeTime();
        $super = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        $base = Salon::query()->count(); // سالن پیش‌فرض تست‌ها
        Salon::factory()->count(6)->create(['subscription_ends_at' => now()->addMonths(2), 'is_suspended' => false]);
        Salon::factory()->create(['subscription_ends_at' => now()->addDays(3), 'is_suspended' => false]);   // رو به انقضا
        Salon::factory()->create(['subscription_ends_at' => now()->addDays(3), 'is_suspended' => true]);    // معلق، نه رو به انقضا
        Salon::factory()->create(['subscription_ends_at' => now()->subDay(), 'is_suspended' => false]);     // منقضی
        Salon::factory()->create(['subscription_ends_at' => now()->subDay(), 'is_suspended' => true]);      // منقضی و معلق
        $newest = Salon::factory()->create(['subscription_ends_at' => now()->addYear(), 'created_at' => now()->addMinute()]);

        $expected = Salon::query()->get();
        $loaded = 0;
        Event::listen('eloquent.retrieved: '.Salon::class, function () use (&$loaded) {
            $loaded++;
        });

        $response = $this->actingAs($super)->get(route('superadmin.dashboard'))->assertOk();

        $stats = $response->viewData('stats');
        // بسته‌ی ۱ ظاهری: دسته‌های جدا از هم (SalonStatus) — همان جواب PHP برای تک‌تک سالن‌ها
        foreach (\App\Support\SuperAdmin\SalonStatus::keys() as $key) {
            $this->assertSame($expected->filter(fn ($s) => \App\Support\SuperAdmin\SalonStatus::of($s) === $key)->count(), $stats[$key], $key);
        }
        $this->assertSame(1, $stats['expiring_soon']);
        $this->assertSame(1, $stats['expired']);
        $this->assertSame(2, $stats['suspended']);
        $this->assertSame($expected->count(), $stats['total']);
        $this->assertSame($base + 11, $expected->count());

        $recent = $response->viewData('recentSalons');
        $this->assertCount(5, $recent);
        $this->assertSame($newest->id, $recent->first()->id);
        $this->assertNotNull($recent->first()->specialists_count);
        $this->assertLessThanOrEqual(5, $loaded, "{$loaded} salons were loaded to render the dashboard");
    }
}
