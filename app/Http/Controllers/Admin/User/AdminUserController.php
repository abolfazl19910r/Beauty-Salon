<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\ResetAdminUserPasswordRequest;
use App\Http\Requests\Admin\User\StoreAdminUserRequest;
use App\Http\Requests\Admin\User\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
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
            $user = User::create([
                'name'              => $request->name,
                'phone'             => $request->phone,
                'password'          => Hash::make($request->password),
                'is_admin'          => $request->boolean('is_admin'),
                'phone_verified_at' => $request->boolean('is_active') ? now() : null,
            ]);

            if ($request->filled('roles')) {
                $user->roles()->sync($request->roles);
            }

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
            $user->update([
                'name'              => $request->name,
                'phone'             => $request->phone,
                'is_admin'          => $request->boolean('is_admin'),
                'phone_verified_at' => $request->boolean('is_active')
                    ? ($user->phone_verified_at ?? now())
                    : null,
            ]);

            $roles = $request->input('roles', []);
            $user->roles()->sync($roles);

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
            $bookingsCount = $user->bookings()->count();
            if ($bookingsCount > 0) {
                return redirect()->back()
                    ->with('error', "این کاربر دارای {$bookingsCount} نوبت ثبت شده است و قابل حذف نیست.");
            }

            $user->roles()->detach();
            $user->delete();

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

            $user->forceFill([
                'phone_verified_at' => $activate ? ($user->phone_verified_at ?? now()) : null,
            ])->save();

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
            $user->forceFill(['password' => Hash::make($request->password)])->save();
            return redirect()->back()->with('success', 'رمز عبور با موفقیت بازنشانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بازنشانی رمز عبور: ' . $e->getMessage());
        }
    }

    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        try {
            $user->roles()->sync($request->input('roles', []));
            return redirect()->back()->with('success', 'نقش‌های کاربر با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'خطا در بروزرسانی نقش‌ها: ' . $e->getMessage());
        }
    }
}
