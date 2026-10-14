@extends('layouts.app')
@section('title', $package->name)

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-2xl mx-auto fade-in">
        <a href="{{ route('courses.index') }}" class="text-sm" style="{{ $dim }}">→ همه‌ی دوره‌ها</a>
        <div class="rounded-xl p-6 mt-3" style="{{ $card }}">
            @include('courses._flash')
            <h1 class="text-2xl font-bold" style="{{ $gold }}">{{ $package->name }}</h1>
            <p class="text-sm mt-1" style="{{ $dim }}">{{ $package->service?->name }}@if ($package->specialist) — با {{ $package->specialist->name }}@endif</p>
            @if ($package->description)
                <p class="mt-4 leading-8" style="color: var(--rasta-cream);">{{ $package->description }}</p>
            @endif

            <dl class="grid grid-cols-2 gap-3 mt-5 text-sm persian-number">
                <div class="rounded-lg p-3" style="{{ $inner }}"><dt style="{{ $dim }}">تعداد جلسه</dt><dd class="font-bold mt-1" style="color: var(--rasta-cream);">{{ to_persian_num((string) $package->sessions_count) }}</dd></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><dt style="{{ $dim }}">فاصله‌ی جلسه‌ها</dt><dd class="font-bold mt-1" style="color: var(--rasta-cream);">حدود {{ to_persian_num((string) $package->interval_days) }} روز</dd></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><dt style="{{ $dim }}">قیمت کل</dt><dd class="font-bold mt-1" style="color: var(--rasta-cream);">{{ number_format($package->price) }} تومان</dd></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><dt style="{{ $dim }}">مهلت استفاده</dt><dd class="font-bold mt-1" style="color: var(--rasta-cream);">{{ $package->validity_days ? to_persian_num((string) $package->validity_days).' روز از خرید' : 'بدون محدودیت' }}</dd></div>
            </dl>
            @if ($package->savingAmount() > 0)
                <p class="mt-3 text-sm persian-number" style="color: #6EE7B7;">{{ number_format($package->savingAmount()) }} تومان کمتر از {{ to_persian_num((string) $package->sessions_count) }} جلسه‌ی تکی.</p>
            @endif
            @if ($package->min_prepayment_percent)
                <p class="mt-2 text-sm persian-number" style="{{ $dim }}">می‌توانید اول دست‌کم {{ number_format($package->minPrepaymentFor($package->price)) }} تومان بدهید و باقی را بعداً؛ جلسه‌ها به اندازه‌ی مبلغ پرداخت‌شده رزرو می‌شوند.</p>
            @endif

            @auth
                <form method="POST" action="{{ route('courses.purchase', $package) }}" class="mt-6">
                    @csrf
                    <label class="block text-sm mb-1" style="{{ $dim }}" for="discount_code">کد تخفیف (اختیاری)</label>
                    <div class="flex gap-2">
                        <input id="discount_code" name="discount_code" value="{{ old('discount_code') }}" dir="ltr" class="flex-1 px-3 py-2 rounded-lg"
                               style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);">
                        <button type="submit" class="px-6 py-2 rounded-lg font-bold" style="{{ $btn }}">خرید دوره</button>
                    </div>
                </form>
            @else
                <a href="{{ route('login') }}" class="mt-6 block text-center px-6 py-3 rounded-lg font-bold" style="{{ $btn }}">برای خرید وارد شوید</a>
            @endauth
        </div>
    </div>
@endsection
