@extends('layouts.admin')
@section('title', 'ویرایش پروفایل')

@section('content')
    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="rounded-t-xl px-6 py-4 flex items-center gap-3" style="background:var(--admin-accent)">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0"
                 style="background:rgba(255,255,255,.2);color:#fff">
                {{ mb_substr($user->name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-lg font-bold text-white">ویرایش پروفایل</h1>
                <p class="text-xs" style="color:rgba(255,255,255,.7)">{{ $user->email }}</p>
            </div>
        </div>

        <div class="rounded-b-xl p-6 space-y-8" style="background:var(--admin-surface);border:1px solid var(--admin-border);border-top:none">

            {{-- Information form --}}
            <div>
                <h2 class="text-base font-semibold mb-4 flex items-center gap-2" style="color:var(--admin-text)">
                    <svg class="w-5 h-5" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    اطلاعات شخصی
                </h2>
                <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">نام</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('name')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">ایمیل</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required dir="ltr"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('email')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">شماره موبایل</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" placeholder="09123456789"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('phone')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg"
                                style="background:var(--admin-accent)">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            <hr style="border-color:var(--admin-border)">

            {{-- Password form --}}
            <div>
                <h2 class="text-base font-semibold mb-4 flex items-center gap-2" style="color:var(--admin-text)">
                    <svg class="w-5 h-5" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    تغییر رمز عبور
                </h2>
                <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">رمز عبور فعلی</label>
                            <input type="password" name="current_password" required dir="ltr"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('current_password')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">رمز عبور جدید</label>
                            <input type="password" name="password" id="password" required dir="ltr"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                            @error('password')<p class="text-xs mt-1 text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" style="color:var(--admin-text-dim)">تکرار رمز جدید</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required dir="ltr"
                                   class="w-full rounded-lg px-3 py-2 text-sm"
                                   style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        </div>
                    </div>

                    <p class="text-xs" style="color:var(--admin-text-light)">رمز عبور باید حداقل ۸ کاراکتر باشد.</p>

                    <div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white rounded-lg"
                                style="background:var(--admin-accent)">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            بروزرسانی رمز عبور
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('password_confirmation')?.addEventListener('input', function () {
            var p = document.getElementById('password');
            this.setCustomValidity(p && p.value !== this.value ? 'رمزهای عبور مطابقت ندارند' : '');
        });
    </script>
@endpush
