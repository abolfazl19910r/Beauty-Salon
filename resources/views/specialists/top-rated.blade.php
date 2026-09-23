@extends('layouts.app')
@section('title', 'برترین متخصص‌ها')

{{-- ⭐ صفحه‌ی عمومی برترین متخصص‌ها (۲۰۲۶-۰۹-۲۴) — قبلاً view نداشت و پشت لاگین بود. معیار از
     SpecialistRepository::getTopRated: میانگین امتیاز حداقل ۴ از حداقل ۵ امتیاز ثبت‌شده. --}}
@section('content')
    <div class="max-w-6xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        <h1 class="text-2xl md:text-3xl font-bold mb-2" style="color: var(--rasta-gold-light);">برترین متخصص‌های {{ $currentSalonName }}</h1>
        <p class="text-sm opacity-75 mb-6">متخصص‌هایی با میانگین امتیاز ۴ یا بیشتر از حداقل ۵ امتیاز مشتری‌ها.</p>

        @include('specialists.partials.nav')

        @if ($specialists->isEmpty())
            <div class="text-center py-10">
                <p class="opacity-70 mb-4">هنوز متخصصی به حد نصاب امتیاز نرسیده است.</p>
                <a href="{{ route('specialists.search') }}" class="underline" style="color: var(--rasta-gold);">مشاهده‌ی همه‌ی متخصص‌ها</a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($specialists as $specialist)
                    @include('specialists.partials.card', ['specialist' => $specialist])
                @endforeach
            </div>
        @endif
    </div>
@endsection
