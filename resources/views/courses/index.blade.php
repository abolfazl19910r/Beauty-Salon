@extends('layouts.app')
@section('title', 'دوره‌های درمانی')

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-5xl mx-auto fade-in">
        <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold" style="{{ $gold }}">دوره‌های درمانی</h1>
                <p class="text-sm mt-1" style="{{ $dim }}">چند جلسه را یک‌جا و ارزان‌تر بخرید؛ موعد هر جلسه را خودمان یادآوری می‌کنیم.</p>
            </div>
            @auth
                <a href="{{ route('my-courses.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">دوره‌های من</a>
            @endauth
        </div>
        @include('courses._flash')

        @if ($packages->isEmpty())
            <div class="rounded-xl p-8 text-center" style="{{ $card }} {{ $dim }}">فعلاً دوره‌ای برای فروش آنلاین نیست.</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($packages as $package)
                    <a href="{{ route('courses.show', $package) }}" class="block rounded-xl p-5 transition hover:opacity-95" style="{{ $card }}" data-course-package="{{ $package->id }}">
                        <div class="flex justify-between items-start gap-3">
                            <h2 class="font-bold text-lg" style="{{ $gold }}">{{ $package->name }}</h2>
                            <span class="text-xs px-2 py-1 rounded-full persian-number" style="background: rgba(201,162,75,0.12); color: var(--rasta-gold);">{{ to_persian_num((string) $package->sessions_count) }} جلسه</span>
                        </div>
                        <p class="text-sm mt-1" style="{{ $dim }}">{{ $package->service?->name }}@if ($package->specialist) — {{ $package->specialist->name }}@endif</p>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <div class="text-xl font-bold persian-number" style="color: var(--rasta-cream);">{{ number_format($package->price) }} <span class="text-xs">تومان</span></div>
                                @if ($package->savingAmount() > 0)
                                    <div class="text-xs mt-1 persian-number" style="color: #6EE7B7;">{{ number_format($package->savingAmount()) }} تومان کمتر از جلسه‌های تکی</div>
                                @endif
                            </div>
                            <div class="text-xs text-left" style="{{ $dim }}">
                                هر {{ to_persian_num((string) $package->interval_days) }} روز یک جلسه
                                @if ($package->validity_days)<br>مهلت {{ to_persian_num((string) $package->validity_days) }} روز@endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
