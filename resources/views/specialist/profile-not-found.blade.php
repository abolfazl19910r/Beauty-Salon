@extends('layouts.specialist')

@section('title', 'پروفایل ناقص')

@section('content')
    <div class="fade-in flex items-center justify-center min-h-[70vh]">
        <div class="specialist-card max-w-md w-full p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h2 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa mb-4">پروفایل شما ناقص است</h2>

            <p class="text-[var(--specialist-text-dim)] mb-2">کاربر گرامی {{ auth()->user()->name }}</p>
            <p class="text-[var(--specialist-text-dim)] mb-6">
                شما به عنوان متخصص در سیستم ثبت شده‌اید، اما اطلاعات پروفایل شما هنوز تکمیل نشده است.
            </p>

            <div class="rounded-lg p-4 mb-6 text-right" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                <p class="text-sm text-[var(--specialist-text-dim)]">
                    <strong class="text-[var(--specialist-plum-light)]">توجه:</strong> لطفاً با مدیر سیستم تماس بگیرید تا اطلاعات شما در بخش متخصصین ثبت شود.
                </p>
            </div>

            <div class="space-y-3">
                <a href="{{ route('dashboard') }}" class="specialist-cta block w-full px-6 py-3 rounded-lg transition-opacity hover:opacity-90 font-bold">
                    بازگشت به داشبورد
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-6 py-3 rounded-lg transition text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]" style="border: 1px solid var(--specialist-border);">
                        خروج از سیستم
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
