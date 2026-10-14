@extends('layouts.app')

@section('title', $service->name)

@section('content')
    <style>
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: var(--m-sh-soft); }
    </style>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-sm text-m-tx/50 mb-8 fade-in" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-m-gold2 transition-colors">خانه</a>
        <svg class="w-3.5 h-3.5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
        <a href="{{ route('services.index') }}" class="hover:text-m-gold2 transition-colors">خدمات</a>
        <svg class="w-3.5 h-3.5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
        </svg>
        <span class="text-m-gold2">{{ $service->name }}</span>
    </nav>

    <div class="max-w-4xl mx-auto">

        {{-- Main service card --}}
        <div class="bg-m-card rounded-3xl overflow-hidden border border-m-gold/12 shadow-2xl fade-in mb-10">
            {{-- Image --}}
            @if($service->image)
                <div class="overflow-hidden h-72">
                    <img src="{{ $service->image_url }}" alt="{{ $service->name }}"
                         class="w-full h-full object-cover">
                </div>
            @else
                <div class="h-52 bg-m-bg flex items-center justify-center border-b border-m-gold/10">
                    <img src="{{ asset('images/placeholder-service.svg') }}" alt="{{ $service->name }}"
                         class="h-full w-full object-cover opacity-60">
                </div>
            @endif

            <div class="p-6 md:p-8">
                {{-- Title + Category + Price --}}
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-m-tx mb-3"
                            style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                            {{ $service->name }}
                        </h1>
                        @if($service->category)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full
                                     bg-m-gold/15 text-m-gold2 border border-m-gold/25">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            {{ $service->category->name }}
                        </span>
                        @endif
                    </div>
                    <div class="text-left sm:text-right shrink-0">
                        <p class="text-2xl font-bold text-m-gold2 persian-number">{{ number_format($service->price) }}</p>
                        <p class="text-xs text-m-tx/50 mt-0.5">تومان</p>
                        <div class="flex items-center gap-1.5 mt-2 text-sm text-m-tx/60 justify-end">
                            <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                            </svg>
                            <span class="persian-number">{{ $service->duration }} دقیقه</span>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-m-bg/60 rounded-2xl p-5 mb-8 text-m-tx/75 leading-8 text-sm border border-m-gold/8">
                    {!! nl2br(e($service->description)) !!}
                </div>

                {{-- Specialists in this service --}}
                @if(count($specialists) > 0)
                    <div class="mb-8">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-m-gold2 mb-4"
                            style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                            <svg class="w-5 h-5 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            متخصصین این خدمت
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($specialists as $specialist)
                                <div class="card-hover bg-m-bg/60 rounded-xl p-4 border border-m-gold/10 flex flex-col items-center text-center">
                                    <div class="w-12 h-12 rounded-full bg-m-gold/15 flex items-center justify-center mb-3
                                            text-lg font-bold text-m-gold2"
                                         style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                                        {{ mb_substr($specialist->name, 0, 1) }}
                                    </div>
                                    <p class="font-bold text-sm text-m-tx mb-1">{{ $specialist->name }}</p>
                                    @if($specialist->schedules->isNotEmpty())
                                        <p class="text-xs text-m-tx/50 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-m-gold/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                                                <line x1="3" y1="10" x2="21" y2="10"/>
                                            </svg>
                                            {{ $specialist->work_days }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Reservation button --}}
                <div class="text-center pt-4 border-t border-m-gold/10">
                    <a href="{{ route('bookings.create', ['service' => $service->id]) }}"
                       class="inline-block px-10 py-3.5 rounded-full font-bold text-sm transition-all duration-300
                          bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                          hover:shadow-xl hover:shadow-[#C9A24B]/30 hover:-translate-y-1">
                        رزرو این خدمت
                    </a>
                </div>
            </div>
        </div>

        {{-- Related services --}}
        @if($relatedServices->isNotEmpty())
            <div class="fade-in">
                <h2 class="flex items-center gap-2 text-2xl font-bold text-m-gold2 mb-6"
                    style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">
                    <svg class="w-6 h-6 text-m-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    خدمات مرتبط
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    @foreach($relatedServices as $rel)
                        <div class="card-hover bg-m-card rounded-2xl overflow-hidden border border-m-gold/10">
                            @if($rel->image)
                                <div class="overflow-hidden h-36">
                                    <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}"
                                         loading="lazy" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105">
                                </div>
                            @else
                                <div class="h-36 bg-m-bg/60 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-m-gold/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="p-4">
                                <h3 class="font-bold text-m-tx mb-2 text-sm">{{ $rel->name }}</h3>
                                <div class="flex items-center justify-between">
                                <span class="text-m-gold2 text-sm font-semibold persian-number">
                                    {{ number_format($rel->price) }} تومان
                                </span>
                                    <a href="{{ route('services.show', $rel) }}"
                                       class="text-xs text-m-gold hover:text-m-gold2 transition-colors flex items-center gap-1">
                                        مشاهده
                                        <svg class="w-3.5 h-3.5 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
