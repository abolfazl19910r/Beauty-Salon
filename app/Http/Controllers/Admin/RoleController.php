<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    public function index()
    {
        $roles = Role::withCount('users')->latest()->paginate(10);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'label' => 'required|string|max:255',
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش جدید با موفقیت ایجاد شد.');
    }

    public function show(Role $role)
    {
        $users = $role->users()->paginate(10);
        return view('admin.roles.show', compact('role', 'users'));
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'label' => 'required|string|max:255',
        ]);

        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت بروزرسانی شد.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', 'این نقش به کاربرانی اختصاص داده شده است و نمی‌توان آن را حذف کرد.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    public function assignUserForm(Role $role)
    {
        $users = User::whereDoesntHave('roles', function($query) use ($role) {
            $query->where('roles.id', $role->id);
        })->get();

        return view('admin.roles.assign', compact('role', 'users'));
    }

    public function assignUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role->assignToUser($user);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش به کاربر اختصاص داده شد.');
    }

    public function removeUser(Role $role, User $user)
    {
        $role->removeFromUser($user);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'نقش از کاربر حذف شد.');
    }
}
