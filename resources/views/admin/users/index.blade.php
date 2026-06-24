@extends('layouts.admin')
@section('title', 'مدیریت کاربران')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    مدیریت کاربران
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">لیست کاربران سیستم و مدیریت آنها</p>
            </div>
            @permission('create-users')
            <a href="{{ route('admin.users.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white"
               style="background:var(--admin-accent);"
               onmouseover="this.style.background='var(--admin-accent-hover)'"
               onmouseout="this.style.background='var(--admin-accent)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن کاربر
            </a>
            @endpermission
        </div>

        {{-- فیلترها --}}
        <div class="rounded-xl p-4 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-40">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">جستجو</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none" style="color:var(--admin-text-light);">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="نام، موبایل یا ایمیل..."
                               class="w-full text-sm rounded-lg px-3 py-2 pr-9 outline-none transition"
                               style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                               onfocus="this.style.borderColor='var(--admin-accent)'"
                               onblur="this.style.borderColor='var(--admin-border)'">
                    </div>
                </div>
                <div class="min-w-36">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">نقش</label>
                    <select name="role" class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                            style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        <option value="">همه نقش‌ها</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>{{ $role->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-36">
                    <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">وضعیت</label>
                    <select name="status" class="w-full text-sm rounded-lg px-3 py-2 outline-none"
                            style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                        <option value="">همه</option>
                        <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">جستجو</button>
                    @if(request('search') || request('role') || request('status'))
                        <a href="{{ route('admin.users.index') }}"
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">پاک</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- جدول --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">کاربر</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">شماره موبایل</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">نقش‌ها</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ ثبت‌نام</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                         style="background:var(--admin-accent); color:#fff;">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium" style="color:var(--admin-text);">{{ $user->name }}</p>
                                        <p class="text-xs" style="color:var(--admin-text-light);">{{ $user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm" dir="ltr" style="color:var(--admin-text-dim);">{{ $user->phone }}</td>
                            <td class="px-4 py-3">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium mr-1"
                                          style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ $role->label }}</span>
                                @empty
                                    <span class="text-xs" style="color:var(--admin-text-light);">بدون نقش</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#F0FDF4; color:#166534;">فعال</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="background:#FEF2F2; color:#991B1B;">غیرفعال</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs persian-number" style="color:var(--admin-text-dim);">
                                {{ verta($user->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.users.show', $user) }}" title="مشاهده"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:var(--admin-accent);"
                                       onmouseover="this.style.background='var(--admin-accent-light)'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @permission('edit-users')
                                    <a href="{{ route('admin.users.edit', $user) }}" title="ویرایش"
                                       class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                       style="color:#7C3AED;"
                                       onmouseover="this.style.background='#F5F3FF'"
                                       onmouseout="this.style.background=''">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    {{-- تغییر وضعیت --}}
                                    <form action="{{ route('admin.users.status.update', $user) }}" method="POST" class="inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                        <button type="submit" title="{{ $user->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                                class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                style="color:{{ $user->is_active ? '#DC2626' : '#16A34A' }};"
                                                onmouseover="this.style.background='{{ $user->is_active ? '#FEF2F2' : '#F0FDF4' }}'"
                                                onmouseout="this.style.background=''">
                                            @if($user->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    @endpermission
                                    @permission('delete-users')
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" title="حذف"
                                                data-confirm-delete data-confirm-message="آیا از حذف {{ $user->name }} اطمینان دارید؟"
                                                class="w-7 h-7 rounded flex items-center justify-center transition-colors"
                                                style="color:#DC2626;"
                                                onmouseover="this.style.background='#FEF2F2'"
                                                onmouseout="this.style.background=''">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endpermission
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">
                                هیچ کاربری یافت نشد
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
