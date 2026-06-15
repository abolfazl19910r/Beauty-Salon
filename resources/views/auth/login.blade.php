<?php
use Illuminate\Support\Facades\Route;
/** @var \Illuminate\Support\ViewErrorBag $errors */
?>
<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">ورود به راستا</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">برای رزرو نوبت وارد شوید</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- شماره موبایل --}}
        <div>
            <x-input-label for="phone" :value="__('شماره موبایل')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                </div>
                <x-text-input id="phone" class="pr-10" type="text" name="phone"
                              :value="old('phone')" required autofocus autocomplete="phone"
                              dir="ltr" placeholder="09123456789" />
            </div>
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        {{-- رمز عبور --}}
        <div>
            <x-input-label for="password" :value="__('رمز عبور')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <x-text-input id="password" class="pr-10" type="password" name="password"
                              required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        {{-- به خاطر سپردن + فراموشی رمز --}}
        <div class="flex items-center justify-between text-sm">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="w-4 h-4 rounded border-[#C9A24B]/30 bg-white/5 text-[#C9A24B] focus:ring-[#C9A24B]/30">
                <span class="text-[#F8F3E9]/70">مرا به خاطر بسپار</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[#E6CD8A] hover:text-[#C9A24B] transition-colors">
                    فراموشی رمز عبور
                </a>
            @endif
        </div>

        {{-- دکمه ورود --}}
        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            ورود به حساب
        </button>

        <p class="text-center text-sm text-[#F8F3E9]/60">
            حساب کاربری ندارید؟
            <a href="{{ route('register') }}" class="text-[#E6CD8A] hover:text-[#C9A24B] transition-colors font-medium mr-1">
                ثبت نام کنید
            </a>
        </p>
    </form>
</x-guest-layout>
