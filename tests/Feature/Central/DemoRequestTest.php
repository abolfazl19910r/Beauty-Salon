<?php

namespace Tests\Feature\Central;

use App\Models\DemoRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Central\DemoRequestReceivedNotification;
use App\Services\SMSService;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * فرم «درخواست دموی رایگان» لندینگ تازه (۲۰۲۶-۱۰-۱۴) و فهرست آن در پنل سوپرادمین.
 */
class DemoRequestTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $overrides = []): array
    {
        return array_merge(['name' => 'سارا محمدی', 'salon' => 'سالن رزگلد', 'phone' => '۰۹۱۲ ۳۴۵ ۶۷۸۹', 'type' => 'nails'], $overrides);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    public function test_the_page_script_submission_is_stored_with_a_normalised_phone(): void
    {
        $this->postJson(route('demo-requests.store'), $this->valid())
            ->assertCreated()->assertJsonPath('success', true);

        $demo = DemoRequest::sole();
        $this->assertSame('09123456789', $demo->phone);
        $this->assertSame('سالن رزگلد', $demo->salon_name);
        $this->assertSame('nails', $demo->salon_type);
        $this->assertNull($demo->handled_at);
    }

    public function test_without_javascript_the_form_returns_to_the_page_in_its_sent_state(): void
    {
        $this->post(route('demo-requests.store'), $this->valid(['phone' => '+98 912 345 6789']))
            ->assertRedirect(route('central.home').'#start')
            ->assertSessionHas('demo_requested');

        $this->assertSame('09123456789', DemoRequest::sole()->phone);
        $this->withSession(['demo_requested' => true])->get(route('central.home'))
            ->assertSee('class="form sent"', false);
    }

    public function test_invalid_input_is_explained_per_field(): void
    {
        $this->postJson(route('demo-requests.store'), $this->valid(['name' => ' ', 'phone' => '12345', 'type' => 'spa']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'type'])
            ->assertJsonPath('errors.phone.0', 'شماره موبایل معتبر نیست (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).');

        $this->from(route('central.home'))->post(route('demo-requests.store'), $this->valid(['phone' => '0912']))
            ->assertRedirect(route('central.home'))
            ->assertSessionHasErrors('phone');
        $this->assertSame(0, DemoRequest::count());
    }

    public function test_the_honeypot_answers_success_but_stores_nothing(): void
    {
        $this->postJson(route('demo-requests.store'), $this->valid(['website' => 'http://spam.example']))
            ->assertCreated();

        $this->assertSame(0, DemoRequest::count());
    }

    public function test_the_same_phone_within_a_day_refreshes_one_row(): void
    {
        $this->postJson(route('demo-requests.store'), $this->valid())->assertCreated();
        $this->postJson(route('demo-requests.store'), $this->valid(['salon' => 'سالن تازه', 'phone' => '09123456789']))->assertCreated();
        $this->assertSame(1, DemoRequest::count());
        $this->assertSame('سالن تازه', DemoRequest::sole()->salon_name);

        $this->travel(25)->hours();
        $this->postJson(route('demo-requests.store'), $this->valid())->assertCreated();
        $this->assertSame(2, DemoRequest::count());
    }

    public function test_one_address_is_limited_to_five_requests_an_hour(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson(route('demo-requests.store'), $this->valid(['phone' => '0912000000'.$i]))->assertCreated();
        }
        $this->postJson(route('demo-requests.store'), $this->valid(['phone' => '09120000009']))->assertStatus(429);
        $this->assertSame(5, DemoRequest::count());
    }

    public function test_super_admin_lists_open_requests_and_marks_them_handled(): void
    {
        $admin = $this->superAdmin();
        $open = DemoRequest::factory()->create(['name' => 'لیلا کریمی']);
        DemoRequest::factory()->create(['name' => 'پیگیری‌شده قبلی', 'handled_at' => now()->subDay()]);

        $this->actingAs($admin)->get(route('superadmin.demo-requests.index'))
            ->assertOk()
            ->assertSee('لیلا کریمی')
            ->assertDontSee('پیگیری‌شده قبلی')
            ->assertSee('data-demo-requests-badge', false);

        $this->actingAs($admin)->patch(route('superadmin.demo-requests.update', $open), ['handled' => 1, 'admin_note' => 'فردا ساعت ۱۰ دمو'])
            ->assertRedirect();
        $open->refresh();
        $this->assertNotNull($open->handled_at);
        $this->assertSame($admin->id, $open->handled_by);
        $this->assertSame('فردا ساعت ۱۰ دمو', $open->admin_note);

        $this->actingAs($admin)->get(route('superadmin.demo-requests.index'))->assertDontSee('data-demo-requests-badge', false);
        $this->actingAs($admin)->get(route('superadmin.demo-requests.index', ['status' => 'handled']))->assertSee('لیلا کریمی');

        $this->actingAs($admin)->patch(route('superadmin.demo-requests.update', $open), ['handled' => 0]);
        $this->assertNull($open->fresh()->handled_at);
        $this->assertNull($open->fresh()->handled_by);
    }

    public function test_only_the_super_admin_sees_demo_requests(): void
    {
        $demo = DemoRequest::factory()->create();
        $salonAdmin = User::factory()->create(['is_admin' => true]);

        $this->get(route('superadmin.demo-requests.index'))->assertRedirect();
        $this->actingAs($salonAdmin)->get(route('superadmin.demo-requests.index'))->assertForbidden();
        $this->actingAs($salonAdmin)->patch(route('superadmin.demo-requests.update', $demo), ['handled' => 1])->assertForbidden();
        $this->assertNull($demo->fresh()->handled_at);
    }

    public function test_a_new_request_texts_every_super_admin_and_emails_the_configured_address(): void
    {
        Notification::fake();
        $first = $this->superAdmin();
        $second = $this->superAdmin();
        PlatformSettings::save(['demo_notify_email' => 'sales@mahru.ir']);

        $this->postJson(route('demo-requests.store'), $this->valid())->assertCreated();

        foreach ([$first, $second] as $admin) {
            Notification::assertSentTo($admin, DemoRequestReceivedNotification::class, fn ($n, array $channels) => $channels === ['sms']);
        }
        Notification::assertSentOnDemand(DemoRequestReceivedNotification::class,
            fn ($n, array $channels, object $notifiable) => $channels === ['mail'] && $notifiable->routes['mail'] === 'sales@mahru.ir');
    }

    public function test_repeats_and_bots_do_not_notify_and_without_an_address_no_email_goes_out(): void
    {
        Notification::fake();
        $admin = $this->superAdmin();

        $this->postJson(route('demo-requests.store'), $this->valid())->assertCreated();
        $this->postJson(route('demo-requests.store'), $this->valid())->assertCreated();
        $this->postJson(route('demo-requests.store'), $this->valid(['phone' => '09350000000', 'website' => 'x']))->assertCreated();

        Notification::assertSentToTimes($admin, DemoRequestReceivedNotification::class, 1);
        Notification::assertNotSentTo(new AnonymousNotifiable, DemoRequestReceivedNotification::class);
        Notification::assertSentOnDemandTimes(DemoRequestReceivedNotification::class, 0);
    }

    public function test_the_sms_is_a_platform_cost_and_fits_one_part(): void
    {
        $admin = $this->superAdmin();
        $admin->forceFill(['phone' => '09121234567'])->save();
        $demo = DemoRequest::factory()->create(['name' => 'سارا محمدی', 'salon_name' => 'سالن رزگلد', 'phone' => '09123456789']);

        $sms = $this->mock(SMSService::class);
        // بدون شناسه‌ی سالن: هزینه‌ی پلتفرم، نه سهمیه‌ی سالن
        $sms->shouldReceive('send')->once()->withArgs(fn (...$args) => $args[0] === '09121234567' && count($args) === 2)->andReturnTrue();

        (new DemoRequestReceivedNotification($demo))->toSms($admin);

        $text = DemoRequestReceivedNotification::smsText($demo);
        $this->assertStringContainsString('09123456789', $text);
        $this->assertLessThanOrEqual(70, mb_strlen($text), $text);
    }
}
