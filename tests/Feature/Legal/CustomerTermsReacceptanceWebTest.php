<?php

namespace Tests\Feature\Legal;

use App\Models\LegalAcceptance;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * پذیرش دوباره‌ی قوانین — مشتری وب (۲۰۲۶-۱۰-۲۸). پیش‌تر تغییر نسخه‌ی قوانین هیچ اثری نداشت: مشتری قدیمی بی پذیرش نسخه‌ی تازه نوبت
 * می‌گرفت. حالا تغییر «مهم» بعد از ورود صفحه‌ی «قوانین به‌روز شد» را نشان می‌دهد و نوبت/دوره‌ی تازه تا پذیرش بسته است؛ خروج، حذف
 * حساب و نوبت‌های فعلی باز می‌مانند.
 */
class CustomerTermsReacceptanceWebTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $salon = app(CurrentSalon::class)->get();
        $this->customer = User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer', 'phone' => '09121117777']);
        foreach (['terms', 'privacy'] as $document) {
            LegalAcceptance::create([
                'user_id' => $this->customer->id, 'salon_id' => $salon->id, 'document' => $document, 'version' => '1405-08-03',
                'source' => 'web', 'accepted_at' => now()->subMonth(),
            ]);
        }
    }

    private function majorTermsChange(): void
    {
        config(['legal.versions.terms' => '1405-09-01', 'legal.changes.terms.1405-09-01' => ['major' => true, 'summary' => 'مهلت لغو نوبت ۲۴ ساعت شد']]);
    }

    public function test_without_a_major_change_nothing_changes(): void
    {
        config(['legal.versions.terms' => '1405-09-01', 'legal.changes.terms.1405-09-01' => ['major' => false, 'summary' => 'اصلاح نگارش']]);

        $this->actingAs($this->customer)->get(route('bookings.create'))->assertOk()->assertDontSee('data-terms-update-banner', false);
    }

    public function test_a_major_change_blocks_new_bookings_and_courses_until_accepted(): void
    {
        $this->majorTermsChange();
        $this->actingAs($this->customer);

        $this->get(route('bookings.create'))->assertRedirect(route('terms-update.show'));
        $this->post(route('bookings.store'), [])->assertRedirect(route('terms-update.show'));
        $this->post(route('bookings.confirm'), [])->assertRedirect(route('terms-update.show'));
        $this->postJson(route('bookings.store'), [])->assertStatus(409)->assertJsonPath('code', 'terms_update_required');
        $this->assertContains('legal.terms', app('router')->getRoutes()->getByName('courses.purchase')->gatherMiddleware(),
            'خرید دوره (پشت امکان پلن) هم بسته است');
        $this->setUpCourseWorld();
        $this->courseCustomer = $this->customer;
        $course = $this->paidCourse();
        $this->get(route('my-courses.book', $course))->assertRedirect(route('terms-update.show'));
        $this->get(route('my-courses.show', $course))->assertOk();
    }

    public function test_the_rest_of_the_account_stays_open_with_a_banner(): void
    {
        $this->majorTermsChange();
        $this->actingAs($this->customer);

        $this->get(route('bookings.index'))->assertOk()->assertSee('data-terms-update-banner', false);
        $this->get(route('profile.edit'))->assertOk()->assertSee('id="delete-account"', false);
        $this->get(route('my-courses.index'))->assertOk();
        $this->get(route('complaints.index'))->assertOk();
    }

    public function test_the_page_shows_the_summary_and_the_ways_out(): void
    {
        $this->majorTermsChange();

        $this->actingAs($this->customer)->get(route('terms-update.show'))->assertOk()
            ->assertSee('قوانین به‌روز شد')->assertSee('مهلت لغو نوبت ۲۴ ساعت شد')->assertSee('data-pending-document="terms"', false)
            ->assertDontSee('data-pending-document="privacy"', false)
            ->assertSee('name="accept_terms"', false)->assertSee('data-terms-decline', false)
            ->assertSee(route('logout'), false)->assertSee('/privacy', false)->assertDontSee('data-terms-update-banner', false);
    }

    public function test_accepting_records_the_new_version_and_returns_to_the_booking(): void
    {
        $this->majorTermsChange();
        $this->actingAs($this->customer)->get(route('bookings.create'));

        $this->post(route('terms-update.accept'), [])->assertSessionHasErrors('accept_terms');

        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.10', 'HTTP_USER_AGENT' => 'Firefox'])
            ->post(route('terms-update.accept'), ['accept_terms' => '1'])->assertRedirect(route('bookings.create'));

        $row = LegalAcceptance::where('user_id', $this->customer->id)->where('version', '1405-09-01')->sole();
        $this->assertSame('terms', $row->document);
        $this->assertSame('web', $row->source);
        $this->assertSame('5.6.7.10', $row->ip_address);
        $this->assertSame('Firefox', $row->user_agent);
        $this->assertSame($this->customer->salon_id, $row->salon_id);
        $this->assertSame(0, LegalAcceptance::where('user_id', $this->customer->id)->where('document', 'privacy')->where('version', '!=', '1405-08-03')->count(),
            'فقط سند در انتظار دوباره ثبت می‌شود');

        $this->get(route('bookings.create'))->assertOk();
        $this->get(route('terms-update.show'))->assertRedirect(route('home'));
    }

    public function test_login_goes_to_the_terms_page_first_then_to_the_intended_page(): void
    {
        $this->majorTermsChange();
        $salon = app(CurrentSalon::class)->get();
        $this->customer->update(['login_verification_code' => '123456', 'login_verification_code_expire_at' => now()->addMinutes(2)]);
        $target = url('/s/'.$salon->slug.'/bookings');

        $this->withSession(['customer_login_user_id' => $this->customer->id, 'url.intended' => $target])
            ->post(route('salon.login.verify', ['salon_slug' => $salon->slug]), ['code' => '123456'])
            ->assertRedirect(route('terms-update.show'));

        $this->post(route('terms-update.accept'), ['accept_terms' => '1'])->assertRedirect($target);
    }

    public function test_declining_customer_can_still_log_out_and_delete_the_account(): void
    {
        $this->majorTermsChange();

        $this->actingAs($this->customer)->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }
}
