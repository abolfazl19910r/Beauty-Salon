<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    /**
     *
     * @return View
     */
    public function index(): View
    {
        $roles = Role::withCount('users')->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    /**
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.roles.create');
    }

    /**
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'label' => 'required|string|max:255',
        ], [
            'name.required' => 'نام فنی نقش الزامی است.',
            'name.unique' => 'نام فنی نقش تکراری است.',
            'label.required' => 'عنوان نمایشی نقش الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.roles.create')
                ->withErrors($validator)
                ->withInput();
        }

        Role::create([
            'name' => $request->name,
            'label' => $request->label,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    /**
     *
     * @param Role $role
     * @return View
     */
    public function show(Role $role): View
    {
        $users = $role->users()->paginate(10);
        return view('admin.roles.show', compact('role', 'users'));
    }

    /**
     *
     * @param Role $role
     * @return View
     */
    public function edit(Role $role): View
    {
        return view('admin.roles.edit', compact('role'));
    }

    /**
     *
     * @param Request $request
     * @param Role $role
     * @return RedirectResponse
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'label' => 'required|string|max:255',
        ], [
            'name.required' => 'نام فنی نقش الزامی است.',
            'name.unique' => 'نام فنی نقش تکراری است.',
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

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت بروزرسانی شد.');
    }

    /**
     *
     * @param Role $role
     * @return RedirectResponse
     */
    public function destroy(Role $role): RedirectResponse
    {
        $role->users()->detach();

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    /**
     *
     * @param Role $role
     * @return View
     */
    public function assignForm(Role $role): View
    {
        $users = User::whereDoesntHave('roles', function ($query) use ($role) {
            $query->where('role_id', $role->id);
        })->get();

        return view('admin.roles.assign', compact('role', 'users'));
    }

    /**
     *
     * @param Request $request
     * @param Role $role
     * @return RedirectResponse
     */
    public function assign(Request $request, Role $role): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ], [
            'user_id.required' => 'انتخاب کاربر الزامی است.',
            'user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.roles.assign.form', $role)
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::findOrFail($request->user_id);
        $user->assignRole($role);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت به کاربر اختصاص داده شد.');
    }

    /**
     *
     * @param Role $role
     * @param User $user
     * @return RedirectResponse
     */
    public function removeUser(Role $role, User $user): RedirectResponse
    {
        $user->removeRole($role);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش با موفقیت از کاربر حذف شد.');
    }
}
