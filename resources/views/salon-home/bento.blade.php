@extends('layouts.app')

{{-- ⭐ بسته‌ی «و» — چیدمان «بنتو»: همه‌چیز در کاشی‌های اندازه‌دار مثل یک اپ؛ تیترها با کوفی، گوشه‌های خیلی گرد. --}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $bServices = collect($services ?? []);
        $bSpecialists = collect($specialists ?? []);
        $bHero = $bServices->first(fn ($s) => $s->image_url);
    @endphp
    <style>
        .b-grid{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
        @media (min-width:1024px){.b-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
        .b-t{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:28px;padding:22px;overflow:hidden;position:relative}
        .b-fill{background:var(--m-btn);color:var(--m-on-gold);border-color:transparent}
        .b-soft{background:rgb(var(--m-gold) / .12);border-color:transparent}
        .b-lab{font-size:13px;font-weight:600;color:rgb(var(--m-mu))}
        .b-fill .b-lab{color:inherit;opacity:.8}
        .b-k{font-family:var(--m-display, "Mahru");font-weight:800}
        .b-span2{grid-column:span 2}
        @media (min-width:1024px){.b-hero{grid-column:span 2;grid-row:span 2}}
        .b-ic{width:46px;height:46px;border-radius:16px;background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold));display:flex;align-items:center;justify-content:center}
    </style>

    <div class="max-w-7xl mx-auto px-1 pt-6 pb-14" dir="rtl">
        <div class="b-grid">
            <div class="b-t b-span2 b-hero flex flex-col justify-between" style="min-height:320px">
                <span class="inline-flex self-start text-sm font-semibold px-4 py-2 rounded-full" style="background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold))">{{ $currentSalonTagline }}</span>
                <div>
                    <h1 class="b-k text-4xl md:text-5xl leading-relaxed text-m-tx">{{ $currentSalonName }}</h1>
                    <p class="text-m-mu leading-8 my-4">{{ \Illuminate\Support\Str::limit($currentSalonBio, 150) }}</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('bookings.create') }}" class="btn-gold px-7 py-3.5">رزرو نوبت</a>
                        <a href="{{ route('services.index') }}" class="btn-glass px-6 py-3.5">خدمات و قیمت‌ها</a>
                    </div>
                </div>
            </div>

            <a href="{{ route('bookings.create') }}" class="b-t b-fill flex flex-col justify-between" style="min-height:150px">
                <span class="b-lab">رزرو آنلاین</span>
                <span><span class="b-k text-3xl block">همین حالا</span><span class="text-sm font-bold">ساعت خالی را ببینید ←</span></span>
            </a>

            @if (($reviewStats['count'] ?? 0) > 0)
                <div class="b-t flex flex-col justify-between">
                    <span class="b-lab">امتیاز مشتری‌ها</span>
                    <span><span class="b-k text-5xl text-m-gold block persian-number">{{ to_persian_num(number_format($reviewStats['avg'], 1)) }}</span><span class="text-sm text-m-mu">از {{ to_persian_num((string) $reviewStats['count']) }} نظر</span></span>
                </div>
            @else
                <div class="b-t flex flex-col justify-between">
                    <span class="b-lab">متخصص‌ها</span>
                    <span class="b-k text-5xl text-m-gold persian-number">{{ to_persian_num((string) $bSpecialists->count()) }}</span>
                </div>
            @endif

            <div class="b-t" style="padding:0;min-height:150px;background:rgb(var(--m-bg3))">
                @if ($bHero)<img src="{{ $bHero->image_url }}" alt="{{ $bHero->name }}" loading="lazy" class="w-full h-full object-cover">@endif
            </div>

            <div class="b-t">
                <span class="b-lab">ساعت کاری</span>
                <div class="b-k text-lg mt-2 text-m-tx persian-number leading-8">{{ $currentSalonHours[0] ?? 'برای هماهنگی تماس بگیرید' }}</div>
            </div>

            @forelse ($bServices->take(3) as $service)
                <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="b-t flex flex-col gap-4 justify-between">
                    <span class="b-ic" aria-hidden="true"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><circle cx="6" cy="7" r="3"/><circle cx="6" cy="17" r="3"/><path d="M8.5 8.5L20 18M8.5 15.5L20 6"/></svg></span>
                    <span><b class="block text-m-tx">{{ $service->name }}</b><span class="text-sm text-m-mu persian-number">{{ to_persian_num((string) $service->duration) }} دقیقه · {{ to_persian_num(number_format($service->price)) }} ت</span></span>
                </a>
            @empty
                <div class="b-t b-span2"><p class="text-m-mu">فهرست خدمات به‌زودی اضافه می‌شود.</p></div>
            @endforelse

            <a href="{{ route('services.index') }}" class="b-t b-soft flex flex-col gap-4 justify-between" id="services">
                <span class="b-ic" style="background:rgb(var(--m-bg2))" aria-hidden="true"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path d="M12 5v14M5 12h14"/></svg></span>
                <span><b class="block text-m-tx">همه‌ی خدمات</b><span class="text-sm text-m-mu">با قیمت و مدت</span></span>
            </a>

            @if ($bSpecialists->isNotEmpty())
                <a href="{{ route('specialists.search') }}" class="b-t b-span2 flex items-center justify-between gap-4" id="specialists">
                    <span><span class="b-lab">متخصص‌ها</span><span class="b-k text-xl block mt-1 text-m-tx">{{ $bSpecialists->pluck('name')->take(3)->implode('، ') }}</span></span>
                    <span class="flex -space-x-3 space-x-reverse">
                        @foreach ($bSpecialists as $specialist)
                            @if ($specialist->photoUrl())
                                <img src="{{ $specialist->photoUrl() }}" alt="" class="w-12 h-12 rounded-full object-cover border-2" style="border-color:rgb(var(--m-bg2))">
                            @else
                                <span class="w-12 h-12 rounded-full flex items-center justify-center border-2 text-m-gold" style="background:rgb(var(--m-bg3));border-color:rgb(var(--m-bg2))">{{ mb_substr($specialist->name, 0, 1) }}</span>
                            @endif
                        @endforeach
                    </span>
                </a>
            @endif

            @if ($currentSalonAddress || $currentSalonPhone)
                <div class="b-t b-span2 flex items-center gap-4">
                    <span class="b-ic" style="width:56px;height:56px" aria-hidden="true"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/></svg></span>
                    <span><span class="b-lab">نشانی و تماس</span><b class="block mt-1 text-m-tx">{{ $currentSalonAddress }}</b>@if ($currentSalonPhone)<a href="tel:{{ $currentSalonPhone }}" class="text-sm text-m-mu" dir="ltr">{{ $currentSalonPhone }}</a>@endif</span>
                </div>
            @endif
        </div>
    </div>

    @if ($appSalon = app(\App\Support\CurrentSalon::class)->get())
        @include('partials.open-in-app', ['appSalon' => $appSalon])
    @endif
@endsection
