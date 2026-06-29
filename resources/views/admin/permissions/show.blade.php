@extends('layouts.admin')
@section('title', 'نمایش دسترسی')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">{{ $permission->label }}</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">جزئیات دسترسی</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.permissions.edit', $permission) }}"
                   class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                   style="background:var(--admin-accent)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    ویرایش
                </a>
                <a href="{{ route('admin.permissions.index') }}"
                   class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
                   style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            {{-- Information --}}
            <div class="md:col-span-2 rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold" style="color:var(--admin-text)">اطلاعات دسترسی</h2>
                    <span class="px-3 py-1 text-xs rounded-full font-medium" style="background:#faf5ff;color:#7c3aed">
                    {{ $permission->group }}
                </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">عنوان نمایشی</span>
                        <span class="font-medium text-base" style="color:var(--admin-text)">{{ $permission->label }}</span>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">نام فنی</span>
                        <code class="px-2 py-1 rounded text-sm font-mono" dir="ltr"
                              style="background:var(--admin-bg);color:var(--admin-text);border:1px solid var(--admin-border)">
                            {{ $permission->name }}
                        </code>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">تاریخ ایجاد</span>
                        <span dir="ltr" style="color:var(--admin-text)">{{ verta($permission->created_at)->formatDatetime() }}</span>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">بروزرسانی</span>
                        <span dir="ltr" style="color:var(--admin-text)">{{ verta($permission->updated_at)->formatDatetime() }}</span>
                    </div>
                </div>
                @if($permission->description)
                    <div class="mt-4 p-4 rounded-lg" style="background:var(--admin-bg);border:1px solid var(--admin-border)">
                        <span class="block text-xs mb-1" style="color:var(--admin-text-dim)">توضیحات</span>
                        <p class="text-sm" style="color:var(--admin-text)">{{ $permission->description }}</p>
                    </div>
                @endif
            </div>

            {{-- Actions + Statistics --}}
            <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold mb-4" style="color:var(--admin-text)">اقدامات</h2>
                <div class="space-y-2 mb-6">
                    <a href="{{ route('admin.permissions.edit', $permission) }}"
                       class="flex items-center gap-2 p-3 rounded-lg text-sm"
                       style="background:#fffbeb;color:#d97706">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        ویرایش دسترسی
                    </a>
                    <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف این دسترسی اطمینان دارید؟"
                                class="flex items-center gap-2 w-full p-3 rounded-lg text-sm"
                                style="background:#fef2f2;color:#dc2626">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            حذف دسترسی
                        </button>
                    </form>
                </div>

                <div class="pt-4" style="border-top:1px solid var(--admin-border)">
                    <h3 class="text-xs font-semibold mb-3" style="color:var(--admin-text-dim)">آمار</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim)">تعداد نقش‌ها</span>
                            <span class="font-bold" style="color:var(--admin-accent)">{{ $roles->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--admin-text-dim)">کل کاربران</span>
                            <span class="font-bold" style="color:#16a34a">{{ $roles->sum('users_count') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Roles with this access --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold" style="color:var(--admin-text)">
                    نقش‌های دارای این دسترسی
                    <span class="mr-2 px-2 py-0.5 text-xs rounded-full" style="background:var(--admin-accent-light);color:var(--admin-accent)">
                    {{ $roles->count() }}
                </span>
                </h2>
            </div>

            @if($roles->isEmpty())
                <div class="py-12 text-center" style="color:var(--admin-text-dim)">
                    <p class="mb-2">هیچ نقشی این دسترسی را ندارد</p>
                    <p class="text-xs">از بخش مدیریت نقش‌ها، این دسترسی را به نقش‌ها اختصاص دهید</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                    @foreach($roles as $role)
                        <div class="rounded-xl p-4 transition-all"
                             style="border:1px solid var(--admin-border)"
                             onmouseover="this.style.borderColor='var(--admin-accent)';this.style.background='var(--admin-accent-light)'"
                             onmouseout="this.style.borderColor='var(--admin-border)';this.style.background=''">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-sm" style="color:var(--admin-text)">{{ $role->label }}</h3>
                                    <p class="text-xs font-mono mt-1" dir="ltr" style="color:var(--admin-text-dim)">{{ $role->name }}</p>
                                </div>
                                <a href="{{ route('admin.roles.show', $role) }}"
                                   class="p-1.5 rounded-lg" style="color:var(--admin-accent);background:var(--admin-accent-light)">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </div>
                            <div class="mt-3 pt-3 text-xs flex items-center gap-1" style="border-top:1px solid var(--admin-border);color:var(--admin-text-dim)">
                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                {{ $role->users_count }} کاربر
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
@endsection
