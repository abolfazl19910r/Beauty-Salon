@extends('layouts.app')

{{-- ⭐ بسته‌ی «و» — چیدمان «آرام»: یک ستون باریک وسط صفحه، فضای خالی زیاد، وزیرمتن نازک، فهرست ساده به‌جای کارت. --}}

@section('title', $currentSalonName)

@section('content')
    @php
        $aServices = collect($services ?? []);
        $aSpecialists = collect($specialists ?? []);
    @endphp
    <style>
        .a-wrap{max-width:620px;margin:0 auto}
        .a-ring{width:76px;height:76px;border-radius:50%;border:1px solid rgb(var(--m-gold));display:flex;align-items:center;justify-content:center;color:rgb(var(--m-gold));font-weight:300;font-size:30px;margin:0 auto;overflow:hidden}
        .a-info{display:flex;flex-wrap:wrap;justify-content:center;gap:10px 28px;color:rgb(var(--m-mu));font-size:14px;padding:18px 0;border-top:1px solid var(--m-line);border-bottom:1px solid var(--m-line)}
        .a-ttl{font-size:13px;color:rgb(var(--m-mu));font-weight:500;letter-spacing:.06em;margin:46px 0 14px}
        .a-li{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 4px;border-bottom:1px solid var(--m-line);font-size:16px}
        .a-li:hover{background:rgb(var(--m-tx) / .03)}
    </style>

    <div class="a-wrap pt-10 pb-16" dir="rtl">
        <div class="a-ring">
            @if ($currentSalonLogoUrl)
                <x-brand-emblem class="w-full h-full object-contain" />
            @else
                {{ mb_substr($currentSalonName, 0, 1) }}
            @endif
        </div>
        <h1 class="text-center text-5xl md:text-6xl mt-6 mb-3 text-m-tx" style="font-weight:200">{{ $currentSalonName }}</h1>
        <p class="text-center text-m-mu text-lg leading-9" style="font-weight:300">{{ $currentSalonTagline }}</p>
        <div class="flex flex-wrap justify-center gap-3 mt-7">
            <a href="{{ route('bookings.create') }}" class="btn-gold px-9 py-3">رزرو نوبت</a>
            <a href="{{ route('services.index') }}" class="btn-glass px-7 py-3">قیمت‌ها</a>
        </div>

        <div class="a-info mt-8 persian-number">
            @if ($currentSalonAddress)<span>{{ $currentSalonAddress }}</span>@endif
            @if ($currentSalonHours)<span>{{ $currentSalonHours[0] }}</span>@endif
            @if (($reviewStats['count'] ?? 0) > 0)<span>{{ to_persian_num(number_format($reviewStats['avg'], 1)) }} از {{ to_persian_num((string) $reviewStats['count']) }} نظر</span>@endif
            @if ($currentSalonPhone)<a href="tel:{{ $currentSalonPhone }}" dir="ltr">{{ $currentSalonPhone }}</a>@endif
        </div>

        <div class="a-ttl" id="services">خدمات</div>
        @forelse ($aServices as $service)
            <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="a-li">
                <span class="text-m-tx">{{ $service->name }}</span>
                <span class="flex items-center gap-5 text-sm text-m-mu persian-number">
                    <span>{{ to_persian_num((string) $service->duration) }} دقیقه</span>
                    <b class="text-m-tx font-medium whitespace-nowrap">{{ to_persian_num(number_format($service->price)) }} ت</b>
                </span>
            </a>
        @empty
            <p class="text-m-mu">فهرست خدمات به‌زودی اضافه می‌شود.</p>
        @endforelse

        @if ($aSpecialists->isNotEmpty())
            <div class="a-ttl text-center" id="specialists">متخصص‌ها</div>
            <div class="flex flex-wrap justify-center gap-6">
                @foreach ($aSpecialists as $specialist)
                    <a href="{{ route('specialists.show', $specialist) }}" class="text-center text-sm text-m-mu hover:text-m-tx">
                        @if ($specialist->photoUrl())
                            <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" loading="lazy" class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                        @else
                            <span class="w-16 h-16 rounded-full bg-m-bg3 text-m-gold flex items-center justify-center mx-auto mb-2 text-xl">{{ mb_substr($specialist->name, 0, 1) }}</span>
                        @endif
                        {{ $specialist->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <p class="text-center text-m-mu leading-9 mt-14" style="font-weight:300">{{ \Illuminate\Support\Str::limit($currentSalonBio, 220) }}</p>
    </div>

    @if ($appSalon = app(\App\Support\CurrentSalon::class)->get())
        @include('partials.open-in-app', ['appSalon' => $appSalon])
    @endif
@endsection
