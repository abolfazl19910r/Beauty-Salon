<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use App\Support\SalonWorkingHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ اطلاعات تماس و فعالیت هر سالن (۲۰۲۶-۰۹-۲۳): آدرس، تلفن، سابقه‌ی کاری و ساعات کاری —
 * موقع ثبت‌نام عمومی اجباری گرفته می‌شن، per-salon ذخیره می‌شن (مثل name) و جای متن ثابت
 * مشترکی که قبلاً در فوتر/صفحه‌ی اصلی/صفحه‌ی تشکر بود، نمایش داده می‌شن.
 */
class SalonContactDetailsTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    private function signupPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن یاس',
            'slug' => 'yas-salon',
            'owner_name' => 'نگار احمدی',
            'owner_phone' => '09127778899',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload(), $overrides);
    }

    public function test_signup_stores_contact_details_on_the_new_salon(): void
    {
        $this->post(route('salon-signup.store'), $this->signupPayload())
            ->assertRedirect(route('salon-signup.verify'));

        $salon = Salon::where('slug', 'yas-salon')->firstOrFail();

        $this->assertSame('تهران، خیابان ولیعصر، پلاک ۱۲', $salon->address);
        $this->assertSame('02112345678', $salon->phone);
        $this->assertSame(now()->year - 7, $salon->established_year);
        $this->assertSame(7, $salon->experienceYears());
        $this->assertNull($salon->working_hours[5]);
        $this->assertSame(['open' => '09:00', 'close' => '17:00'], $salon->working_hours[4]);
    }

    public function test_experience_years_grow_automatically_every_year(): void
    {
        $this->post(route('salon-signup.store'), $this->signupPayload());
        $this->travel(2)->years();

        $this->assertSame(9, Salon::where('slug', 'yas-salon')->firstOrFail()->experienceYears());
    }

    public function test_persian_digits_and_separators_in_phone_and_experience_are_accepted(): void
    {
        $this->post(route('salon-signup.store'), $this->signupPayload([
            'salon_phone' => '۰۲۱-۱۲۳۴ ۵۶۷۸',
            'experience_years' => '۱۲',
        ]))->assertSessionHasNoErrors();

        $salon = Salon::where('slug', 'yas-salon')->firstOrFail();
        $this->assertSame('02112345678', $salon->phone);
        $this->assertSame(12, $salon->experienceYears());
    }

    public function test_all_four_fields_are_required_at_signup(): void
    {
        $payload = $this->signupPayload();
        unset($payload['salon_address'], $payload['salon_phone'], $payload['experience_years'], $payload['working_hours']);

        $this->post(route('salon-signup.store'), $payload)
            ->assertSessionHasErrors(['salon_address', 'salon_phone', 'experience_years', 'working_hours']);

        $this->assertDatabaseMissing('salons', ['slug' => 'yas-salon']);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post(route('salon-signup.store'), $this->signupPayload(['salon_phone' => '12345']))
            ->assertSessionHasErrors('salon_phone');
    }

    public function test_working_hours_close_before_open_is_rejected(): void
    {
        $payload = $this->signupPayload();
        $payload['working_hours'][1] = ['open' => '18:00', 'close' => '10:00'];

        $this->post(route('salon-signup.store'), $payload)->assertSessionHasErrors('working_hours.1');
    }

    public function test_working_hours_with_every_day_closed_is_rejected(): void
    {
        $payload = $this->signupPayload();
        $payload['working_hours'] = array_fill_keys([0, 1, 2, 3, 4, 5, 6], ['closed' => '1']);

        $this->post(route('salon-signup.store'), $payload)->assertSessionHasErrors('working_hours');
    }

    public function test_working_hours_lines_merge_consecutive_identical_days(): void
    {
        $this->assertSame([
            'شنبه تا چهارشنبه: ۰۹:۰۰ تا ۲۱:۰۰',
            'پنجشنبه: ۰۹:۰۰ تا ۱۷:۰۰',
            'جمعه: تعطیل',
        ], SalonWorkingHours::lines(SalonWorkingHours::defaults()));

        $this->assertSame([], SalonWorkingHours::lines(null));
    }

    public function test_customer_footer_and_home_show_each_salons_own_details(): void
    {
        $a = app(CurrentSalon::class)->get();
        $a->update([
            'address' => 'اصفهان، چهارباغ عباسی',
            'phone' => '03131234567',
            'established_year' => now()->year - 4,
            'working_hours' => SalonWorkingHours::defaults(),
        ]);
        Salon::factory()->create([
            'slug' => 'other-salon',
            'address' => 'شیراز، خیابان زند',
            'phone' => '07131234567',
        ]);

        $this->get('/s/'.$a->slug)
            ->assertOk()
            ->assertSee('اصفهان، چهارباغ عباسی')
            ->assertSee('03131234567')
            ->assertSee('شنبه تا چهارشنبه: ۰۹:۰۰ تا ۲۱:۰۰')
            ->assertSee('data-target="4"', false)
            ->assertDontSee('شیراز، خیابان زند')
            ->assertDontSee('07131234567')
            // متن‌های ثابت مشترک قبلی دیگه هیچ‌جا نیستن
            ->assertDontSee('021-12345678')
            ->assertDontSee('info@rasta-salon.ir');
    }

    public function test_salon_without_details_shows_no_fake_contact_info(): void
    {
        app(CurrentSalon::class)->get()->update([
            'address' => null, 'phone' => null, 'established_year' => null, 'working_hours' => null,
        ]);

        $this->get('/s/'.app(CurrentSalon::class)->get()->slug)
            ->assertOk()
            ->assertDontSee('تهران، خیابان ولیعصر')
            ->assertDontSee('سال تجربه')
            ->assertSee('اطلاعات تماس سالن به‌زودی اضافه می‌شود.');
    }

    public function test_super_admin_can_edit_contact_details_and_editing_without_hours_keeps_them(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);
        $salon = Salon::factory()->create(['working_hours' => SalonWorkingHours::defaults()]);

        $this->actingAs($superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => $salon->max_specialists_count,
            'salon_address' => 'مشهد، بلوار سجاد',
            'salon_phone' => '05131234567',
            'experience_years' => '3',
        ])->assertRedirect(route('superadmin.salons.index'));

        $salon->refresh();
        $this->assertSame('مشهد، بلوار سجاد', $salon->address);
        $this->assertSame('05131234567', $salon->phone);
        $this->assertSame(3, $salon->experienceYears());
        // ⭐ فرم ویرایش بدون تیک «ثبت ساعات کاری» هیچ working_hours ای نمی‌فرسته → دست‌نخورده.
        $this->assertEquals(SalonWorkingHours::defaults(), $salon->working_hours);
    }
}
