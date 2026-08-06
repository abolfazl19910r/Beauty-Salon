@extends('layouts.admin')
@section('title', 'تنظیمات امنیتی')

@section('content')
    <div class="fade-in max-w-lg">
        <div class="mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                تنظیمات امنیتی
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                این تنظیمات مستقیماً در محاسبه‌ی «امتیاز امنیتی» داشبورد امنیت هر کاربر اثر دارند.
            </p>
            <div class="flex gap-2 mt-3">
                <a href="{{ route('admin.security.logs') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    لاگ‌های خام
                </a>
                <a href="{{ route('admin.security.users') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                   style="background-color: var(--admin-accent-light); color:var(--admin-accent);">
                    وضعیت کاربران
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg text-sm" style="background-color: rgba(34,197,94,0.1); color: #16A34A;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.security.settings.update') }}"
              class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf

            <label class="block text-sm font-medium mb-1.5" style="color:var(--admin-text);">مهلت تازگی رمز عبور (روز)</label>
            <p class="text-xs mb-2" style="color:var(--admin-text-dim);">
                اگر کاربری رمز عبورش را در این بازه تغییر داده باشد، امتیاز امنیتی بیشتری می‌گیرد.
            </p>
            <input type="text" inputmode="numeric" name="password_expiry_days" value="{{ old('password_expiry_days', $settings->password_expiry_days) }}"
                   class="w-full rounded-lg px-3 py-2 text-sm persian-number" dir="ltr"
                   style="background:var(--admin-bg); border:1px solid var(--admin-border); color:var(--admin-text);">
            @error('password_expiry_days')
                <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p>
            @enderror

            <button type="submit" class="mt-5 px-5 py-2.5 rounded-lg text-sm font-medium text-white" style="background-color: var(--admin-accent);">
                ذخیره‌ی تغییرات
            </button>
        </form>
    </div>
@endsection
