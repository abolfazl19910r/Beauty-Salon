@extends('layouts.app')

{{-- ⭐ بسته‌ی «و» — چیدمان «مجله»: تیتر خیلی درشت با نسخ، ستون‌بندی با خط‌های نازک، گوشه‌های تیز، فهرست شماره‌دار خدمات. --}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $jServices = collect($services ?? []);
        $jSpecialists = collect($specialists ?? []);
        $jHero = $jServices->first(fn ($s) => $s->image_url);
        $jNum = fn (int $i) => to_persian_num(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT));
    @endphp
    <style>
        .j-rule{border-color:var(--m-line2)}
        .j-strong{border-color:rgb(var(--m-tx) / .85)}
        .j-hero{display:grid}
        @media (min-width:1024px){.j-hero{grid-template-columns:1.25fr 1fr}}
        .j-display{font-family:var(--m-display, "Mahru");font-weight:700;line-height:1.2}
        .j-kick{font-size:12px;font-weight:700;color:rgb(var(--m-gold));letter-spacing:.06em}
        .j-link{font-weight:700;color:rgb(var(--m-gold));border-bottom:2px solid rgb(var(--m-gold));padding-bottom:3px}
        .j-item{display:grid;grid-template-columns:44px 1fr auto;gap:10px;align-items:baseline;padding:14px 0;border-bottom:1px solid var(--m-line)}
        .j-num{font-family:var(--m-display, "Mahru");color:rgb(var(--m-gold));font-size:20px;font-weight:700}
    </style>

    <div class="mx-[calc(50%-50vw)] w-screen pt-2" dir="rtl">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-wrap justify-between gap-3 py-3 text-xs text-m-mu border-b j-rule persian-number">
                <span>{{ $currentSalonName }}@if ($currentSalonAddress) · {{ $currentSalonAddress }}@endif</span>
                @if ($currentSalonHours)<span>{{ $currentSalonHours[0] }}</span>@endif
                @if (($reviewStats['count'] ?? 0) > 0)<span>{{ to_persian_num(number_format($reviewStats['avg'], 1)) }} از {{ to_persian_num((string) $reviewStats['count']) }} نظر</span>@endif
            </div>

            <section class="j-hero border-b j-rule">
                <div class="py-10 lg:py-14 lg:pl-10 lg:border-l j-rule flex flex-col">
                    <div class="j-kick mb-4">{{ $currentSalonTagline }}</div>
                    <h1 class="j-display text-5xl md:text-7xl text-m-tx mb-6">{{ $currentSalonName }}</h1>
                    <p class="text-m-mu leading-9 text-lg mb-8 max-w-xl">{{ \Illuminate\Support\Str::limit($currentSalonBio, 200) }}</p>
                    <div class="flex flex-wrap gap-6 items-center mt-auto">
                        <a href="{{ route('bookings.create') }}" class="btn-gold px-8 py-3.5">رزرو نوبت</a>
                        <a href="{{ route('services.index') }}" class="j-link text-sm">فهرست خدمات و قیمت‌ها</a>
                    </div>
                </div>
                <div class="min-h-[320px] lg:min-h-[520px] bg-m-bg3">
                    @if ($jHero)
                        <img src="{{ $jHero->image_url }}" alt="{{ $jHero->name }}" loading="lazy" class="w-full h-full object-cover">
                    @endif
                </div>
            </section>

            <section id="services" class="grid lg:grid-cols-[240px_1fr] border-b j-rule">
                <div class="py-8 lg:pl-10 lg:border-l j-rule">
                    <div class="j-kick mb-2">فهرست</div>
                    <h2 class="j-display text-4xl text-m-tx mb-2">خدمات</h2>
                    <p class="text-sm text-m-mu leading-7">قیمت‌ها از حداقل است و پس از مشاوره قطعی می‌شود.</p>
                </div>
                <div class="grid md:grid-cols-2 gap-x-10 py-4 lg:pr-10">
                    @forelse ($jServices as $i => $service)
                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="j-item hover:bg-m-tx/5">
                            <span class="j-num">{{ $jNum($i) }}</span>
                            <span><b class="text-m-tx font-semibold">{{ $service->name }}</b><small class="block text-xs text-m-mu persian-number">{{ to_persian_num((string) $service->duration) }} دقیقه</small></span>
                            <span class="font-bold text-m-tx persian-number whitespace-nowrap">{{ to_persian_num(number_format($service->price)) }}</span>
                        </a>
                    @empty
                        <p class="text-m-mu py-6">فهرست خدمات به‌زودی اضافه می‌شود.</p>
                    @endforelse
                </div>
            </section>

            @if ($jSpecialists->isNotEmpty())
                <section id="specialists" class="py-10">
                    <div class="flex items-baseline justify-between mb-6">
                        <h2 class="j-display text-3xl text-m-tx">متخصص‌ها</h2>
                        <a href="{{ route('specialists.search') }}" class="j-link text-sm">همه</a>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($jSpecialists as $specialist)
                            <a href="{{ route('specialists.show', $specialist) }}" class="block group">
                                <div class="h-56 bg-m-bg3 overflow-hidden">
                                    @if ($specialist->photoUrl())
                                        <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" loading="lazy" class="w-full h-full object-cover">
                                    @else
                                        <span class="w-full h-full flex items-center justify-center j-display text-5xl text-m-gold">{{ mb_substr($specialist->name, 0, 1) }}</span>
                                    @endif
                                </div>
                                <b class="block mt-3 j-display text-xl text-m-tx group-hover:text-m-gold">{{ $specialist->name }}</b>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="border-t j-strong py-10 mb-6 flex flex-wrap items-center justify-between gap-4">
                <h2 class="j-display text-3xl text-m-tx">نوبت بعدی‌تان را همین‌جا بگیرید.</h2>
                <a href="{{ route('bookings.create') }}" class="btn-gold px-8 py-3.5">رزرو نوبت</a>
            </section>
        </div>
    </div>

    @if ($appSalon = app(\App\Support\CurrentSalon::class)->get())
        @include('partials.open-in-app', ['appSalon' => $appSalon])
    @endif
@endsection
