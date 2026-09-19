<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\BeautyService;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\GalleryImage;
use App\Models\Leave;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Fix (ممیزی implicit route-model-binding روی کل پنل ادمین، ۲۰۲۶-۰۹-۲۰): SubstituteBindings
 * (گروه global middleware `web`) implicit route parameter ها را قبل از هر middleware سطح-route
 * (`salon.active`/EnsureAdminSalonActive، که CurrentSalon را ست می‌کند) resolve می‌کند — یعنی در
 * یک request واقعی و تازه (CurrentSalon هنوز null)، BelongsToSalon هیچ فیلتری در لحظه‌ی binding
 * اعمال نمی‌کند و هر id از هر سالنی قابل bind شدن است. این کلاس دقیقاً همان باگی است که قبلاً فقط
 * در AdminReportExportController::download() پیدا و فیکس شده بود — این ممیزی نشان داد کل پنل ادمین
 * (به‌جز AdminCategoryController::show که implicit binding ندارد) به همین شکل باز بوده است.
 *
 * الگوی تست: هر رکورد داخل $otherSalon ساخته می‌شود، سپس CurrentSalon کاملاً clear می‌شود — این
 * دقیقاً شبیه‌سازی لحظه‌ی SubstituteBindings در یک request واقعی است؛ reset کردن به سالن ادمین
 * (که در نسخه‌ی اول این probe اشتباهاً انجام می‌شد) باعث می‌شد آسیب‌پذیری اصلاً رخ ندهد و تست
 * false-negative بدهد.
 */
class CrossSalonImplicitBindingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Salon $otherSalon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->otherSalon = Salon::factory()->create(['slug' => 'other-salon-binding-test']);
    }

    private function createInOtherSalon(\Closure $factory)
    {
        app(CurrentSalon::class)->set($this->otherSalon);
        $record = $factory();
        app(CurrentSalon::class)->clear();

        return $record;
    }

    public function test_cannot_edit_another_salons_announcement(): void
    {
        $a = $this->createInOtherSalon(fn () => Announcement::factory()->create());

        $this->actingAs($this->admin)->get("/admin/announcements/{$a->id}/edit")->assertNotFound();
    }

    public function test_cannot_edit_another_salons_blog_category(): void
    {
        $c = $this->createInOtherSalon(fn () => BlogCategory::factory()->create());

        $this->actingAs($this->admin)->get("/admin/blog/categories/{$c->id}/edit")->assertNotFound();
    }

    public function test_cannot_view_another_salons_blog_post(): void
    {
        $p = $this->createInOtherSalon(fn () => BlogPost::factory()->create());

        $this->actingAs($this->admin)->get("/admin/blog/{$p->id}")->assertNotFound();
    }

    public function test_cannot_view_another_salons_booking(): void
    {
        $b = $this->createInOtherSalon(function () {
            $specialist = Specialist::factory()->create();
            $service = BeautyService::factory()->create();

            return Booking::factory()->create([
                'specialist_id' => $specialist->id,
                'service_id' => $service->id,
            ]);
        });

        $this->actingAs($this->admin)->get("/admin/bookings/{$b->id}")->assertNotFound();
    }

    public function test_cannot_edit_another_salons_discount_code(): void
    {
        $d = $this->createInOtherSalon(fn () => DiscountCode::factory()->create());

        $this->actingAs($this->admin)->get("/admin/discount-codes/{$d->id}/edit")->assertNotFound();
    }

    public function test_cannot_delete_another_salons_gallery_image(): void
    {
        $img = $this->createInOtherSalon(fn () => GalleryImage::factory()->create());

        $this->actingAs($this->admin)->delete("/admin/gallery/{$img->id}")->assertNotFound();
        $this->assertNotNull(GalleryImage::withoutGlobalScopes()->find($img->id));
    }

    public function test_cannot_edit_another_salons_service(): void
    {
        $s = $this->createInOtherSalon(fn () => BeautyService::factory()->create());

        $this->actingAs($this->admin)->get("/admin/services/{$s->id}/edit")->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist(): void
    {
        $sp = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->admin)->get("/admin/specialists/{$sp->id}")->assertNotFound();
    }

    public function test_cannot_edit_another_salons_specialist_schedule(): void
    {
        $sp = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->admin)->get("/admin/specialists/{$sp->id}/schedules/edit")->assertNotFound();
    }

    public function test_cannot_view_another_salons_specialist_leaves(): void
    {
        $sp = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->admin)->get("/admin/specialists/{$sp->id}/leaves")->assertNotFound();
    }

    public function test_cannot_list_another_salons_specialist_holidays(): void
    {
        $sp = $this->createInOtherSalon(fn () => Specialist::factory()->create());

        $this->actingAs($this->admin)->get("/admin/specialists/{$sp->id}/holidays")->assertNotFound();
    }

    // ⭐ SpecialistWallet/WithdrawalRequest/Leave: این سه مدل اصلاً BelongsToSalon ندارند (فقط از
    // طریق specialist_id به سالن وصل‌اند) — یعنی نه یک باگ زمان‌بندی، بلکه فقدان کامل scope در هر
    // لحظه‌ای، نه فقط لحظه‌ی binding. فیکس این سه از طریق Specialist::withoutGlobalScopes() انجام
    // شده، نه اضافه‌کردن trait (چون خودِ جدول ستون salon_id ندارد).

    public function test_cannot_view_another_salons_specialist_wallet(): void
    {
        $wallet = $this->createInOtherSalon(function () {
            $specialist = Specialist::factory()->create();

            return SpecialistWallet::factory()->for($specialist)->create();
        });

        $this->actingAs($this->admin)->get("/admin/wallet/{$wallet->id}")->assertNotFound();
    }

    public function test_cannot_view_another_salons_withdrawal_request(): void
    {
        $wr = $this->createInOtherSalon(function () {
            $specialist = Specialist::factory()->create();
            $wallet = SpecialistWallet::factory()->for($specialist)->create();

            return WithdrawalRequest::factory()->create([
                'specialist_id' => $specialist->id,
                'wallet_id' => $wallet->id,
            ]);
        });

        $this->actingAs($this->admin)->get("/admin/wallet/withdrawals/{$wr->id}")->assertNotFound();
    }

    public function test_cannot_update_another_salons_leave_status(): void
    {
        $leave = $this->createInOtherSalon(function () {
            $specialist = Specialist::factory()->create();

            return Leave::factory()->create(['specialist_id' => $specialist->id]);
        });

        $this->actingAs($this->admin)
            ->put("/admin/leaves/{$leave->id}", ['status' => 'approved'])
            ->assertNotFound();
    }

    // ⭐ کنترل — AdminCategoryController::show($id) عمداً implicit binding ندارد (پارامتر خام
    // $id می‌گیرد و خودش داخل بدنه‌ی متد Category::findOrFail($id) صدا می‌زند، یعنی بعد از اجرای
    // کامل salon.active) — این تنها مسیری بود که در ممیزی اولیه بدون هیچ فیکسی هم امن از آب
    // درآمد؛ اینجا فقط برای مستندکردن این رفتار به‌عنوان تست رگرسیون نگه داشته شده.
    public function test_category_show_was_already_safe_without_implicit_binding(): void
    {
        $c = $this->createInOtherSalon(fn () => Category::factory()->create());

        $this->actingAs($this->admin)->get("/admin/categories/{$c->id}")->assertNotFound();
    }
}
