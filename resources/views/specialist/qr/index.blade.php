@extends('layouts.specialist')

@section('title', 'QR من')

{{-- «QR من» (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰): QR صفحه‌ی عمومی متخصص؛ PNG در مرورگر (salon-qr-poster.js)، PDF از SalonQrService --}}
@section('content')
    <div class="fade-in max-w-4xl mx-auto space-y-6" data-specialist-qr-page>
        <div>
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">QR من</h1>
            <p class="text-sm text-[var(--specialist-text-dim)] mt-1">مشتری با اسکن این QR صفحه‌ی شما را باز می‌کند و مستقیم با شما نوبت می‌گیرد.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
            <div class="specialist-card rounded-xl p-5 text-center">
                <div class="font-bold text-[var(--specialist-text)]">{{ $target['title'] }}</div>
                <div class="text-sm text-[var(--specialist-text-dim)] mt-1">{{ $target['subtitle'] }}</div>
                <div class="w-56 h-56 mx-auto mt-4 rounded-lg p-2" style="background:#fff;" data-salon-qr="{{ $target['url'] }}" role="img" aria-label="QR"></div>
                <p class="text-xs mt-3 break-all text-[var(--specialist-text-dim)]" dir="ltr">{{ $target['url'] }}</p>
                <p class="text-sm mt-2 text-[var(--specialist-text-dim)]">بازدید از این QR در ۳۰ روز اخیر: <span class="persian-number font-bold text-[var(--specialist-text)]">{{ to_persian_num((string) $hits) }}</span></p>
            </div>

            <div class="space-y-5">
                <div class="specialist-card rounded-xl p-5"
                     data-qr-poster
                     data-url="{{ $target['url'] }}" data-title="{{ $target['title'] }}" data-subtitle="{{ $target['subtitle'] }}"
                     data-code="{{ $target['code'] }}" data-brand="{{ config('brand.name') }}" data-file="{{ $target['file'] }}"
                     @if ($logo) data-logo="{{ $logo['data_uri'] }}" @endif>
                    <h2 class="font-bold text-[var(--specialist-text)] mb-1">تصویر برای اینستاگرام</h2>
                    <p class="text-xs text-[var(--specialist-text-dim)] mb-3">PNG با نام سالن، نام شما و QR.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-qr-poster-format="story" class="specialist-cta px-3 py-2 rounded-lg text-sm font-bold">استوری</button>
                        <button type="button" data-qr-poster-format="post" class="specialist-cta px-3 py-2 rounded-lg text-sm font-bold">پست مربع</button>
                        <button type="button" data-qr-poster-format="plain" class="px-3 py-2 rounded-lg text-sm font-bold border border-[var(--specialist-border)] text-[var(--specialist-text)]">فقط QR</button>
                    </div>
                </div>

                <div class="specialist-card rounded-xl p-5">
                    <h2 class="font-bold text-[var(--specialist-text)] mb-1">نسخه‌ی چاپی (PDF)</h2>
                    <p class="text-xs text-[var(--specialist-text-dim)] mb-3">با رنگ تیره روی کاغذ سفید چاپ کنید.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Services\Qr\SalonQrService::LAYOUTS as $layout => $label)
                            <a href="{{ route('specialist.qr.pdf', ['layout' => $layout]) }}" target="_blank" rel="noopener" data-qr-pdf="{{ $layout }}"
                               class="px-3 py-2 rounded-lg text-sm font-bold border border-[var(--specialist-border)] text-[var(--specialist-text)]">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/salon-qr.js')
    @vite('resources/js/salon-qr-poster.js')
@endsection
