@extends('layouts.app')
@section('title', 'متخصص‌های '.$service->name)

{{-- ⭐ صفحه‌ی عمومی «متخصص‌های یک خدمت» (۲۰۲۶-۰۹-۲۴) — قبلاً view نداشت (۵۰۰). --}}
@section('content')
    <div class="max-w-6xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        <h1 class="text-2xl md:text-3xl font-bold mb-2" style="color: var(--rasta-gold-light);">متخصص‌های «{{ $service->name }}»</h1>
        <p class="text-sm opacity-75 mb-6 persian-number">
            قیمت: {{ to_persian_num(number_format((int) $service->price)) }} تومان
            @if ($service->duration) — مدت: {{ to_persian_num((string) $service->duration) }} دقیقه @endif
        </p>

        @include('specialists.partials.nav')

        @if ($specialists->isEmpty())
            <p class="text-center opacity-70 py-10">در حال حاضر متخصصی برای این خدمت ثبت نشده است.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($specialists as $specialist)
                    @include('specialists.partials.card', ['specialist' => $specialist])
                @endforeach
            </div>
            <div class="mt-8">{{ $specialists->links() }}</div>
        @endif
    </div>
@endsection
