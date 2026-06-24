@extends('layouts.admin')
@section('title', 'افزودن کاربر جدید')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
        .form-error { color:#DC2626; font-size:0.78rem; margin-top:4px; }
        .role-check { display:flex; align-items:center; gap:8px; padding:7px 10px; border-radius:8px; cursor:pointer; font-size:0.875rem; }
        .role-check:hover { background:var(--admin-accent-light); }
        .role-check input[type=checkbox] { accent-color:var(--admin-accent); width:15px; height:15px; flex-shrink:0; }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                افزودن کاربر جدید
            </h1>
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- فرم اصلی --}}
            <div class="lg:col-span-2">
                <form action="{{ route('admin.users.store') }}" method="POST" id="user-form">
                    @csrf
                    <div class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات شخصی</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">نام و نام خانوادگی <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="نام کامل کاربر">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">شماره موبایل <span style="color:#DC2626;">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-input" dir="ltr" required placeholder="09123456789">
                                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="text-xs mt-1" style="color:var(--admin-text-light);">فرمت: 09123456789</p>
                            </div>
                            <div>
                                <label class="form-label">ایمیل (اختیاری)</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-input" dir="ltr" placeholder="example@domain.com">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">رمز عبور <span style="color:#DC2626;">*</span></label>
                                <input type="password" name="password" class="form-input" dir="ltr" required placeholder="حداقل ۸ کاراکتر">
                                @error('password') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تنظیمات دسترسی</h2>
                        <div class="flex flex-wrap gap-4 mb-4">
                            <label class="role-check">
                                <input type="checkbox" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}
                                style="accent-color:var(--admin-accent); width:15px; height:15px;">
                                <span style="color:var(--admin-text);">دسترسی مدیریت</span>
                            </label>
                            <label class="role-check">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                style="accent-color:#16A34A; width:15px; height:15px;">
                                <span style="color:var(--admin-text);">حساب فعال</span>
                            </label>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-medium mb-2" style="color:var(--admin-text-dim);">نقش‌ها:</p>
                            @foreach($roles as $role)
                                <label class="role-check">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                           {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                           style="accent-color:var(--admin-accent); width:15px; height:15px;">
                                    <span style="color:var(--admin-text);">{{ $role->label }}</span>
                                    <span class="text-xs mr-auto" style="color:var(--admin-text-light);">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-5 p-4 rounded-xl" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white"
                                style="background:var(--admin-accent);"
                                onmouseover="this.style.background='var(--admin-accent-hover)'"
                                onmouseout="this.style.background='var(--admin-accent)'">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                            </svg>
                            ذخیره کاربر
                        </button>
                        <a href="{{ route('admin.users.index') }}"
                           class="px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                           style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
                           onmouseover="this.style.background='var(--admin-border)'"
                           onmouseout="this.style.background='var(--admin-accent-light)'">انصراف</a>
                    </div>
                </form>
            </div>

            {{-- راهنما --}}
            <div class="space-y-4">
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-3" style="color:var(--admin-text);">راهنما</h2>
                    <div class="space-y-3 text-sm" style="color:var(--admin-text-dim);">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#3B82F6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>شماره موبایل باید یکتا و به فرمت ۱۱ رقمی باشد.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#3B82F6;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <span>رمز عبور حداقل ۸ کاراکتر باشد.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:#F59E0B;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>دسترسی مدیریت به کاربر اجازه ورود به پنل ادمین را می‌دهد.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
