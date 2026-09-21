<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\ResetAdminUserPasswordRequest;
use App\Http\Requests\Admin\User\StoreAdminUserRequest;
use App\Http\Requests\Admin\User\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\User\AdminUserService;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» (feat/saas-multi-admin).
 *
 * ⚠️ باگ واقعی رفع‌شده: قبل از این فاز، index() مستقیم روی User::query() بدون هیچ فیلتری کار
 * می‌کرد — یک نشت کامل بین‌سالنی (هر ادمین هر سالنی همه‌ی کاربران همه‌ی سالن‌ها رو می‌دید/
 * ویرایش/حذف می‌کرد). حالا برای یک owner معمولی (CurrentSalon ست است — EnsureAdminSalonActive)
 * فقط ادمین‌های *همون* سالن (از طریق Salon::admins()، نه User::query()) قابل‌مشاهده/مدیریت‌ند.
 *
 * برای سوپر ادمین که مستقیم /admin/users را باز کند (نه از /superadmin)، CurrentSalon هیچ‌وقت
 * ست نمی‌شود (به EnsureAdminSalonActive/EnsureSalonOwner نگاه کن) — رفتار قدیمی (دیدن همه‌ی
 * کاربران، بدون مفهوم salon_role) برای او دست‌نخورده می‌ماند؛ ساخت واقعی اولین ادمین یک سالن
 * جدید همچنان مسیر /superadmin است.
 */
class AdminUserController extends Controller
{
    public function __construct(
        protected readonly AdminUserService $userService,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $salon = app(CurrentSalon::class)->get();
        $query = $salon ? $this->salonAdminsQuery($salon) : $this->userRepository->query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('phone_verified_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNull('phone_verified_at');
            }
        }

        $query->with('roles:id,name,label');
        if ($salon) {
            $query->with(['salons' => fn ($q) => $q->where('salons.id', $salon->id)]);
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::when(
            ! auth()->user()->hasRole('super-admin'),
            fn ($q) => $q->where('name', '!=', 'super-admin')
        )->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::when(
            ! auth()->user()->hasRole('super-admin'),
            fn ($q) => $q->where('name', '!=', 'super-admin')
        )->get();
        $hasCurrentSalon = app(CurrentSalon::class)->id() !== null;

        return view('admin.users.create', compact('roles', 'hasCurrentSalon'));
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        try {
            $salon = app(CurrentSalon::class)->get();
            $salonRole = $request->validated('salon_role');

            $this->userService->create([
                ...$request->validated(),
                // ⭐ وقتی CurrentSalon ست است، is_admin و roles دیگر از چک‌باکس آزاد نمی‌آیند —
                // مستقیم از انتخاب owner («مالک» یا «منشی») مشتق می‌شوند، تا یک staff هیچ‌وقت
                // is_admin=true نگیرد (که کل محدودیت permission را دور می‌زد — به
                // User::hasPermission() نگاه کن).
                'is_admin' => $salon ? $salonRole === 'owner' : $request->boolean('is_admin'),
                'is_active' => $request->boolean('is_active'),
                'roles' => $salon
                    ? $this->salonRoleIds($salonRole, $request->boolean('finance_access'))
                    : $request->validated('roles', []),
                'salon' => $salon,
                'salon_role' => $salon ? $salonRole : null,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');

        } catch (\App\Exceptions\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در ایجاد کاربر: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(User $user): View
    {
        $this->authorizeSalonMembership($user);

        $roles = Role::when(
            ! auth()->user()->hasRole('super-admin'),
            fn ($q) => $q->where('name', '!=', 'super-admin')
        )->get();
        $userRoles = $user->roles()->pluck('roles.id')->toArray();
        $bookings = $user->bookings()->with(['service', 'specialist'])->latest()->take(5)->get();
        $salonRole = app(CurrentSalon::class)->get()?->admins()
            ->wherePivot('user_id', $user->id)->first()?->pivot->role;

        return view('admin.users.show', compact('user', 'roles', 'userRoles', 'bookings', 'salonRole'));
    }

    public function edit(User $user): View
    {
        $this->authorizeSalonMembership($user);

        $roles = Role::when(
            ! auth()->user()->hasRole('super-admin'),
            fn ($q) => $q->where('name', '!=', 'super-admin')
        )->get();
        $userRoles = $user->roles()->pluck('roles.id')->toArray();
        $salon = app(CurrentSalon::class)->get();
        $hasCurrentSalon = $salon !== null;
        $salonRole = $salon?->admins()->wherePivot('user_id', $user->id)->first()?->pivot->role;
        $financeAccess = $userRoles && in_array(Role::where('name', 'finance-access')->value('id'), $userRoles);

        return view('admin.users.edit', compact('user', 'roles', 'userRoles', 'salonRole', 'financeAccess', 'hasCurrentSalon'));
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->authorizeSalonMembership($user);

        try {
            $salon = app(CurrentSalon::class)->get();
            $salonRole = $request->validated('salon_role');

            $this->userService->update($user, [
                ...$request->validated(),
                'is_admin' => $salon ? $salonRole === 'owner' : $request->boolean('is_admin'),
                'is_active' => $request->boolean('is_active'),
                'roles' => $salon
                    ? $this->salonRoleIds($salonRole, $request->boolean('finance_access'))
                    : $request->validated('roles', []),
                'salon_role' => $salon ? $salonRole : null,
            ], $salon);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');

        } catch (\App\Exceptions\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در بروزرسانی کاربر: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSalonMembership($user);

        try {
            $salon = app(CurrentSalon::class)->get();
            $remainingBookings = $this->userService->delete($user, $salon);

            if ($remainingBookings > 0) {
                return redirect()->back()
                    ->with('error', "این کاربر دارای {$remainingBookings} نوبت ثبت شده است و قابل حذف نیست.");
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر با موفقیت حذف شد.');

        } catch (\App\Exceptions\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در حذف کاربر: '.$e->getMessage());
        }
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSalonMembership($user);

        try {
            $salon = app(CurrentSalon::class)->get();
            $activate = (bool) $request->input('is_active', 0);
            $this->userService->updateStatus($user, $activate, $salon);

            $status = $activate ? 'فعال' : 'غیرفعال';
            $message = "وضعیت کاربر با موفقیت به «{$status}» تغییر یافت.";

            return redirect()->back()->with('success', $message);

        } catch (\App\Exceptions\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در تغییر وضعیت کاربر: '.$e->getMessage());
        }
    }

    public function resetPassword(ResetAdminUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->authorizeSalonMembership($user);

        try {
            $this->userService->resetPassword($user, $request->password);

            return redirect()->back()->with('success', 'رمز عبور با موفقیت بازنشانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بازنشانی رمز عبور: '.$e->getMessage());
        }
    }

    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSalonMembership($user);

        try {
            $this->userService->syncRoles($user, $request->input('roles', []));

            return redirect()->back()->with('success', 'نقش‌های کاربر با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بروزرسانی نقش‌ها: '.$e->getMessage());
        }
    }

    private function salonAdminsQuery(Salon $salon): Builder
    {
        return $this->userRepository->query()->whereHas('salons', function ($q) use ($salon) {
            $q->where('salons.id', $salon->id);
        });
    }

    /**
     * وقتی CurrentSalon ست است (owner)، هر عملیات روی یک User باید تأیید کند که او واقعاً یکی
     * از ادمین‌های *همین* سالن است — وگرنه یک owner می‌توانست با حدس‌زدن id، کاربر سالن دیگری
     * را ببیند/ویرایش/حذف کند (route model binding به‌تنهایی سالن را چک نمی‌کند).
     */
    private function authorizeSalonMembership(User $user): void
    {
        $salon = app(CurrentSalon::class)->get();

        if (! $salon) {
            return;
        }

        abort_unless(
            $salon->admins()->wherePivot('user_id', $user->id)->exists(),
            404
        );
    }

    /**
     * @return array<int>
     */
    private function salonRoleIds(?string $salonRole, bool $financeAccess): array
    {
        if ($salonRole === 'owner') {
            // ⭐ هم‌راستا با SuperAdminService::createSalonWithAdmin() (owner اول): owner به
            // نقش سیستمی جداگانه نیاز ندارد، is_admin=true طبق bypass مستندشده‌ی
            // User::hasPermission() کافی و همیشگی این پروژه است.
            return [];
        }

        $roleIds = Role::whereIn('name', array_filter([
            'staff',
            $financeAccess ? 'finance-access' : null,
        ]))->pluck('id')->all();

        return $roleIds;
    }
}
