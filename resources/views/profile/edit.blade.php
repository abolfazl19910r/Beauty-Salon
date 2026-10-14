@extends('layouts.app')

@section('title', 'ویرایش پروفایل')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('profile.show') }}"
               class="w-9 h-9 rounded-xl bg-m-card border border-m-gold/15 flex items-center justify-center
                  text-m-tx/60 hover:text-m-gold2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-0.5">حساب کاربری</p>
                <h1 class="text-2xl font-bold text-m-gold2"
                    style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">ویرایش پروفایل</h1>
            </div>
        </div>

        <div class="space-y-5">
            {{-- Profile information --}}
            <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-m-gold/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h2 class="font-bold text-sm text-m-gold2">اطلاعات پروفایل</h2>
                    <p class="text-xs text-m-tx/45 mr-auto">بروزرسانی نام و شماره موبایل</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Change password --}}
            <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-m-gold/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <h2 class="font-bold text-sm text-m-gold2">تغییر رمز عبور</h2>
                    <p class="text-xs text-m-tx/45 mr-auto">برای امنیت از رمز قوی استفاده کنید</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- پیامک‌های پیگیری و پیشنهاد سالن (۲۰۲۶-۱۰-۱۴) --}}
            @if (auth()->user()->user_type === 'customer')
            <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden">
                <form method="post" action="{{ route('profile.marketing-sms') }}" class="p-6 flex items-center justify-between gap-4">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="marketing_sms" value="0">
                    <label for="marketing_sms" class="flex-1 cursor-pointer">
                        <h2 class="font-bold text-sm text-m-gold2">پیامک‌های پیگیری و پیشنهاد سالن</h2>
                        <p class="text-xs text-m-tx/45 mt-1 leading-6">مثلاً وقتی مدتی سر نزده‌اید، با کد تخفیف. پیامک‌های تأیید و یادآوری نوبت‌هایتان همیشه می‌رسد.</p>
                        @if (session('status') === 'marketing-sms-updated')
                            <p class="text-xs text-m-ok mt-1">ذخیره شد</p>
                        @endif
                    </label>
                    <input id="marketing_sms" type="checkbox" name="marketing_sms" value="1" onchange="this.form.submit()"
                           class="w-5 h-5 rounded" style="accent-color:rgb(var(--m-gold))" @checked(auth()->user()->marketing_sms_opted_out_at === null)>
                    <noscript><button type="submit" class="text-xs text-m-gold2">ذخیره</button></noscript>
                </form>
            </div>
            @endif

            {{-- Security dashboard --}}
            <a href="{{ route('security.dashboard') }}"
               class="block bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden hover:border-m-gold/25 transition-colors">
                <div class="p-6 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        <div>
                            <h2 class="font-bold text-sm text-m-gold2">امنیت حساب کاربری</h2>
                            <p class="text-xs text-m-tx/45">امتیاز امنیتی، نشست‌های فعال و فعالیت‌های اخیر</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-m-tx/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </div>
            </a>

            {{-- Delete account --}}
            {{-- Two-factor authentication --}}
            <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-m-gold/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <h2 class="font-bold text-sm text-m-gold2">احراز هویت دو مرحله‌ای</h2>
                    <p class="text-xs text-m-tx/45 mr-auto">برای استفاده از «پرداخت امن» فعال کنید</p>
                </div>
                <div class="p-6 flex items-center justify-between">
                    <p class="text-sm text-m-tx/60">وضعیت: <span class="{{ auth()->user()->two_factor_enabled ? 'text-m-ok' : 'text-m-tx/50' }} font-semibold">{{ auth()->user()->two_factor_enabled ? 'فعال' : 'غیرفعال' }}</span></p>
                    <a href="{{ route('security.2fa') }}"
                       class="px-5 py-2 rounded-xl text-sm font-semibold border border-m-gold/25 text-m-tx/85 hover:bg-m-tx/5 transition-colors">
                        مدیریت تنظیمات
                    </a>
                </div>
            </div>

            <div class="bg-m-card rounded-2xl border border-m-bad/15 overflow-hidden">
                <div class="px-6 py-4 border-b border-m-bad/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-m-bad" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    <h2 class="font-bold text-sm text-m-bad">حذف حساب کاربری</h2>
                    <p class="text-xs text-m-tx/45 mr-auto">این عملیات برگشت‌پذیر نیست</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
