<?php

namespace Tests\Feature\Console;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ⭐ تصمیم ۲۰۲۶-۰۹-۲۷: ردیف‌های قدیمی با مالکیت اشتباه با یک دستور یک‌باره اصلاح می‌شوند (migrationها فقط schema
 * هستند): user_id اعلان‌هایی که به متخصص فرستاده شده بودند (شناسه‌ی متخصص به‌جای کاربرِ متخصص)، و جوایز وفاداری
 * بدون سالن (قبل از ستون rewards.salon_id).
 */
class RepairLegacyTenantRowsTest extends TestCase
{
    use RefreshDatabase;

    private function legacySpecialistNotification(Specialist $specialist, ?int $wrongUserId): string
    {
        $id = (string) Str::uuid();
        DB::table('user_notifications')->insert([
            'id' => $id, 'type' => 'App\\Notifications\\Review\\NewReviewNotification', 'data' => '{}',
            'user_id' => $wrongUserId, 'notifiable_type' => Specialist::class, 'notifiable_id' => $specialist->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_it_reassigns_specialist_notifications_to_the_specialists_user_and_orphan_rewards_to_a_salon(): void
    {
        $unrelated = User::factory()->create();
        $specialist = Specialist::factory()->create();
        $wrong = $this->legacySpecialistNotification($specialist, $unrelated->id);
        $right = $this->legacySpecialistNotification($specialist, $specialist->user_id);
        DB::table('rewards')->insert(['title' => 'legacy', 'required_points' => 10, 'discount_type' => 'fixed', 'discount_amount' => 1000, 'salon_id' => null]);
        $salon = Salon::factory()->create(['slug' => 'repair-target']);

        $this->artisan('tenancy:repair-legacy-rows', ['--rewards-salon' => 'repair-target'])->assertSuccessful();

        $this->assertSame($specialist->user_id, UserNotification::find($wrong)->user_id);
        $this->assertSame($specialist->user_id, UserNotification::find($right)->user_id);
        $this->assertSame($salon->id, (int) DB::table('rewards')->where('title', 'legacy')->value('salon_id'));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $unrelated = User::factory()->create();
        $wrong = $this->legacySpecialistNotification(Specialist::factory()->create(), $unrelated->id);
        DB::table('rewards')->insert(['title' => 'legacy', 'required_points' => 10, 'discount_type' => 'fixed', 'discount_amount' => 1000, 'salon_id' => null]);

        $this->artisan('tenancy:repair-legacy-rows', ['--dry-run' => true, '--rewards-salon' => 'rasta'])->assertSuccessful();

        $this->assertSame($unrelated->id, UserNotification::find($wrong)->user_id);
        $this->assertNull(DB::table('rewards')->where('title', 'legacy')->value('salon_id'));
    }

    public function test_without_a_target_salon_orphan_rewards_are_only_reported(): void
    {
        DB::table('rewards')->insert(['title' => 'legacy', 'required_points' => 10, 'discount_type' => 'fixed', 'discount_amount' => 1000, 'salon_id' => null]);

        $this->artisan('tenancy:repair-legacy-rows')->expectsOutputToContain('1')->assertSuccessful();

        $this->assertNull(DB::table('rewards')->where('title', 'legacy')->value('salon_id'));
    }

    public function test_an_unknown_target_salon_fails_without_changes(): void
    {
        DB::table('rewards')->insert(['title' => 'legacy', 'required_points' => 10, 'discount_type' => 'fixed', 'discount_amount' => 1000, 'salon_id' => null]);

        $this->artisan('tenancy:repair-legacy-rows', ['--rewards-salon' => 'no-such-salon'])->assertFailed();

        $this->assertNull(DB::table('rewards')->where('title', 'legacy')->value('salon_id'));
        $this->assertNotNull(app(CurrentSalon::class));
    }
}
