<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        if ($request->filled('status')) {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     *
     * @return View
     */
    public function create(): View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => 'required|string|min:8',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'نام کاربر الزامی است.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => $request->has('is_admin'),
                'is_active' => $request->has('is_active', true),
            ]);

            if ($request->has('roles')) {
                $user->roles()->sync($request->roles);
            }

            Log::info('New user created', [
                'user_id' => $user->id,
                'created_by' => auth()->id()
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');

        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در ایجاد کاربر: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     *
     * @param User $user
     * @return View
     */
    public function show(User $user): View
    {
        $roles = Role::all();
        $userRoles = $user->roles()->pluck('roles.id')->toArray();
        $bookings = $user->bookings()->with(['service', 'specialist'])->latest()->take(5)->get();
        return view('admin.users.show', compact('user', 'roles', 'userRoles', 'bookings'));
    }

    /**
     *
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        $userRoles = $user->roles()->pluck('roles.id')->toArray();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:11|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'نام کاربر الزامی است.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_admin' => $request->has('is_admin'),
                'is_active' => $request->has('is_active', true),
            ]);

            if ($request->has('roles')) {
                $user->roles()->sync($request->roles);
            } else {
                $user->roles()->detach();
            }

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id()
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در بروزرسانی کاربر: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     *
     * @param User $user
     * @return RedirectResponse
     */
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

            Log::info('User deleted', [
                'user_id' => $user->id,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر با موفقیت حذف شد.');

        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در حذف کاربر: ' . $e->getMessage());
        }
    }

    /**
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user->update([
                'is_active' => $request->is_active,
            ]);

            $status = $request->is_active ? 'فعال' : 'غیرفعال';
            $message = "وضعیت کاربر با موفقیت به «{$status}» تغییر یافت.";

            Log::info('User status updated', [
                'user_id' => $user->id,
                'status' => $status,
                'updated_by' => auth()->id()
            ]);

            return redirect()->back()
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to update user status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در تغییر وضعیت کاربر: ' . $e->getMessage());
        }
    }

    /**
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'رمز عبور جدید الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور مطابقت ندارد.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
            ]);

            Log::info('User password reset', [
                'user_id' => $user->id,
                'reset_by' => auth()->id()
            ]);

            return redirect()->back()
                ->with('success', 'رمز عبور کاربر با موفقیت بازنشانی شد.');

        } catch (\Exception $e) {
            Log::error('Failed to reset user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در بازنشانی رمز عبور: ' . $e->getMessage());
        }
    }

    /**
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $roles = $request->input('roles', []);
            $user->roles()->sync($roles);

            Log::info('User roles updated', [
                'user_id' => $user->id,
                'roles' => $roles,
                'updated_by' => auth()->id()
            ]);

            return redirect()->back()
                ->with('success', 'نقش‌های کاربر با موفقیت بروزرسانی شد.');

        } catch (\Exception $e) {
            Log::error('Failed to sync user roles', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در بروزرسانی نقش‌های کاربر: ' . $e->getMessage());
        }
    }
}
