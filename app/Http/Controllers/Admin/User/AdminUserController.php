<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\ResetAdminUserPasswordRequest;
use App\Http\Requests\Admin\User\StoreAdminUserRequest;
use App\Http\Requests\Admin\User\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\User\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    protected AdminUserService $userService;

    public function __construct(AdminUserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): View
    {
        $query = User::query();

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

        $users = $query->with('roles:id,name,label')->latest()->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        try {
            $this->userService->create([
                ...$request->validated(),
                'is_admin'  => $request->boolean('is_admin'),
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در ایجاد کاربر: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(User $user): View
    {
        $roles    = Role::all();
        $userRoles= $user->roles()->pluck('roles.id')->toArray();
        $bookings = $user->bookings()->with(['service', 'specialist'])->latest()->take(5)->get();
        return view('admin.users.show', compact('user', 'roles', 'userRoles', 'bookings'));
    }

    public function edit(User $user): View
    {
        $roles     = Role::all();
        $userRoles = $user->roles()->pluck('roles.id')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->update($user, [
                ...$request->validated(),
                'is_admin'  => $request->boolean('is_admin'),
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در بروزرسانی کاربر: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $remainingBookings = $this->userService->delete($user);

            if ($remainingBookings > 0) {
                return redirect()->back()
                    ->with('error', "این کاربر دارای {$remainingBookings} نوبت ثبت شده است و قابل حذف نیست.");
            }

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر با موفقیت حذف شد.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در حذف کاربر: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        try {
            $activate = (bool) $request->input('is_active', 0);
            $this->userService->updateStatus($user, $activate);

            $status  = $activate ? 'فعال' : 'غیرفعال';
            $message = "وضعیت کاربر با موفقیت به «{$status}» تغییر یافت.";

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'خطا در تغییر وضعیت کاربر: ' . $e->getMessage());
        }
    }

    public function resetPassword(ResetAdminUserPasswordRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->resetPassword($user, $request->password);
            return redirect()->back()->with('success', 'رمز عبور با موفقیت بازنشانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بازنشانی رمز عبور: ' . $e->getMessage());
        }
    }

    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        try {
            $this->userService->syncRoles($user, $request->input('roles', []));
            return redirect()->back()->with('success', 'نقش‌های کاربر با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بروزرسانی نقش‌ها: ' . $e->getMessage());
        }
    }
}
