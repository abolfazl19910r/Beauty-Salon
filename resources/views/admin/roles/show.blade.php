@extends('layouts.admin')
@section('title', 'نمایش نقش')

@section('content')
    <div class="container px-6 mx-auto">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold" style="color:var(--admin-text)">{{ $role->label }}</h1>
                <p class="text-sm mt-1" style="color:var(--admin-text-dim)">جزئیات نقش و کاربران دارای این نقش</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.roles.edit', $role) }}"
                   class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                   style="background:var(--admin-accent)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    ویرایش
                </a>
                <a href="{{ route('admin.roles.index') }}"
                   class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
                   style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">

            {{-- Role information --}}
            <div class="md:col-span-2 rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold mb-4" style="color:var(--admin-text)">اطلاعات نقش</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">عنوان نمایشی</span>
                        <span class="font-medium" style="color:var(--admin-text)">{{ $role->label }}</span>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">نام فنی</span>
                        <span class="font-mono" dir="ltr" style="color:var(--admin-text)">{{ $role->name }}</span>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">تاریخ ایجاد</span>
                        <span dir="ltr" style="color:var(--admin-text)">{{ verta($role->created_at)->formatDatetime() }}</span>
                    </div>
                    <div>
                        <span class="block mb-1" style="color:var(--admin-text-dim)">بروزرسانی</span>
                        <span dir="ltr" style="color:var(--admin-text)">{{ verta($role->updated_at)->formatDatetime() }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="rounded-xl p-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold mb-4" style="color:var(--admin-text)">اقدامات</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.roles.edit', $role) }}"
                       class="flex items-center gap-2 p-3 rounded-lg text-sm transition-colors"
                       style="background:#fffbeb;color:#d97706">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        ویرایش نقش
                    </a>
                    <a href="{{ route('admin.roles.assign.form', $role) }}"
                       class="flex items-center gap-2 p-3 rounded-lg text-sm transition-colors"
                       style="background:#f0fdf4;color:#16a34a">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        اختصاص به کاربر
                    </a>
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف این نقش اطمینان دارید؟"
                                class="flex items-center gap-2 w-full p-3 rounded-lg text-sm transition-colors"
                                style="background:#fef2f2;color:#dc2626">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            حذف نقش
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Accesses --}}
        <div class="rounded-xl p-6 mb-6" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <h2 class="text-base font-semibold mb-4 flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-5 h-5" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                دسترسی‌های این نقش
            </h2>
            @if($permissions->isEmpty())
                <p class="text-sm" style="color:var(--admin-text-dim)">هیچ دسترسی به این نقش اختصاص داده نشده است</p>
            @else
                @foreach($permissions as $groupName => $groupPermissions)
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold mb-2 pb-1" style="color:var(--admin-text);border-bottom:1px solid var(--admin-border)">
                            {{ $groupName ?? 'عمومی' }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($groupPermissions as $permission)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium"
                                      style="background:var(--admin-accent-light);color:var(--admin-accent)">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $permission->label }}
                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Users --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <div class="px-6 py-4 flex items-center justify-between" style="border-bottom:1px solid var(--admin-border)">
                <h2 class="text-base font-semibold" style="color:var(--admin-text)">
                    کاربران دارای این نقش
                    <span class="mr-2 px-2 py-0.5 text-xs rounded-full" style="background:var(--admin-accent-light);color:var(--admin-accent)">{{ $users->total() }}</span>
                </h2>
                <a href="{{ route('admin.roles.assign.form', $role) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs text-white rounded-lg"
                   style="background:var(--admin-accent)">
                    + افزودن کاربر
                </a>
            </div>

            @if($users->isEmpty())
                <div class="py-12 text-center" style="color:var(--admin-text-dim)">
                    <p>هیچ کاربری با این نقش یافت نشد</p>
                </div>
            @else
                <table class="w-full">
                    <thead>
                    <tr style="background:var(--admin-bg)">
                        <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">نام</th>
                        <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">شماره موبایل</th>
                        <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">ایمیل</th>
                        <th class="py-3 px-6 text-right text-xs font-semibold" style="color:var(--admin-text-dim)">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--admin-border)">
                    @foreach($users as $user)
                        <tr class="transition-colors" onmouseover="this.style.background='var(--admin-accent-light)'" onmouseout="this.style.background=''">
                            <td class="py-4 px-6 font-medium text-sm" style="color:var(--admin-text)">{{ $user->name }}</td>
                            <td class="py-4 px-6 text-sm" dir="ltr" style="color:var(--admin-text-dim)">{{ $user->phone }}</td>
                            <td class="py-4 px-6 text-sm" style="color:var(--admin-text-dim)">{{ $user->email }}</td>
                            <td class="py-4 px-6">
                                <form action="{{ route('admin.roles.remove.user', [$role, $user]) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-confirm-delete data-confirm-message="آیا از حذف نقش از این کاربر اطمینان دارید؟"
                                            class="inline-flex items-center gap-1 text-xs px-3 py-1.5 rounded-lg transition-colors"
                                            style="color:#dc2626;background:#fef2f2">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        حذف نقش
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="p-4" style="border-top:1px solid var(--admin-border)">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
