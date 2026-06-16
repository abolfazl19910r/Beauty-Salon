@extends('layouts.app')
@section('title', 'تشکر از شما')

@section('content')
    <div class="max-w-2xl mx-auto text-center fade-in">

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/15 p-10 md:p-12">

            <div class="relative w-20 h-20 mx-auto mb-6">
                <div class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ping"></div>
                <div class="relative w-20 h-20 rounded-full bg-emerald-900/40 border-2 border-emerald-500/40 flex items-center justify-center">
                    <svg class="w-10 h-10 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-4"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">
                نظر شما با موفقیت ثبت شد!
            </h1>

            <p class="text-[#F8F3E9]/60 leading-8 mb-8 text-sm">
                از اینکه وقت گذاشتید و نظر خود را با ما به اشتراک گذاشتید، صمیمانه متشکریم.
                <br>نظرات شما به ما کمک می‌کند تا خدمات بهتری ارائه دهیم.
            </p>

            <div class="bg-[#C9A24B]/10 border border-[#C9A24B]/20 rounded-2xl p-6 mb-8">
                <div class="w-12 h-12 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-[#E6CD8A]" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <p class="text-[#E6CD8A] font-bold persian-number">شما ۱۰ امتیاز وفاداری دریافت کردید!</p>
                <p class="text-[#F8F3E9]/55 text-xs mt-2">از امتیازهای خود برای دریافت تخفیف در نوبت‌های بعدی استفاده کنید.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('home') }}"
                   class="px-8 py-3 rounded-xl text-sm font-semibold transition-all
                      bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                      hover:shadow-lg hover:shadow-[#C9A24B]/25">
                    بازگشت به صفحه اصلی
                </a>
                <a href="{{ route('services.index') }}"
                   class="px-8 py-3 rounded-xl text-sm border border-[#C9A24B]/25
                      text-[#E6CD8A] hover:bg-[#C9A24B]/10 transition-colors">
                    رزرو نوبت جدید
                </a>
            </div>
        </div>

        <div class="mt-8 text-[#F8F3E9]/40 text-sm">
            <p>آیا سوالی دارید؟ با پشتیبانی ما تماس بگیرید:</p>
            <p class="font-bold text-[#F8F3E9]/60 mt-1 persian-number" dir="ltr">021-12345678</p>
        </div>
    </div>
@endsection
