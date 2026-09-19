<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» — بازنویسی‌شده. قبل از این فاز، این تست‌ها روی
 * User::factory()->create() ساده کار می‌کردند چون AdminUserController اصلاً سالن‌محور نبود؛ حالا
 * $this->admin باید واقعاً owner سالن پیش‌فرض تست (`rasta` — از TestCase::setUp()) باشد، وگرنه
 * EnsureSalonOwner همه‌ی مسیرهای admin.users.* را 403 می‌کند.
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        // ⭐ UserFactory::configure()->afterCreating() از فاز ۱ همین الان یک is_admin=true تازه‌ساز
        // رو (وقتی سالن جاری هنوز owner نداره) به‌عنوان owner همون سالن attach می‌کنه — پس اینجا
        // نیازی به attach دستی دوباره نیست (وگرنه UNIQUE constraint می‌شکنه).
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_owner_can_create_a_staff_admin_with_limited_access(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'منشی جدید',
            'phone' => '09121234567',
            'password' => 'password123',
            'salon_role' => 'staff',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user = User::where('phone', '09121234567')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_admin);
        $this->assertTrue($user->roles->contains('name', 'staff'));
        $this->assertFalse($user->roles->contains('name', 'finance-access'));
        $this->assertSame('staff', $this->salon->admins()->wherePivot('user_id', $user->id)->first()->pivot->role);
    }

    public function test_owner_can_create_a_staff_admin_with_finance_access(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'منشی مالی',
            'phone' => '09121234570',
            'password' => 'password123',
            'salon_role' => 'staff',
            'finance_access' => '1',
            'is_active' => '1',
        ]);

        $user = User::where('phone', '09121234570')->first();
        $this->assertTrue($user->roles->contains('name', 'staff'));
        $this->assertTrue($user->roles->contains('name', 'finance-access'));
    }

    public function test_owner_can_create_a_second_owner(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'شریک کسب‌وکار',
            'phone' => '09121234571',
            'password' => 'password123',
            'salon_role' => 'owner',
            'is_active' => '1',
        ]);

        $user = User::where('phone', '09121234571')->first();
        $this->assertTrue($user->is_admin);
        $this->assertSame('owner', $this->salon->admins()->wherePivot('user_id', $user->id)->first()->pivot->role);
    }

    public function test_creating_user_without_is_active_leaves_phone_unverified(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'غیرفعال',
            'phone' => '09121234568',
            'password' => 'password123',
            'salon_role' => 'staff',
        ]);

        $user = User::where('phone', '09121234568')->first();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        User::factory()->create(['phone' => '09121234569']);

        $response = $this->actingAs($this->admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'تکراری',
                'phone' => '09121234569',
                'password' => 'password123',
                'salon_role' => 'staff',
            ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_salon_role_is_required_when_owner_creates_a_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'بدون نقش',
                'phone' => '09121234572',
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('salon_role');
    }

    public function test_admin_can_update_a_staff_users_role(): void
    {
        $user = User::factory()->create(['name' => 'قبل', 'is_admin' => false]);
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => 'بعد',
            'phone' => $user->phone,
            'salon_role' => 'staff',
            'finance_access' => '1',
            'is_active' => '1',
        ]);

        $user->refresh();
        $this->assertSame('بعد', $user->name);
        $this->assertTrue($user->roles->contains('name', 'finance-access'));
    }

    public function test_updating_phone_uniqueness_ignores_the_user_being_edited(): void
    {
        $user = User::factory()->create(['phone' => '09121111111']);
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name,
            'phone' => '09121111111', // same phone, should not trigger unique violation against itself
            'salon_role' => 'staff',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
    }

    public function test_deleting_a_user_with_bookings_is_blocked(): void
    {
        $user = User::factory()->create();
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);
        Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_a_user_without_bookings_succeeds(): void
    {
        $user = User::factory()->create();
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_update_status_activates_and_deactivates_a_staff_user(): void
    {
        $user = User::factory()->create(['phone_verified_at' => null]);
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}/status", ['is_active' => 1]);
        $this->assertNotNull($user->refresh()->phone_verified_at);

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}/status", ['is_active' => 0]);
        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $user = User::factory()->create();
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);
        $oldHash = $user->password;

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertNotSame($oldHash, $user->refresh()->password);
    }

    public function test_password_reset_requires_confirmation_to_match(): void
    {
        $user = User::factory()->create();
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'doesnotmatch',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_can_sync_roles_independently(): void
    {
        $user = User::factory()->create();
        $this->salon->admins()->attach($user->id, ['role' => 'staff']);
        $roleA = Role::factory()->create();
        $roleB = Role::factory()->create();
        $user->roles()->sync([$roleA->id]);

        $this->actingAs($this->admin)->post("/admin/users/{$user->id}/roles", ['roles' => [$roleB->id]]);

        $user->refresh();
        $this->assertFalse($user->roles->contains($roleA));
        $this->assertTrue($user->roles->contains($roleB));
    }

    public function test_index_only_lists_admins_of_the_current_salon(): void
    {
        $matching = User::factory()->create(['name' => 'یافت‌شونده', 'phone_verified_at' => now()]);
        $this->salon->admins()->attach($matching->id, ['role' => 'staff']);
        $role = Role::factory()->create();
        $matching->roles()->attach($role);

        // یک کاربر مربوط به سالن دیگر — نباید در فهرست این سالن ظاهر بشه (نشت بین‌سالنی قبلی)
        $otherSalon = Salon::factory()->create(['slug' => 'other-salon']);
        $otherSalonAdmin = User::factory()->create(['name' => 'دیگری']);
        $otherSalon->admins()->attach($otherSalonAdmin->id, ['role' => 'owner']);

        $response = $this->actingAs($this->admin)->get('/admin/users?search=یافت&role='.$role->id.'&status=active');

        $response->assertOk();
        $users = $response->viewData('users');
        $this->assertTrue($users->contains('id', $matching->id));
        $this->assertFalse($users->contains('id', $otherSalonAdmin->id));
        $this->assertCount(1, $users);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }

    public function test_owner_cannot_access_a_users_page_belonging_to_another_salon(): void
    {
        $otherSalon = Salon::factory()->create(['slug' => 'other-salon-2']);
        $foreignUser = User::factory()->create();
        $otherSalon->admins()->attach($foreignUser->id, ['role' => 'staff']);

        $this->actingAs($this->admin)->get("/admin/users/{$foreignUser->id}")->assertStatus(404);
    }

    // ⭐ تصمیم تأییدشده (۲۰۲۶-۰۹-۱۹): سالن باید همیشه حداقل یک owner فعال داشته باشد.
    public function test_the_last_owner_of_a_salon_cannot_be_deleted(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        $this->assertTrue($this->salon->fresh()->admins()->wherePivot('user_id', $this->admin->id)->exists());
    }

    public function test_the_last_owner_of_a_salon_cannot_be_demoted_to_staff(): void
    {
        $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'phone' => $this->admin->phone,
            'salon_role' => 'staff',
            'is_active' => '1',
        ]);

        $this->assertSame(
            'owner',
            $this->salon->fresh()->admins()->wherePivot('user_id', $this->admin->id)->first()->pivot->role
        );
    }

    public function test_the_last_owner_of_a_salon_cannot_be_deactivated(): void
    {
        $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}/status", ['is_active' => 0]);

        $this->assertNotNull($this->admin->refresh()->phone_verified_at);
    }

    public function test_a_second_owner_can_be_deleted_when_another_owner_remains(): void
    {
        $secondOwner = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->attach($secondOwner->id, ['role' => 'owner']);

        $response = $this->actingAs($this->admin)->delete("/admin/users/{$secondOwner->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $secondOwner->id]);
    }

    public function test_create_edit_and_show_pages_render_successfully(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/create')->assertOk();

        $staff = User::factory()->create();
        $this->salon->admins()->attach($staff->id, ['role' => 'staff']);

        $this->actingAs($this->admin)->get("/admin/users/{$staff->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/users/{$staff->id}/edit")->assertOk();
        $this->actingAs($this->admin)->get("/admin/users/{$this->admin->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/users/{$this->admin->id}/edit")->assertOk();
    }
}
