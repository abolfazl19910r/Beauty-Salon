@extends('layouts.app')

@section('title', 'تشکر از شما')

@section('content')
    <div class="max-w-2xl mx-auto text-center py-16">
        <div class="bg-white rounded-2xl shadow-xl p-12">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full mb-6 animate-bounce">
                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-gray-800 mb-4">
                🎉 نظر شما با موفقیت ثبت شد!
            </h1>

            <p class="text-gray-600 text-lg mb-8">
                از اینکه وقت گذاشتید و نظر خود را با ما به اشتراک گذاشتید، صمیمانه متشکریم.
                <br>
                نظرات شما به ما کمک می‌کند تا خدمات بهتری ارائه دهیم.
            </p>

            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 mb-8">
                <div class="flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <p class="text-orange-800 font-semibold text-lg">
                    🎁 شما 10 امتیاز وفاداری دریافت کردید!
                </p>
                <p class="text-orange-600 text-sm mt-2">
                    از امتیازهای خود برای دریافت تخفیف در نوبت‌های بعدی استفاده کنید.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('home') }}"
                   class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl">
                    🏠 بازگشت به صفحه اصلی
                </a>
                <a href="{{ route('services.index') }}"
                   class="bg-white border-2 border-purple-600 text-purple-600 px-8 py-3 rounded-xl font-bold hover:bg-purple-50 transition-colors">
                    📅 رزرو نوبت جدید
                </a>
            </div>
        </div>

        <div class="mt-8 text-gray-500 text-sm">
            <p>آیا سوالی دارید؟ با پشتیبانی ما تماس بگیرید:</p>
            <p class="font-bold text-gray-700 mt-2">021-12345678</p>
        </div>
    </div>
@endsection
