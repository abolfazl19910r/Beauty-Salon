@extends('layouts.specialist')

@section('title', 'نصب پنل روی گوشی')

{{-- راهنمای نصب پنل متخصص (بسته‌ی ۶): آیفون ← «افزودن به صفحه‌ی اصلی» (PWA)؛ اندروید ← اپ «ماهرو همکار» از صفحه‌ی دانلود ماهرو --}}
@section('content')
    <div class="fade-in max-w-xl mx-auto space-y-6" data-specialist-install>
        <div>
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">پنل متخصص روی گوشی</h1>
            <p class="text-sm text-[var(--specialist-text-dim)] mt-1">یک بار نصب کنید و هر بار از صفحه‌ی اصلی گوشی باز کنید.</p>
        </div>

        <div class="specialist-card rounded-xl p-5">
            <h2 class="font-bold text-[var(--specialist-text)] mb-3">آیفون</h2>
            @include('partials.ios-add-to-home-steps', ['appTitle' => 'پنل متخصص'])
            <p class="text-xs text-[var(--specialist-text-dim)] mt-3">هنگام ورود «مرا به خاطر بسپار» را بزنید تا ۹۰ روز وارد بمانید. خبر نوبت‌ها روی آیفون با پیامک می‌آید.</p>
        </div>

        <div class="specialist-card rounded-xl p-5">
            <h2 class="font-bold text-[var(--specialist-text)] mb-2">اندروید</h2>
            <p class="text-sm text-[var(--specialist-text-dim)] leading-7">اپ «ماهرو همکار» را از صفحه‌ی دانلود ماهرو نصب کنید؛ نوبت‌ها، تقویم و کیف پول با اعلان روی گوشی.</p>
            <a href="{{ \App\Support\CentralUrl::route('app-download.show') }}" class="specialist-cta inline-block mt-3 px-4 py-2 rounded-lg text-sm font-bold">صفحه‌ی دانلود اپ</a>
        </div>
    </div>
@endsection
