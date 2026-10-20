<?php

namespace Tests\Feature\Central;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * بسته‌ی «ح»، ۱۲ (۲۰۲۶-۱۰-۲۰): «سالن‌های من» در دامنه‌ی اصلی — شماره ← کد ← فهرست سالن‌ها ← ورود همان سالن با شماره‌ی پرشده.
 */
class MySalonsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '09127778899';

    private Salon $rasta;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SendLoginVerificationCodeJob::class]);
        $this->rasta = app(CurrentSalon::class)->get();
        app(CurrentSalon::class)->clear();
    }

    private function customerIn(Salon $salon, string $phone = self::PHONE): User
    {
        return User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer', 'phone' => $phone]);
    }

    private function sentCode(): string
    {
        $code = null;
        Bus::assertDispatched(SendLoginVerificationCodeJob::class, function ($job) use (&$code) {
            $code = (new \ReflectionProperty($job, 'code'))->getValue($job);

            return true;
        });

        return $code;
    }

    public function test_code_then_list_of_only_this_numbers_open_salons_with_prefilled_login(): void
    {
        $yas = Salon::factory()->create(['slug' => 'yas', 'name' => 'یاس']);
        $closed = Salon::factory()->create(['slug' => 'baste', 'name' => 'بسته', 'is_suspended' => true]);
        $other = Salon::factory()->create(['slug' => 'digari', 'name' => 'دیگری']);
        $this->customerIn($this->rasta);
        $this->customerIn($yas);
        $this->customerIn($closed);
        $this->customerIn($other, '09120000000');
        User::factory()->create(['salon_id' => $other->id, 'user_type' => 'staff', 'phone' => self::PHONE]);

        $this->get(route('my-salons.show'))->assertOk()->assertSee('data-my-salons-phone', false)
            ->assertSee('© '.date('Y').' '.config('brand.name').'.')->assertDontSee('سالن زیبایی '.config('brand.name'));
        $this->post(route('my-salons.send'), ['phone' => '۰۹۱۲۷۷۷۸۸۹۹'])->assertRedirect(route('my-salons.verify'));
        $this->get(route('my-salons.verify'))->assertOk()->assertSee(self::PHONE);
        // پیش از کد هیچ سالنی دیده نمی‌شود
        $this->get(route('my-salons.show'))->assertDontSee('data-my-salons-list', false);

        $this->post(route('my-salons.verify.store'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('my-salons.verify.store'), ['code' => $this->sentCode()])->assertRedirect(route('my-salons.show'));

        $html = $this->get(route('my-salons.show'))->assertOk()->assertSee('data-my-salons-list', false)->getContent();
        $this->assertStringContainsString(e(route('salon.login', ['salon_slug' => 'yas', 'phone' => self::PHONE])), $html);
        $this->assertStringContainsString(e(route('salon.login', ['salon_slug' => $this->rasta->slug, 'phone' => self::PHONE])), $html);
        $this->assertStringNotContainsString('baste', $html);
        $this->assertStringNotContainsString('digari', $html);

        // ورود هیچ سالنی ساخته نشده
        $this->assertGuest();

        // فرم ورود سالن شماره را پر می‌کند (فقط شماره‌ی معتبر)
        $this->get(route('salon.login', ['salon_slug' => 'yas', 'phone' => self::PHONE]))->assertOk()->assertSee('value="'.self::PHONE.'"', false);
        $this->get(route('salon.login', ['salon_slug' => 'yas', 'phone' => '<b>x</b>']))->assertOk()->assertDontSee('<b>x</b>', false);
    }

    public function test_unknown_number_gets_the_same_page_but_no_sms_and_no_valid_code(): void
    {
        $this->post(route('my-salons.send'), ['phone' => '09351112233'])->assertRedirect(route('my-salons.verify'));
        $this->get(route('my-salons.verify'))->assertOk();
        Bus::assertNotDispatched(SendLoginVerificationCodeJob::class);

        $this->post(route('my-salons.verify.store'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->get(route('my-salons.show'))->assertSee('data-my-salons-phone', false);
    }

    public function test_bad_number_and_expired_verification(): void
    {
        $this->post(route('my-salons.send'), ['phone' => '12345'])->assertSessionHasErrors('phone');

        $this->customerIn($this->rasta);
        $this->post(route('my-salons.send'), ['phone' => self::PHONE]);
        $this->post(route('my-salons.verify.store'), ['code' => $this->sentCode()]);
        $this->get(route('my-salons.show'))->assertSee('data-my-salons-list', false);

        $this->travel(16)->minutes();
        $this->get(route('my-salons.show'))->assertSee('data-my-salons-phone', false)->assertDontSee('data-my-salons-list', false);
    }

    public function test_landing_links_to_my_salons(): void
    {
        $this->get(route('central.home'))->assertOk()->assertSee(route('my-salons.show'), false);
    }
}
