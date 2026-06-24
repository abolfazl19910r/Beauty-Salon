@extends('layouts.admin')
@section('title', 'ویرایش کاربر')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .role-check { display:flex; align-items:center; gap:8px; padding:7px 10px; border-radius:8px; cursor:pointer; font-size:0.875rem; }
        .role-check:hover { background:var(--admin-accent-light); }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">ویرایش کاربر</h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $user->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-accent);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    مشاهده
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium"
                   style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                   onmouseover="this.style.background='var(--admin-border)'"
                   onmouseout="this.style.background='var(--admin-accent-light)'">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    بازگشت
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- فرم اصلی --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- اطلاعات اصلی --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات اصلی</h2>
                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">نام و نام خانوادگی <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">شماره موبایل <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input" dir="ltr" required>
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">ایمیل</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" dir="ltr">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">تنظیمات</label>
                                <div class="flex flex-wrap gap-3 pt-1">
                                    <label class="role-check">
                                        <input type="checkbox" name="is_admin" value="1"
                                               {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                                               style="accent-color:var(--admin-accent); width:15px; height:15px;">
                                        <span style="color:var(--admin-text);">دسترسی مدیریت</span>
                                    </label>
                                    <label class="role-check">
                                        <input type="checkbox" name="is_active" value="1"
                                               {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                               style="accent-color:#16A34A; width:15px; height:15px;">
                                        <span style="color:var(--admin-text);">حساب فعال</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label mb-2">نقش‌ها</label>
                            <div class="grid grid-cols-2 gap-1 p-3 rounded-lg" style="border:1px solid var(--admin-border); background:var(--admin-bg);">
                                @foreach($roles as $role)
                                    <label class="role-check">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                               {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}
                                               style="accent-color:var(--admin-accent); width:15px; height:15px;">
                                        <span style="color:var(--admin-text);">{{ $role->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('roles') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-between pt-4" style="border-top:1px solid var(--admin-border);">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium text-white"
                                    style="background:var(--admin-accent);"
                                    onmouseover="this.style.background='var(--admin-accent-hover)'"
                                    onmouseout="this.style.background='var(--admin-accent)'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                                </svg>
                                ذخیره تغییرات
                            </button>
                            <a href="{{ route('admin.users.index') }}"
                               class="px-5 py-2.5 rounded-lg text-sm font-medium"
                               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                               onmouseover="this.style.background='var(--admin-border)'"
                               onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                        </div>
                    </form>
                </div>

                {{-- تغییر رمز عبور --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تغییر رمز عبور</h2>
                    <form action="{{ route('admin.users.password.reset', $user) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">رمز عبور جدید</label>
                                <input type="password" name="password" class="form-input" dir="ltr" placeholder="حداقل ۸ کاراکتر">
                                @error('password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">تکرار رمز عبور</label>
                                <input type="password" name="password_confirmation" class="form-input" dir="ltr" placeholder="تکرار رمز عبور">
                            </div>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium text-white"
                                style="background:#7C3AED;"
                                onmouseover="this.style.background='#6D28D9'"
                                onmouseout="this.style.background='#7C3AED'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            تغییر رمز عبور
                        </button>
                    </form>
                </div>
            </div>

            {{-- ستون عملیات --}}
            <div class="space-y-4">
                {{-- اطلاعات فعلی --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
                             style="background:var(--admin-accent); color:#fff;">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold text-sm" style="color:var(--admin-text);">{{ $user->name }}</p>
                            <div class="flex gap-1 mt-1">
                                @if($user->is_active)
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background:#F0FDF4; color:#166534;">فعال</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background:#FEF2F2; color:#991B1B;">غیرفعال</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-xs space-y-1.5" style="color:var(--admin-text-dim);">
                        <div class="flex justify-between">
                            <span>موبایل:</span>
                            <span dir="ltr" style="color:var(--admin-text);">{{ $user->phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>عضویت:</span>
                            <span class="persian-number" style="color:var(--admin-text);">{{ verta($user->created_at)->format('Y/m/d') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>نوبت‌ها:</span>
                            <span class="persian-number font-bold" style="color:var(--admin-accent);">{{ $user->bookings()->count() }}</span>
                        </div>
                    </div>
                </div>

                {{-- عملیات سریع --}}
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-3 pb-2" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">عملیات سریع</h2>
                    <div class="space-y-2">
                        <form action="{{ route('admin.users.status.update', $user) }}" method="POST">
                            @csrf @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm transition-colors"
                                    style="background:{{ $user->is_active ? '#FEF2F2' : '#F0FDF4' }}; color:{{ $user->is_active ? '#991B1B' : '#166534' }};">
                                @if($user->is_active)
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    غیرفعال کردن
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    فعال کردن
                                @endif
                            </button>
                        </form>
                        @permission('delete-users')
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="button" data-confirm-delete data-confirm-message="آیا از حذف {{ $user->name }} اطمینان دارید؟"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm"
                                    style="background:#FEF2F2; color:#991B1B;">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                                حذف کاربر
                            </button>
                        </form>
                        @endpermission
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
