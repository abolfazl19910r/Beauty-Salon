<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminPermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->paginate(20);
        $groups = Permission::select('group')->distinct()->pluck('group');

        return view('admin.permissions.index', compact('permissions', 'groups'));
    }

    public function create(): View
    {
        $groups = Permission::select('group')->distinct()->pluck('group');

        return view('admin.permissions.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',
            'label' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'نام فنی دسترسی الزامی است.',
            'name.unique' => 'این دسترسی قبلاً ثبت شده است.',
            'label.required' => 'عنوان نمایشی الزامی است.',
            'group.required' => 'گروه دسترسی الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.permissions.create')
                ->withErrors($validator)
                ->withInput();
        }

        Permission::create($request->all());

        return redirect()->route('admin.permissions.index')
            ->with('success', 'دسترسی با موفقیت ایجاد شد.');
    }

    public function show(Permission $permission): View
    {
        $roles = $permission->roles()->withCount('users')->get();

        return view('admin.permissions.show', compact('permission', 'roles'));
    }

    public function edit(Permission $permission): View
    {
        $groups = Permission::select('group')->distinct()->pluck('group');

        return view('admin.permissions.edit', compact('permission', 'groups'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
            'label' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'نام فنی دسترسی الزامی است.',
            'name.unique' => 'این دسترسی قبلاً ثبت شده است.',
            'label.required' => 'عنوان نمایشی الزامی است.',
            'group.required' => 'گروه دسترسی الزامی است.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.permissions.edit', $permission)
                ->withErrors($validator)
                ->withInput();
        }

        $permission->update($request->all());

        return redirect()->route('admin.permissions.show', $permission)
            ->with('success', 'دسترسی با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $criticalPermissions = [
            'access_admin_panel',
            'manage-roles',
            'manage-settings',
        ];

        if (in_array($permission->name, $criticalPermissions)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'این دسترسی حیاتی است و نمی‌توان آن را حذف کرد.');
        }

        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'دسترسی با موفقیت حذف شد.');
    }

    public function filter(Request $request): View
    {
        $query = Permission::query();

        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('group')->orderBy('name')->paginate(20);
        $groups = Permission::select('group')->distinct()->pluck('group');

        return view('admin.permissions.index', compact('permissions', 'groups'));
    }
}
