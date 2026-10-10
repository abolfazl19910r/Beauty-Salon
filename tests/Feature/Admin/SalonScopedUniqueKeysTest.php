<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\LoyaltySetting;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رگرسیون (۲۰۲۶-۱۰-۱۱): کلیدهای یکتای داده‌ی سالن در کل جدول یکتا بودند؛ ادمین سالن تازه نمی‌توانست
 * دسته‌بندی «رنگ مو» بسازد چون سالن دیگری داشت (خطای خام «validation.unique»).
 */
class SalonScopedUniqueKeysTest extends TestCase
{
    use RefreshDatabase;

    private Salon $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->other = Salon::factory()->create();
    }

    /** @param  callable(): mixed  $fn */
    private function inOtherSalon(callable $fn): mixed
    {
        return app(CurrentSalon::class)->withSalon($this->other, $fn);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'user_type' => 'staff']);
    }

    public function test_category_name_and_slug_are_unique_per_salon_only(): void
    {
        $foreign = $this->inOtherSalon(fn () => Category::factory()->create(['name' => 'رنگ مو', 'slug' => 'rng-mo']));
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/categories', ['name' => 'رنگ مو', 'is_active' => 1])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.categories.index'));
        $mine = Category::where('name', 'رنگ مو')->firstOrFail();
        $this->assertSame('rng-mo', $mine->slug);
        $this->assertNotSame($foreign->salon_id, $mine->salon_id);

        // تکرار در همان سالن هنوز رد می‌شود — با پیام فارسی، نه کلید خام
        $this->actingAs($admin)->post('/admin/categories', ['name' => 'رنگ مو', 'is_active' => 1])
            ->assertSessionHasErrors(['name' => 'نام قبلاً انتخاب شده است.']);

        // نام متفاوت با همان slug: پسوند می‌گیرد، خطای دیتابیس نمی‌دهد
        $this->actingAs($admin)->post('/admin/categories', ['name' => 'رنگ-مو', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('rng-mo-2', Category::where('name', 'رنگ-مو')->firstOrFail()->slug);
    }

    public function test_category_parent_must_belong_to_the_same_salon(): void
    {
        $foreignParent = $this->inOtherSalon(fn () => Category::factory()->create());

        $this->actingAs($this->admin())->post('/admin/categories', ['name' => 'زیرشاخه', 'parent_id' => $foreignParent->id])
            ->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('categories', ['name' => 'زیرشاخه']);
    }

    public function test_other_salon_owned_keys_no_longer_collide_across_salons(): void
    {
        $this->inOtherSalon(function () {
            BeautyService::factory()->create(['name' => 'کوتاهی مو', 'slug' => null]);
            BlogCategory::factory()->create(['name' => 'آموزش', 'slug' => null]);
            BlogPost::factory()->create(['title' => 'مراقبت از مو', 'slug' => null]);
            DiscountCode::factory()->create(['code' => 'NOROOZ']);
            LoyaltySetting::create(['key' => 'points_per_amount', 'value' => '5000']);
        });

        $service = BeautyService::factory()->create(['name' => 'کوتاهی مو', 'slug' => null]);
        $blogCategory = BlogCategory::factory()->create(['name' => 'آموزش', 'slug' => null]);
        $post = BlogPost::factory()->create(['title' => 'مراقبت از مو', 'slug' => null]);
        DiscountCode::factory()->create(['code' => 'NOROOZ']);
        LoyaltySetting::create(['key' => 'points_per_amount', 'value' => '20000']);

        $this->assertSame('kotahy-mo', $service->slug);
        $this->assertSame('amozsh', $blogCategory->slug);
        $this->assertSame('mrakbt-az-mo', $post->slug);
        $this->assertSame('20000', (string) LoyaltySetting::getValue('points_per_amount'));
        $this->assertSame(2, DiscountCode::withoutGlobalScopes()->where('code', 'NOROOZ')->count());
    }

    public function test_discount_code_is_still_unique_inside_one_salon(): void
    {
        DiscountCode::factory()->create(['code' => 'YALDA']);

        $fails = fn () => validator(['code' => 'YALDA'], ['code' => (new \App\Http\Requests\Admin\DiscountCode\StoreDiscountCodeRequest)->rules()['code']])->fails();

        $this->assertTrue($fails());
        $this->assertFalse($this->inOtherSalon($fails));
    }
}
