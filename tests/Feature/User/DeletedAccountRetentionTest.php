<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DeletedAccountArchive;
use App\Models\SecurityLog;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 🐞 بسته‌ی مجوز 5د: حذف حساب مشتری داده‌ی شخصی (نام، شماره) را فوراً پاک می‌کرد و هیچ ردی نمی‌ماند — تعارض با ماده‌ی ۶۶۸ آیین
 * دادرسی کیفری (اطلاعات کاربر و IP دست‌کم شش ماه پس از خاتمه‌ی اشتراک). ماده‌ی ۵۹ هـ قانون تجارت الکترونیکی حق محو را «با رعایت
 * ضوابط مربوطه» می‌دهد: حساب و نمایش عمومی همان لحظه پاک می‌شود، هویت و IP شش ماه در جای جدا و رمزنگاری‌شده (فقط ابرمدیر)
 * می‌ماند و بعد خودکار پاک می‌شود.
 */
class DeletedAccountRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create([
            'salon_id' => app(CurrentSalon::class)->id(), 'user_type' => 'customer', 'name' => 'مریم احمدی', 'phone' => '09120000777',
        ]);
    }

    public function test_deleting_an_account_with_history_keeps_identity_and_ip_for_six_months_apart(): void
    {
        $user = $this->customer();
        Booking::factory()->create([
            'user_id' => $user->id, 'service_id' => BeautyService::factory()->create()->id, 'specialist_id' => Specialist::factory()->create()->id,
            'booking_time' => now()->subDays(3), 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        SecurityLog::forceCreate(['user_id' => $user->id, 'event' => 'login_attempt', 'ip_address' => '5.9.9.1', 'user_agent' => 'Phone A', 'created_at' => now()->subDays(20)]);
        SecurityLog::forceCreate(['user_id' => $user->id, 'event' => 'booking_created', 'ip_address' => '5.9.9.2', 'user_agent' => 'Phone B', 'created_at' => now()->subDays(3)]);

        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '5.9.9.3', 'HTTP_USER_AGENT' => 'Phone C'])
            ->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

        // حساب همان لحظه ناشناس است (رفتار قبلی می‌ماند)
        $this->assertSame('مشتری حذف‌شده', $user->fresh()->name);

        $archive = DeletedAccountArchive::where('user_id', $user->id)->first();
        $this->assertNotNull($archive, 'هویت و IP پس از حذف در جای جدا می‌ماند');
        $this->assertSame('مریم احمدی', $archive->name);
        $this->assertSame('09120000777', $archive->phone);
        $this->assertSame('5.9.9.3', $archive->deletion_ip);
        $this->assertSame('Phone C', $archive->deletion_user_agent);
        $this->assertEqualsCanonicalizing(['5.9.9.1', '5.9.9.2', '5.9.9.3'], collect($archive->ip_history)->pluck('ip')->all());
        $this->assertTrue($archive->purge_after->between(now()->addDays(189), now()->addDays(191)));
        $this->assertSame($archive->id, SecurityLog::where('event', 'account_deleted')->first()?->context['archive_id']);

        // در دیتابیس رمزنگاری‌شده (نه متن ساده)؛ جست‌وجو با hash شماره
        $raw = DB::table('deleted_account_archives')->where('id', $archive->id)->first();
        $this->assertStringNotContainsString('09120000777', (string) $raw->phone);
        $this->assertStringNotContainsString('5.9.9.1', (string) $raw->ip_history);
        $this->assertSame($archive->id, DeletedAccountArchive::findByPhone('09120000777')->first()?->id);
    }

    public function test_an_account_without_history_is_deleted_but_still_archived(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();

        $this->assertModelMissing($user);
        $this->assertSame('09120000777', DeletedAccountArchive::where('user_id', $user->id)->first()?->phone);
    }

    public function test_the_app_deletion_path_archives_too(): void
    {
        $user = $this->customer();
        $token = $user->createToken('phone', ['customer'])->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/customer/account', ['password' => 'password'])->assertSuccessful();

        $this->assertNotNull(DeletedAccountArchive::where('user_id', $user->id)->first());
    }

    public function test_archives_are_purged_automatically_after_the_period(): void
    {
        $old = DeletedAccountArchive::create(['user_id' => 1, 'name' => 'x', 'phone' => '09120000001', 'deleted_at' => now()->subDays(200), 'purge_after' => now()->subDay()]);
        $kept = DeletedAccountArchive::create(['user_id' => 2, 'name' => 'y', 'phone' => '09120000002', 'deleted_at' => now()->subDays(10), 'purge_after' => now()->addDays(180)]);

        $this->artisan('retention:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($kept);
    }

    /**
     * purge_after برای MariaDB قدیمی (XAMPP، خطای 1067) nullable شد. ردیف بی آن نه زودتر از مدت قانونی پاک می‌شود و نه برای همیشه
     * می‌ماند (حریم خصوصی وعده‌ی پاک شدن خودکار داده): مدت نگهداری از created_at خودش.
     */
    public function test_an_archive_without_purge_after_is_kept_for_the_legal_period_then_purged(): void
    {
        $recent = DeletedAccountArchive::create(['user_id' => 3, 'name' => 'z', 'phone' => '09120000003', 'deleted_at' => now()->subDays(100)]);
        $recent->forceFill(['created_at' => now()->subDays(100)])->save();
        $stale = DeletedAccountArchive::create(['user_id' => 4, 'name' => 'w', 'phone' => '09120000004', 'deleted_at' => now()->subDays(200)]);
        $stale->forceFill(['created_at' => now()->subDays(200)])->save();
        $this->assertNull($stale->fresh()->purge_after);

        $this->artisan('retention:prune')->assertSuccessful();

        $this->assertModelExists($recent);
        $this->assertModelMissing($stale);
    }

    public function test_only_super_admin_sees_the_archive(): void
    {
        DeletedAccountArchive::create(['user_id' => 9, 'name' => 'مریم احمدی', 'phone' => '09120000777', 'deleted_at' => now(), 'purge_after' => now()->addDays(190)]);

        $salonAdmin = User::factory()->create(['is_admin' => true, 'salon_id' => app(CurrentSalon::class)->id()]);
        $this->actingAs($salonAdmin)->get(route('superadmin.retained-data.index', ['phone' => '09120000777']))->assertForbidden();

        $super = User::factory()->create();
        $super->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $this->actingAs($super)->get(route('superadmin.retained-data.index', ['phone' => '09120000777']))
            ->assertOk()->assertSee('مریم احمدی');
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'retained-data.search', 'user_id' => $super->id]);
    }
}
