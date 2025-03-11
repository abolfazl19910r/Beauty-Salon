<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full p-6 bg-white rounded-lg shadow-lg hover-shadow">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-pink-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-800">تایید آدرس ایمیل</h2>
                <p class="text-gray-600 mt-2">ایمیل خود را تایید کنید</p>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg text-sm text-blue-600 mb-6">
                {{ __('ممنون از ثبت‌نام شما! قبل از شروع، لطفاً ایمیل خود را با کلیک بر روی لینکی که برای شما ارسال کردیم تایید کنید. اگر ایمیل را دریافت نکرده‌اید، با کمال میل ایمیل دیگری برای شما ارسال می‌کنیم.') }}
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-6 p-4 bg-green-50 text-green-600 rounded-lg text-sm">
                    {{ __('یک لینک تایید جدید به آدرس ایمیلی که در هنگام ثبت‌نام وارد کرده‌اید ارسال شد.') }}
                </div>
            @endif

            <div class="space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                        {{ __('ارسال مجدد ایمیل تاییدیه') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm text-pink-600 hover:text-pink-500">
                        {{ __('خروج از حساب کاربری') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
