<?php

namespace App\Http\Controllers\Admin\Role;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function index(): View
    {
        // ⭐ باگ ۷ (گزارش‌شده ۲۰۲۶-۰۹-۱۸، رفع‌شده همان‌روز): این کوئری قبلاً نقش super-admin
        // را (نام + برچسب فارسی) در جدول لیست نقش‌ها به هر ادمین معمولی هم نشان می‌داد — فقط
        // دکمه‌های عملیات مخفی بودند، نه خودِ ردیف. escalation نبود ولی نشت اطلاعات بود.
        $roles = $this->roleRepository->paginateWithUserCount((bool) auth()->user()?->hasRole('super-admin'));

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = $this->permissionRepository->getAllGroupedByGroup();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => $this->nameRules(),
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'نام فنی نقش الزامی است.',
            'name.unique' => 'نام فنی نقش تکراری است.',
            'name.not_in' => 'این نام متعلق به یک نقش سیستمی است.',
            'label.required' => 'عنوان نمایشی نقش الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.roles.create')
                ->withErrors($validator)
                ->withInput();
        }

        $role = $this->roleRepository->create([
            'salon_id' => app(CurrentSalon::class)->id(),
            'name' => $request->name,
            'label' => $request->label,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    public function show(Role $role): View
    {
        // ⭐ باگ ۷ (ادامه): show() تنها متد این کنترلر بود که guardSuperRole() نداشت — یک ادمین
        // معمولی می‌توانست مستقیماً /admin/roles/{super-admin-role-id} را باز کند و ببیند چه
        // کسانی سوپر ادمین‌اند و این نقش چه مجوزهایی دارد.
        $this->ensureVisible($role);
        $this->guardSuperRole($role);

        $users = $role->users()->whereIn('users.id', $this->userRepository->querySalonMembers()->select('users.id'))->paginate(10);
        $permissions = $role->permissions->groupBy('group');

        return view('admin.roles.show', compact('role', 'users', 'permissions'));
    }

    public function edit(Role $role): View
    {
        $this->ensureEditable($role);
        $permissions = $this->permissionRepository->getAllGroupedByGroup();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureEditable($role);
        $validator = Validator::make($request->all(), [
            'name' => $this->nameRules($role),
            'label' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'نام فنی نقش الزامی است.',
            'name.unique' => 'نام فنی نقش تکراری است.',
            'name.not_in' => 'این نام متعلق به یک نقش سیستمی است.',
            'label.required' => 'عنوان نمایشی نقش الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.roles.edit', $role)
                ->withErrors($validator)
                ->withInput();
        }

        $role->update([
            'name' => $request->name,
            'label' => $request->label,
        ]);

        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت بروزرسانی شد.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureEditable($role);
        $role->users()->detach();
        $role->permissions()->detach();

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    public function assignForm(Role $role): View
    {
        $this->ensureVisible($role);
        $this->guardSuperRole($role);
        $users = $this->userRepository->getUsersWithoutRole($role->id);

        return view('admin.roles.assign', compact('role', 'users'));
    }

    public function assign(Request $request, Role $role): RedirectResponse
    {
        $this->ensureVisible($role);
        $this->guardSuperRole($role);
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (! $this->userRepository->querySalonMembers()->whereKey($value)->exists()) {
                    $fail('کاربر انتخاب شده عضو این سالن نیست.');
                }
            }],
        ], [
            'user_id.required' => 'انتخاب کاربر الزامی است.',
            'user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.roles.assign.form', $role)
                ->withErrors($validator)
                ->withInput();
        }

        $user = $this->userRepository->findOrFail($request->user_id);
        $user->assignRole($role);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت به کاربر اختصاص داده شد.');
    }

    public function removeUser(Role $role, User $user): RedirectResponse
    {
        $this->ensureVisible($role);
        $this->guardSuperRole($role);
        abort_unless($this->userRepository->isSalonMember($user), 404);
        $user->removeRole($role);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت از کاربر حذف شد.');
    }

    private function guardSuperRole(Role $role): void
    {
        abort_if(
            $role->name === 'super-admin' && ! auth()->user()?->hasRole('super-admin'),
            403,
            'امکان مدیریت نقش سوپر ادمین از پنل ادمین وجود ندارد.'
        );
    }

    /**
     * نقش سالن دیگه (route binding قبل از ست‌شدن سالن انجام می‌شه، پس global scope اینجا کمکی نمی‌کنه) → ۴۰۴.
     */
    private function ensureVisible(Role $role): void
    {
        $salonId = app(CurrentSalon::class)->id();

        abort_if($salonId !== null && $role->salon_id !== null && $role->salon_id !== $salonId, 404);
    }

    /**
     * نقش سیستمی (salon_id = null) مشترک همه‌ی سالن‌هاست و کد با نامش چک می‌کنه؛ فقط مدیر پلتفرم تغییرش می‌ده.
     */
    private function ensureEditable(Role $role): void
    {
        $this->ensureVisible($role);
        $this->guardSuperRole($role);

        abort_if(
            $role->isSystem() && ! auth()->user()?->hasRole('super-admin'),
            403,
            'نقش‌های سیستمی فقط توسط مدیر پلتفرم قابل تغییر هستند.'
        );
    }

    /**
     * نام فنی در هر سالن یکتاست و نمی‌تواند نام یک نقش سیستمی باشد (hasRole با نام چک می‌کند).
     */
    private function nameRules(?Role $role = null): array
    {
        $salonId = $role?->salon_id ?? app(CurrentSalon::class)->id();

        return [
            'required', 'string', 'max:255',
            Rule::unique('roles', 'name')
                ->where(fn ($q) => $salonId === null ? $q->whereNull('salon_id') : $q->where('salon_id', $salonId))
                ->ignore($role?->id),
            Rule::notIn($role?->isSystem() ? [] : $this->roleRepository->getSystemRoleNames()),
        ];
    }
}
