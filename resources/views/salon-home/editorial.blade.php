@extends('layouts.app')

{{--
    ⭐ بسته‌ی ۵ب — قالب «روزنامه‌ی سالن» (editorial = روزنامه + پیشنهاد + پکیج‌ها؛ بوم «۴ قالب ترکیبی»، پیش‌نمایش مریم‌گلی روشن):
    صفحه‌ی اول روزنامه؛ تیتر نسخ از tagline با روتیتر «دسته‌ها، از سال …»، کاور (عکس کاور ← اولین عکس گالری ← طرح پالت)؛ ستون کنار
    با «پیشنهاد ویژه» (فقط کد «نمایش در سایت»)، نقل از نظرهای تأییدشده و دو عدد واقعی؛ خدمات شماره‌دار، پکیج‌های فروشی و خط
    باشگاه مشتریان؛ کارشناسان با خط ضخیم بالای نام. داده: HomeController + TemplateHomeData (نبود هر تکه ← آن تکه پنهان).
--}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $edCategories = collect($template['menu'] ?? [])->pluck('name')->reject(fn ($n) => $n === 'خدمات')->unique()->take(3)->implode('، '); // «خدمات» = بی دسته
        $edSince = $currentSalonExperienceYears !== null ? (int) now()->year - (int) $currentSalonExperienceYears - 621 : null;
        $edKicker = collect([$edCategories ?: null, $edSince ? 'از سال '.\App\Support\Fa::digits((string) $edSince) : null])->filter()->implode('، ');
        $edServices = collect($services ?? [])->values();
        $edPackages = $template['packages'] ?? [];
        $edOffer = $template['offer'] ?? null;
        $edQuote = $template['quote'] ?? null;
        $edReviews = (int) ($reviewStats['count'] ?? 0);
        $edYears = $currentSalonExperienceYears ?? null;
        $edRewards = (int) ($template['rewards'] ?? 0);
        $edNext = $template['nextFree'] ?? [];
    @endphp
    <style>
        .ed{color:rgb(var(--m-tx))}
        .ed-wrap{max-width:1200px;margin:0 auto;padding:0 16px}
        @media (min-width:768px){.ed-wrap{padding:0 24px}}
        .ed-rule{border-bottom:1px solid rgb(var(--m-tx))}
        .ed-thin{border-bottom:1px solid rgb(var(--m-tx) / .16)}
        .ed-lead-grid{display:grid;gap:32px;padding:32px 0}
        @media (min-width:1024px){.ed-lead-grid{grid-template-columns:1.25fr 1px .75fr;gap:40px;padding:44px 0}}
        .ed-vrule{display:none;background:rgb(var(--m-tx))}
        @media (min-width:1024px){.ed-vrule{display:block}}
        .ed-kicker{margin:0;font-size:15px;font-weight:700;color:rgb(var(--m-accent2))}
        .ed h1{margin:12px 0 0;font-family:var(--m-display);font-weight:700;font-size:38px;line-height:1.4}
        @media (min-width:768px){.ed h1{font-size:60px;line-height:1.35}}
        .ed-lead{margin:16px 0 0;font-size:17.5px;line-height:2;color:rgb(var(--m-mu));max-width:54ch}
        .ed-cover{margin-top:26px;height:220px}
        @media (min-width:768px){.ed-cover{height:300px}}
        .ed-offer{background:rgb(var(--m-foot-bg));color:#fff;padding:24px;border:1px solid rgb(255 255 255 / .08)}
        .ed-offer span{font-size:13px;color:rgb(var(--m-foot-ac))}
        .ed-offer b{display:block;font-family:var(--m-display);font-size:32px;margin-top:6px;line-height:1.5}
        .ed-offer p{margin:6px 0 14px;font-size:14px;opacity:.8}
        .ed-offer a{display:inline-block;background:rgb(var(--m-foot-ac));color:var(--m-foot-on);padding:10px 20px;font-weight:800}
        .ed-quote{margin:8px 0 0;font-family:var(--m-display);font-size:23px;line-height:1.7}
        .ed-num b{font-family:var(--m-display);font-size:32px}
        .ed-num div{font-size:13px;color:rgb(var(--m-mu))}
        .ed h2{margin:0 0 8px;font-family:var(--m-display);font-weight:700;font-size:32px}
        @media (min-width:768px){.ed h2{font-size:36px}}
        .ed-li{display:grid;grid-template-columns:40px 1fr auto;gap:12px;align-items:baseline;padding:14px 0;border-bottom:1px solid rgb(var(--m-tx) / .16);color:rgb(var(--m-tx))}
        .ed-li:hover b.ed-li__name{color:rgb(var(--m-gold))}
        .ed-li__no{font-family:var(--m-display);font-size:19px;color:rgb(var(--m-accent2))}
        .ed-small{font-size:13px;color:rgb(var(--m-mu))}
        .ed-pkg{padding:16px 0;border-bottom:1px solid rgb(var(--m-tx) / .16);display:flex;justify-content:space-between;align-items:center;gap:16px;color:rgb(var(--m-tx))}
        .ed-pkg:hover b.ed-pkg__name{color:rgb(var(--m-gold))}
        .ed-club{margin:14px 0 0;font-size:14px;color:rgb(var(--m-accent2))}
        .ed-person{color:rgb(var(--m-tx));display:block}
        .ed-person__art{height:200px}
        @media (min-width:768px){.ed-person__art{height:220px}}
        .ed-person__name{padding-top:12px;border-top:3px solid rgb(var(--m-tx));margin-top:12px;font-family:var(--m-display);font-size:22px}
        .ed-person small{font-size:13.5px;color:rgb(var(--m-gold))}
    </style>

    <div class="ed" dir="rtl" data-template-home="editorial">
        <div class="ed-wrap">
            <section class="ed-lead-grid ed-rule" aria-labelledby="ed-title">
                <div>
                    @if ($edKicker !== '')
                        <p class="ed-kicker">{{ $edKicker }}</p>
                    @endif
                    <h1 id="ed-title">{{ $currentSalonTagline }}</h1>
                    @if ($currentSalonBio)
                        <p class="ed-lead">{{ \Illuminate\Support\Str::limit($currentSalonBio, 220) }}</p>
                    @endif
                    <x-palette-art class="ed-cover" :src="$template['cover'] ?? null" :variant="1" :alt="$currentSalonName" data-ed-cover />
                </div>
                <div class="ed-vrule" aria-hidden="true"></div>
                <aside class="flex flex-col gap-[22px]" aria-label="پیشنهاد و نظرها">
                    @if ($edOffer)
                        <div class="ed-offer" data-ed-offer>
                            <span>پیشنهاد ویژه</span>
                            <b>{{ $edOffer['title'] }}</b>
                            <p>کد {{ $edOffer['code'] }}@if ($edOffer['until'])، تا {{ $edOffer['until'] }}@endif</p>
                            <a href="{{ route('bookings.create') }}">رزرو با تخفیف</a>
                        </div>
                    @else
                        <div class="ed-offer" data-ed-book>
                            <span>نوبت آنلاین</span>
                            <b>رزرو در چند ثانیه</b>
                            <p>خدمت، کارشناس و ساعت خالی را خودتان انتخاب کنید.</p>
                            <a href="{{ route('bookings.create') }}">رزرو نوبت</a>
                        </div>
                    @endif
                    @if ($edQuote)
                        <a href="{{ route('salon.reviews') }}" class="block text-m-tx" data-ed-quote>
                            <span class="ed-small">از نظرهای تأییدشده</span>
                            <blockquote class="ed-quote">«{{ $edQuote }}»</blockquote>
                        </a>
                    @endif
                    @if ($edReviews > 0 || $edYears)
                        <div class="flex gap-[26px] pt-4 border-t border-m-tx/15" data-ed-numbers>
                            @if ($edReviews > 0)
                                <a href="{{ route('salon.reviews') }}" class="ed-num text-m-tx"><b>{{ fa_num($reviewStats['avg'], 1) }}</b><div>از {{ fa_num($edReviews) }} نظر</div></a>
                            @endif
                            @if ($edYears)
                                <div class="ed-num"><b>{{ fa_num($edYears) }}</b><div>سال سابقه</div></div>
                            @endif
                        </div>
                    @endif
                </aside>
            </section>

            @if ($edServices->isNotEmpty() || $edPackages !== [])
                <section class="grid gap-12 py-11 ed-rule {{ $edServices->isNotEmpty() && $edPackages !== [] ? 'lg:grid-cols-2' : '' }}">
                    @if ($edServices->isNotEmpty())
                        <div data-ed-services>
                            <h2>خدمات</h2>
                            <ol class="list-none m-0 p-0 {{ $edPackages === [] ? 'lg:columns-2 lg:gap-12' : '' }}">
                                @foreach ($edServices as $service)
                                    <li class="break-inside-avoid">
                                        <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="ed-li">
                                            <span class="ed-li__no">{{ fa_num(str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT)) }}</span>
                                            <span><b class="ed-li__name block">{{ $service->name }}</b><span class="ed-small">{{ fa_num($service->duration) }} دقیقه</span></span>
                                            <b>{{ fa_money($service->price) }}</b>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                            @if (($template['serviceCount'] ?? 0) > $edServices->count())
                                <a href="{{ route('services.index') }}" class="inline-block mt-4 font-bold text-m-gold">همه‌ی {{ fa_num($template['serviceCount']) }} خدمت</a>
                            @endif
                        </div>
                    @endif
                    @if ($edPackages !== [])
                        <div data-ed-packages>
                            <h2>پکیج‌ها</h2>
                            @foreach ($edPackages as $package)
                                <a href="{{ route('courses.show', $package['id']) }}" class="ed-pkg">
                                    <span>
                                        <b class="ed-pkg__name block text-[17px]">{{ $package['name'] }}</b>
                                        <span class="ed-small">{{ fa_num($package['sessions']) }} جلسه@if ($package['interval'])، {{ $package['interval'] }}@endif</span>
                                    </span>
                                    <span class="text-left shrink-0">
                                        @if ($package['list'])
                                            <span class="ed-small line-through">{{ fa_money($package['list']) }}</span>
                                        @endif
                                        <b class="block">{{ fa_money($package['price']) }}</b>
                                    </span>
                                </a>
                            @endforeach
                            @if ($edRewards > 0)
                                <p class="ed-club" data-ed-club>
                                    باشگاه مشتریان: با هر نوبت امتیاز بگیرید و با {{ fa_num($edRewards) }} جایزه عوض کنید.
                                    <a href="{{ route('loyalty.index') }}" class="underline">باشگاه من</a>
                                </p>
                            @endif
                        </div>
                    @endif
                </section>
            @endif

            @if ($edPackages === [] && $edRewards > 0)
                <p class="ed-club py-5 ed-thin" data-ed-club>
                    باشگاه مشتریان: با هر نوبت امتیاز بگیرید و با {{ fa_num($edRewards) }} جایزه عوض کنید.
                    <a href="{{ route('loyalty.index') }}" class="underline">باشگاه من</a>
                </p>
            @endif

            @if (collect($specialists ?? [])->isNotEmpty())
                <section class="pt-11 pb-14" aria-labelledby="ed-team-title">
                    <h2 id="ed-team-title" class="!mb-5">کارشناسان</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($specialists as $specialist)
                            <a href="{{ route('specialists.show', $specialist) }}" class="ed-person" data-ed-person>
                                <x-palette-art class="ed-person__art" :src="$specialist->photoUrl()" :variant="$loop->index + 2" :alt="$specialist->name" />
                                <div class="ed-person__name">{{ $specialist->name }}</div>
                                @if (isset($edNext[$specialist->id]))
                                    <small>نوبت خالی: {{ $edNext[$specialist->id]['text'] }}</small>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
