<?php

namespace Tests\Feature\Admin;

use App\Models\Leave;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، ممیزی جداسازی سالن‌ها): فهرست مرخصی‌های مدیریت و JSON «در انتظار» مرخصی متخصص‌های
 * همه‌ی سالن‌ها رو نشون می‌داد (نام متخصص، تاریخ، دلیل). جدول leaves ستون salon_id نداره؛ تغییر وضعیت از قبل چک
 * مالکیت داشت، ولی فهرست‌ها نه.
 */
class LeaveListSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_admin' => true]);

        Leave::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'status' => 'pending', 'reason' => 'OWN-LEAVE-REASON']);

        app(CurrentSalon::class)->set(Salon::factory()->create(['slug' => 'other-leaves']));
        Leave::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'status' => 'pending', 'reason' => 'OTHER-LEAVE-REASON']);
        app(CurrentSalon::class)->clear();
    }

    public function test_leave_list_shows_only_this_salons_leaves(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.leaves.index'))->assertOk();

        $response->assertSee('OWN-LEAVE-REASON')->assertDontSee('OTHER-LEAVE-REASON');
        $this->assertSame(1, $response->viewData('leaves')->total());
    }

    public function test_pending_leaves_json_shows_only_this_salons_leaves(): void
    {
        $response = $this->actingAs($this->owner)->getJson(route('admin.leaves.pending'))->assertOk();

        $this->assertSame(['OWN-LEAVE-REASON'], collect($response->json())->pluck('reason')->all());
    }
}
