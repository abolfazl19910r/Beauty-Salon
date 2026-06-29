@extends('layouts.admin')
@section('title', 'مدیریت نقش‌ها')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">مدیریت نقش‌ها</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">مدیریت نقش‌ها و دسترسی‌های کاربران سیستم</p>
            </div>
            @permission('manage-roles')
            <a href="{{ route('admin.roles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg"
               style="background:var(--admin-accent)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                افزودن نقش جدید
            </a>
            @endpermission
        </div>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            @if($roles->isEmpty())
                <div class="py-16 text-center" style="color:var(--admin-text-dim)">
                    <svg class="w-14 h-14 mx-auto mb-4" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <p class="mb-4">هیچ نقشی تعریف نشده است</p>
                    @permission('manage-roles')
                    <a href="{{ route('admin.roles.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white rounded-lg"
                       style="background:var(--admin-accent)">ایجاد اولین نقش</a>
                    @endpermission
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr style="background:var(--admin-bg)">
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">عنوان نقش</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">نام فنی</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">تعداد کاربران</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">تاریخ ایجاد</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:var(--admin-border)">
                        @foreach($roles as $role)
                            <tr class="transition-colors" onmouseover="this.style.background='var(--admin-accent-light)'" onmouseout="this.style.background=''">
                                <td class="py-4 px-6 font-medium" style="color:var(--admin-text)">{{ $role->label }}</td>
                                <td class="py-4 px-6 font-mono text-sm" dir="ltr" style="color:var(--admin-text-dim)">{{ $role->name }}</td>
                                <td class="py-4 px-6">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background:var(--admin-accent-light);color:var(--admin-accent)">
                            {{ $role->users_count }}
                        </span>
                                </td>
                                <td class="py-4 px-6 text-sm" dir="ltr" style="color:var(--admin-text-dim)">
                                    {{ verta($role->created_at)->format('Y/m/d H:i') }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.roles.show', $role) }}" title="نمایش"
                                           class="p-1.5 rounded-lg transition-colors" style="color:var(--admin-accent);background:var(--admin-accent-light)">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        @permission('manage-roles')
                                        <a href="{{ route('admin.roles.edit', $role) }}" title="ویرایش"
                                           class="p-1.5 rounded-lg transition-colors" style="color:#d97706;background:#fffbeb">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <a href="{{ route('admin.roles.assign.form', $role) }}" title="اختصاص به کاربر"
                                           class="p-1.5 rounded-lg transition-colors" style="color:#16a34a;background:#f0fdf4">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                                        </a>
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف نقش «{{ $role->label }}» اطمینان دارید؟"
                                                    class="p-1.5 rounded-lg transition-colors" style="color:#dc2626;background:#fef2f2">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                        @endpermission
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4" style="border-top:1px solid var(--admin-border)">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
