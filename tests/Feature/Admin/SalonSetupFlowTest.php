<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Admin\Dashboard\SalonSetupChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۷ (آزمون راه‌اندازی سالن آزمایشی روی production، ۲۰۲۶-۱۱-۲۶): مدیر تازه بی راهنمای بیرونی سالن را راه بیندازد.
 */
class SalonSetupFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_services_without_a_category_can_be_given_to_an_expert(): void
    {
        $category = Category::factory()->create(['name' => 'پوست']);
        $grouped = BeautyService::factory()->create(['name' => 'پاکسازی', 'category_id' => $category->id]);
        $loose = BeautyService::factory()->create(['name' => 'خدمت بی‌دسته', 'category_id' => null]);

        $html = $this->actingAs($this->admin)->get('/admin/specialists/create')->assertOk()->getContent();
        $this->assertStringContainsString('بدون دسته‌بندی', $html);
        $this->assertStringContainsString('name="services[]" value="'.$loose->id.'"', $html);
        $this->assertStringContainsString('name="services[]" value="'.$grouped->id.'"', $html);

        $specialist = Specialist::factory()->create();
        $this->actingAs($this->admin)->get(route('admin.specialists.edit', $specialist))->assertOk()
            ->assertSee('name="services[]" value="'.$loose->id.'"', false);
    }

    public function test_the_weekly_schedule_starts_on_saturday_and_shows_breaks(): void
    {
        $specialist = Specialist::factory()->create();
        $specialist->schedules()->delete();
        SpecialistSchedule::create(['specialist_id' => $specialist->id, 'day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '18:00',
            'break_start' => '13:00', 'break_end' => '14:00', 'is_active' => true]);

        $edit = $this->actingAs($this->admin)->get(route('admin.specialists.schedules.edit', $specialist))->assertOk()->getContent();
        $this->assertLessThan(strpos($edit, 'name="schedules[0][day_of_week]"'), strpos($edit, 'name="schedules[6][day_of_week]"'), 'شنبه پیش از یکشنبه');

        $show = $this->actingAs($this->admin)->get(route('admin.specialists.show', $specialist))->assertOk()->getContent();
        $this->assertLessThan(strpos($show, '>یکشنبه<'), strpos($show, '>شنبه<'));
        $this->assertStringContainsString('استراحت ۱۳:۰۰ تا ۱۴:۰۰', $show);
    }

    public function test_every_setup_step_says_how(): void
    {
        $setup = SalonSetupChecklist::for(\App\Models\Salon::where('slug', 'rasta')->firstOrFail());

        $this->assertSame(['info', 'services', 'experts', 'gateway'], array_column($setup['steps'], 'key'));
        foreach ($setup['steps'] as $step) {
            $this->assertNotSame('', $step['how'], $step['key']);
        }
    }

    public function test_the_gateway_page_shows_both_ways_and_no_fee_fields(): void
    {
        config(['payments.platform_gateway.enabled' => false]);

        $html = $this->actingAs($this->admin)->get(route('admin.payment-gateways.index'))->assertOk()->getContent();
        $this->assertStringContainsString('data-platform-gateway-soon', $html);
        $this->assertStringContainsString('درگاه خود سالن', $html);
        $this->assertStringNotContainsString('name="fee_percent"', $html);
        $this->assertStringNotContainsString('name="fee_fixed_toman"', $html);
        $this->assertStringNotContainsString('name="label"', $html);
        $this->assertStringNotContainsString('کارمزدی که اینجا وارد می‌کنید', $html);
    }
}
