@extends('layouts.app')

@section('title', 'ویرایش پروفایل')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('profile.show') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-0.5">حساب کاربری</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">ویرایش پروفایل</h1>
            </div>
        </div>

        <div class="space-y-5">
            {{-- Profile information --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">اطلاعات پروفایل</h2>
                    <p class="text-xs text-[#F8F3E9]/45 mr-auto">بروزرسانی نام و شماره موبایل</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Change password --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <h2 class="font-bold text-sm text-[#E6CD8A]">تغییر رمز عبور</h2>
                    <p class="text-xs text-[#F8F3E9]/45 mr-auto">برای امنیت از رمز قوی استفاده کنید</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Delete account --}}
            <div class="bg-[#2E2117] rounded-2xl border border-red-500/15 overflow-hidden">
                <div class="px-6 py-4 border-b border-red-500/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    <h2 class="font-bold text-sm text-red-400">حذف حساب کاربری</h2>
                    <p class="text-xs text-[#F8F3E9]/45 mr-auto">این عملیات برگشت‌پذیر نیست</p>
                </div>
                <div class="p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
