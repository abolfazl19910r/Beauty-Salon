@extends('layouts.app')

{{--
    ⭐ بسته‌ی ۵الف — قالب «اپ» (app = لینک + بنتو + تقویم؛ بوم «۴ قالب ترکیبی»، پیش‌نمایش یاس روشن):
    ستون کناری چسبان «کارت سالن» (نشان در حلقه، نام، توضیح، امتیاز، «باز تا X»، «رزرو نوبت»، تماس / مسیریابی / نظرها) — همین
    کارت در موبایل بالای صفحه و مناسب لینک بیو اینستاگرام؛ کنارش نوار تیره‌ی «نوبت‌های خالی» (۶ ساعت نزدیک، همه‌ی کارشناسان)،
    کاشی‌های خدمات، کاشی رنگ دوم «همه‌ی خدمات و قیمت‌ها»، کاشی کارشناسان با نوبت بعدی و کاشی «نمونه‌کارها، N عکس» (فقط با گالری).
    هر تکه بی داده‌ی واقعی پنهان است. داده: HomeController + TemplateHomeData.
--}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $apReviews = (int) ($reviewStats['count'] ?? 0);
        $apToday = $template['today'] ?? null;
        $apNearest = $template['nearest'] ?? [];
        $apNext = $template['nextFree'] ?? [];
        $apTiles = collect($template['menu'] ?? [])->flatMap(fn ($g) => collect($g['services'])->map(fn ($s) => ['service' => $s, 'category' => $g['name']]))->take(5);
        $apMore = max(0, (int) ($template['serviceCount'] ?? 0) - $apTiles->count());
        $apCategories = collect($template['menu'] ?? [])->pluck('name')->unique()->take(3)->implode('، ');
        $apSalon = app(\App\Support\CurrentSalon::class)->get();
        $apDirections = $apSalon ? \App\Support\SalonMapLocation::directionsUrl($apSalon) : null;
        $apGallery = (int) ($template['galleryCount'] ?? 0);
    @endphp
    <style>
        .ap{color:rgb(var(--m-tx))}
        .ap-card{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:26px;padding:22px}
        .ap-side{display:flex;flex-direction:column;gap:12px;padding:26px}
        @media (min-width:1024px){.ap-side{position:sticky;top:96px}}
        .ap-ring{width:72px;height:72px;border-radius:50%;background:rgb(var(--m-bg3));display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 3px rgb(var(--m-bg2)),0 0 0 5px rgb(var(--m-gold));flex-shrink:0;overflow:hidden}
        .ap h1{margin:0;font-size:24px;font-weight:900;line-height:1.5}
        .ap-chip{background:rgb(var(--m-bg3));padding:4px 10px;border-radius:999px;font-size:13px}
        .ap-chip--ok{background:rgb(var(--m-ok) / .14);color:rgb(var(--m-ok))}
        .ap-book{text-align:center;background:rgb(var(--m-gold));color:var(--m-on-gold);padding:15px;border-radius:18px;font-weight:800;font-size:16px}
        .ap-act{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 0;border-radius:16px;background:rgb(var(--m-bg3));color:rgb(var(--m-tx));font-size:13px}
        .ap-act svg{width:20px;height:20px;color:rgb(var(--m-gold))}
        .ap-band{background:rgb(var(--m-tx));color:rgb(var(--m-bg));border-radius:26px;padding:24px}
        .ap-slot{text-align:center;padding:12px 0;border-radius:16px;background:rgb(var(--m-bg) / .08);color:rgb(var(--m-bg))}
        .ap-slot:first-child{background:rgb(var(--m-bg));color:rgb(var(--m-tx))}
        .ap-slot span{display:block;font-size:12px;opacity:.75}
        .ap-slot b{font-size:17px}
        .ap-tile{display:flex;flex-direction:column;gap:6px;color:rgb(var(--m-tx));min-height:130px}
        .ap-tile:hover{border-color:var(--m-line2)}
        .ap-tile__cat{font-size:12.5px;color:rgb(var(--m-accent2))}
        .ap-tile__meta{margin-top:auto;padding-top:12px;font-size:14px;color:rgb(var(--m-mu))}
        .ap-more{background:rgb(var(--m-accent2));color:var(--m-on2);border-radius:26px;padding:22px;display:flex;flex-direction:column;justify-content:space-between;min-height:130px}
        .ap-face{width:60px;height:60px;border-radius:50%}
    </style>

    <div class="ap max-w-[1240px] mx-auto px-4 md:px-6 pt-6 pb-14 grid gap-5 lg:grid-cols-[380px_1fr] items-start" dir="rtl" data-template-home="app">
        <aside class="ap-card ap-side" aria-label="کارت سالن" data-ap-salon-card>
            <div class="flex gap-3.5 items-center">
                <span class="ap-ring"><x-brand-emblem class="w-11 h-11" pad="2px" /></span>
                <div class="min-w-0">
                    <h1>{{ $currentSalonName }}</h1>
                    @if ($apCategories !== '')
                        <span class="text-[13.5px] text-m-mu">{{ $apCategories }}</span>
                    @endif
                </div>
            </div>
            <p class="m-0 text-[14.5px] leading-[1.9] text-m-mu">{{ \Illuminate\Support\Str::limit($currentSalonBio, 160) }}</p>
            @if ($apReviews > 0 || $apToday)
                <div class="flex flex-wrap gap-1.5">
                    @if ($apReviews > 0)
                        <span class="ap-chip">{{ fa_num($reviewStats['avg'], 1) }} از {{ fa_num($apReviews) }} نظر</span>
                    @endif
                    @if ($apToday)
                        <span class="ap-chip {{ $apToday['open'] ? 'ap-chip--ok' : '' }}" data-ap-today>{{ $apToday['text'] }}</span>
                    @endif
                </div>
            @endif
            <a href="{{ route('bookings.create') }}" class="ap-book">رزرو نوبت</a>
            <div class="grid grid-cols-3 gap-2">
                @if ($currentSalonPhone)
                    <a href="tel:{{ $currentSalonPhone }}" class="ap-act"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>تماس</a>
                @endif
                @if ($apDirections)
                    <a href="{{ $apDirections }}" target="_blank" rel="noopener" class="ap-act"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>مسیریابی</a>
                @endif
                @if ($apReviews > 0)
                    <a href="{{ route('specialists.top-rated') }}" class="ap-act"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5a.56.56 0 011.04 0l2.13 4.32 4.77.69a.56.56 0 01.31.96l-3.45 3.36.81 4.75a.56.56 0 01-.81.59L12 15.92l-4.27 2.25a.56.56 0 01-.81-.59l.81-4.75-3.45-3.36a.56.56 0 01.31-.96l4.77-.69z"/></svg>نظرها</a>
                @else
                    <a href="{{ route('services.index') }}" class="ap-act"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>خدمات</a>
                @endif
            </div>
        </aside>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @if ($apNearest !== [])
                <section class="ap-band sm:col-span-2 xl:col-span-3" aria-labelledby="ap-free" data-ap-free>
                    <div class="flex flex-wrap justify-between items-center gap-2">
                        <b class="text-lg" id="ap-free">نوبت‌های خالی</b>
                        <span class="text-[13.5px] opacity-75">همه‌ی کارشناسان، همه‌ی خدمات</span>
                    </div>
                    <div class="mt-3.5 grid grid-cols-3 md:grid-cols-6 gap-2">
                        @foreach ($apNearest as $slot)
                            <a href="{{ route('bookings.create', ['specialist' => $slot['specialist_id'], 'date' => $slot['date'], 'time' => $slot['time']]) }}" class="ap-slot">
                                <span>{{ $slot['day_label'] }}</span><b>{{ $slot['label'] }}</b>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @foreach ($apTiles as $tile)
                <a href="{{ route('bookings.create', ['service' => $tile['service']->id]) }}" class="ap-card ap-tile" data-ap-service>
                    <span class="ap-tile__cat">{{ $tile['category'] }}</span>
                    <b class="text-lg">{{ $tile['service']->name }}</b>
                    <span class="ap-tile__meta">{{ fa_num($tile['service']->duration) }} دقیقه، از {{ fa_money($tile['service']->price) }} تومان</span>
                </a>
            @endforeach

            @if (($template['serviceCount'] ?? 0) > 0)
                <a href="{{ route('services.index') }}" class="ap-more" data-ap-all-services>
                    <span class="text-[13.5px] opacity-85">{{ $apMore > 0 ? fa_num($apMore).' خدمت دیگر' : 'قیمت و مدت هر خدمت' }}</span>
                    <b class="text-[19px]">همه‌ی خدمات و قیمت‌ها</b>
                </a>
            @endif

            @if (collect($specialists ?? [])->isNotEmpty())
                <section class="ap-card sm:col-span-2" aria-labelledby="ap-team">
                    <b class="text-[17px]" id="ap-team">کارشناسان</b>
                    <div class="mt-3.5 flex flex-wrap justify-between gap-4">
                        @foreach ($specialists as $specialist)
                            <a href="{{ route('specialists.show', $specialist) }}" class="flex flex-col items-center gap-1.5 text-m-tx" data-ap-person>
                                <x-palette-art class="ap-face" :src="$specialist->photoUrl()" :variant="$loop->index" :alt="$specialist->name" />
                                <b class="text-sm">{{ $specialist->name }}</b>
                                @if (isset($apNext[$specialist->id]))
                                    <span class="text-xs text-m-gold">{{ $apNext[$specialist->id]['text'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($apGallery > 0)
                <div class="relative min-h-[170px] rounded-[26px] overflow-hidden" data-ap-gallery>
                    <x-palette-art class="absolute inset-0" :src="$template['galleryCover'] ?? null" :variant="3" alt="نمونه‌کار سالن" />
                    <span class="absolute bottom-3 right-3.5 text-xs text-white bg-black/25 px-2.5 py-0.5 rounded-full">نمونه‌کارها، {{ fa_num($apGallery) }} عکس</span>
                </div>
            @endif
        </div>
    </div>
@endsection
