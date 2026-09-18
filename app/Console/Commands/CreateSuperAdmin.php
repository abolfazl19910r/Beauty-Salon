<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\Admin\User\AdminUserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 7 — final commit of this
 * branch). Replaces the temporary seeded super-admin account (UserSeeder, phone 09399717435)
 * that existed only so `/superadmin` could be tested end-to-end before this command existed —
 * see that seeder's own docblock. That temporary account is intentionally left in place for
 * now (removing it is a separate, later cleanup once this command is actually in use).
 *
 * ⚠️ Deliberately does NOT pass 'roles' to AdminUserService::create() and then sync the
 * super-admin role directly via $user->roles()->syncWithoutDetaching() instead of going through
 * AdminUserService::update()/syncRoles(). Both of those funnel through the private
 * filterAssignableRoles(), which strips the super-admin role id unless the ACTING user
 * (auth()->user()) is already a super-admin — see that method's own logic. In a console command
 * there is no authenticated user at all, so auth()->user() is null and every super-admin role
 * assignment would be silently stripped if this went through the normal admin-user service path.
 * This is the exact same direct-sync pattern UserSeeder already uses for the temporary account.
 *
 * A super admin is deliberately NOT attached to salon_admins — a super admin doesn't own any
 * one salon (see EnsureSuperAdmin / EnsureAdminSalonActive, both of which bypass entirely on
 * hasRole('super-admin') rather than resolving a salon for them).
 */
class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'superadmin:create
                          {phone? : شماره موبایل سوپر ادمین (۰۹xxxxxxxxx)}
                          {name? : نام سوپر ادمین}
                          {--password= : رمز عبور (حداقل ۸ کاراکتر) — اگر داده نشود، پرسیده می‌شود}';

    /**
     * The console command description.
     */
    protected $description = 'ساخت یک حساب سوپر ادمین واقعی (جایگزین حساب موقت seed‌شده)';

    public function handle(AdminUserService $adminUserService): int
    {
        $phone = $this->argument('phone') ?? $this->ask('شماره موبایل سوپر ادمین (۰۹xxxxxxxxx)');
        $name = $this->argument('name') ?? $this->ask('نام سوپر ادمین');
        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('رمز عبور (حداقل ۸ کاراکتر)');
            $confirmation = $this->secret('تکرار رمز عبور');

            if ($password !== $confirmation) {
                $this->error('رمز عبور و تکرار آن یکسان نیستند.');

                return self::FAILURE;
            }
        }

        // ⭐ Same phone/password shape as StoreSalonRequest::rules() (admin_phone/admin_password)
        // for consistency — a super admin is still a 'staff' user_type under the hood
        // (AdminUserService::create() hardcodes that), so the same global-staff-uniqueness rule
        // applies here too.
        $validator = Validator::make(
            ['phone' => $phone, 'name' => $name, 'password' => $password],
            [
                'phone' => [
                    'required', 'string', 'regex:/^09[0-9]{9}$/',
                    Rule::unique('users', 'phone')->where(fn ($query) => $query->where('user_type', 'staff')),
                ],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
            [
                'phone.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
                'phone.unique' => 'کاربری (ادمین/متخصص) با این شماره موبایل از قبل وجود دارد.',
                'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['label' => 'سوپر ادمین']
        );

        $user = $adminUserService->create([
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
            'is_admin' => true,
            'is_active' => true,
            'roles' => [],
        ]);

        $user->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->info("✅ سوپر ادمین «{$user->name}» ({$user->phone}) با موفقیت ساخته شد.");

        return self::SUCCESS;
    }
}
