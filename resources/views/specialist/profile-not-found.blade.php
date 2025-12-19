@extends('layouts.specialist')

@section('title', 'پروفایل ناقص')

@section('content')
    <div class="flex items-center justify-center min-h-[70vh]">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-4">پروفایل شما ناقص است</h2>

            <p class="text-gray-600 mb-2">کاربر گرامی {{ auth()->user()->name }}</p>
            <p class="text-gray-600 mb-6">
                شما به عنوان متخصص در سیستم ثبت شده‌اید، اما اطلاعات پروفایل شما هنوز تکمیل نشده است.
            </p>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-right">
                <p class="text-sm text-blue-800">
                    <strong>توجه:</strong> لطفاً با مدیر سیستم تماس بگیرید تا اطلاعات شما در بخش متخصصین ثبت شود.
                </p>
            </div>

            <div class="space-y-3">
                <a href="{{ route('dashboard') }}" class="block w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-lg hover:opacity-90 transition">
                    بازگشت به داشبورد
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        خروج از سیستم
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
