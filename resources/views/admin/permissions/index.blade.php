@extends('layouts.admin')
@section('title', 'مدیریت دسترسی‌ها')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">مدیریت دسترسی‌ها</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">تعریف و مدیریت دسترسی‌های سیستم</p>
            </div>
            <a href="{{ route('admin.permissions.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg"
               style="background:var(--admin-accent)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                افزودن دسترسی جدید
            </a>
        </div>

        {{-- Filter --}}
        <form action="{{ route('admin.permissions.filter') }}" method="GET"
              class="rounded-xl p-4 mb-5 flex flex-wrap gap-3 items-end"
              style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium mb-1" style="color:var(--admin-text-dim)">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="نام یا عنوان..."
                       class="w-full rounded-lg px-3 py-2 text-sm"
                       style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
            </div>
            <div class="w-48">
                <label class="block text-xs font-medium mb-1" style="color:var(--admin-text-dim)">گروه</label>
                <select name="group"
                        class="w-full rounded-lg px-3 py-2 text-sm"
                        style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                    <option value="">همه گروه‌ها</option>
                    @foreach($groups as $group)
                        <option value="{{ $group }}" {{ request('group') == $group ? 'selected' : '' }}>{{ $group }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                        style="background:var(--admin-accent)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    جستجو
                </button>
                @if(request()->hasAny(['search', 'group']))
                    <a href="{{ route('admin.permissions.index') }}"
                       class="inline-flex items-center px-4 py-2 text-sm rounded-lg border"
                       style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                        پاک‌کردن
                    </a>
                @endif
            </div>
        </form>

        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            @if($permissions->isEmpty())
                <div class="py-16 text-center" style="color:var(--admin-text-dim)">
                    <p class="mb-4">هیچ دسترسی تعریف نشده است</p>
                    <a href="{{ route('admin.permissions.create') }}"
                       class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                       style="background:var(--admin-accent)">ایجاد اولین دسترسی</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr style="background:var(--admin-bg)">
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">عنوان</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">نام فنی</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">گروه</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">توضیحات</th>
                            <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:var(--admin-border)">
                        @foreach($permissions as $permission)
                            <tr class="transition-colors" onmouseover="this.style.background='var(--admin-accent-light)'" onmouseout="this.style.background=''">
                                <td class="py-4 px-6 font-medium text-sm" style="color:var(--admin-text)">{{ $permission->label }}</td>
                                <td class="py-4 px-6 font-mono text-xs" dir="ltr" style="color:var(--admin-text-dim)">{{ $permission->name }}</td>
                                <td class="py-4 px-6">
                        <span class="px-2 py-1 text-xs rounded-full font-medium" style="background:#faf5ff;color:#7c3aed">
                            {{ $permission->group }}
                        </span>
                                </td>
                                <td class="py-4 px-6 text-xs max-w-xs truncate" style="color:var(--admin-text-dim)">
                                    {{ $permission->description ?? '—' }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.permissions.show', $permission) }}" title="نمایش"
                                           class="p-1.5 rounded-lg" style="color:var(--admin-accent);background:var(--admin-accent-light)">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <a href="{{ route('admin.permissions.edit', $permission) }}" title="ویرایش"
                                           class="p-1.5 rounded-lg" style="color:#d97706;background:#fffbeb">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </a>
                                        <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف دسترسی «{{ $permission->label }}» اطمینان دارید؟"
                                                    class="p-1.5 rounded-lg" style="color:#dc2626;background:#fef2f2">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4" style="border-top:1px solid var(--admin-border)">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
