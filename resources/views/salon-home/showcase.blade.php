@extends('layouts.app')

{{--
    ⭐ بسته‌ی ۵ب — قالب «نمایشگاه» (showcase = استودیو + گالری + کاتالوگ؛ بوم «۴ قالب ترکیبی»، پیش‌نمایش فیروزه روشن):
    دو ستون؛ یک سو متن (تیتر کوفی از tagline، کارت «نزدیک‌ترین نوبت خالی»، کاتالوگ خدمات با تراشه‌ی دسته و «N نمونه‌کار»،
    کارشناسان با «نوبت خالی: …») و سوی دیگر قاب ثابت گالری: عکس‌های گالری به‌ترتیب با نام کارشناس و «این خدمت را با همین
    کارشناس رزرو کنید» اگر عکس به خدمت/کارشناس پیوند دارد؛ بی عکس ← کاور سالن ← طرح پالت. موبایل: قاب بعد از کارت نوبت.
    داده: HomeController + TemplateHomeData (فقط داده‌ی واقعی؛ نبود هر تکه ← آن تکه پنهان).
--}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $scServices = collect($template['allServices'] ?? [])->take(12)->values();
        $scCategories = collect($template['categories'] ?? []);
        $scChips = $scServices->pluck('category_id')->filter()->unique()
            ->filter(fn ($id) => $scCategories->has($id))->take(5)->values();
        $scSamples = $template['galleryByService'] ?? [];
        $scNearest = $template['nearest'] ?? null;
        $scNext = $template['nextFree'] ?? [];
        $scCover = $template['cover'] ?? null;
        $scFrames = collect($template['frames'] ?? [])->map(function (array $f) {
            $f['book'] = route('bookings.create', array_filter(['service' => $f['service_id'], 'specialist' => $f['specialist_id']]));
            $f['cta'] = $f['service_id']
                ? ($f['specialist_id'] ? 'این خدمت را با همین کارشناس رزرو کنید' : 'این خدمت را رزرو کنید')
                : 'نوبت بعدی‌تان را رزرو کنید';
            $f['caption'] = $f['service'] ?: $f['title'];

            return $f;
        })->values();
        $scFirst = $scFrames->first();
    @endphp
    <style>
        .sc{color:rgb(var(--m-tx))}
        .sc-grid{display:grid}
        @media (min-width:1024px){.sc-grid{grid-template-columns:1fr 1fr}}
        .sc-main{padding:0 20px 48px}
        @media (min-width:1024px){.sc-main{padding:0 60px 60px}}
        .sc-sec{padding:36px 0;border-bottom:1px solid var(--m-line)}
        .sc-hero{padding:32px 0 40px;border-bottom:1px solid var(--m-line)}
        @media (min-width:1024px){.sc-hero{padding-top:52px}}
        .sc h1{font-family:var(--m-display);font-weight:800;font-size:32px;line-height:1.6;margin:0}
        @media (min-width:768px){.sc h1{font-size:46px;line-height:1.55}}
        .sc-lead{margin-top:14px;font-size:16.5px;line-height:2;color:rgb(var(--m-mu))}
        .sc-next{margin-top:22px;background:rgb(var(--m-card));border:1px solid var(--m-line);border-radius:22px;padding:18px 20px;display:flex;justify-content:space-between;align-items:center;gap:14px}
        .sc-next span{font-size:13.5px;color:rgb(var(--m-mu))}
        .sc-next b{display:block;font-size:22px}
        .sc-btn{background:rgb(var(--m-gold));color:var(--m-on-gold);font-weight:800;padding:13px 26px;border-radius:14px;white-space:nowrap}
        .sc-btn:hover{filter:brightness(1.06)}
        .sc h2{font-family:var(--m-display);font-weight:700;font-size:24px;margin:0}
        .sc-chips{display:flex;flex-wrap:wrap;gap:6px}
        .sc-chip{padding:6px 14px;border-radius:999px;font:inherit;font-size:13.5px;color:rgb(var(--m-tx));border:1px solid var(--m-line);background:transparent;cursor:pointer}
        .sc-chip[aria-pressed=true]{background:rgb(var(--m-tx));color:rgb(var(--m-bg));border-color:transparent}
        .sc-row{display:grid;grid-template-columns:52px 1fr auto;gap:14px;align-items:center;padding:13px 0;border-bottom:1px dashed var(--m-line);color:rgb(var(--m-tx))}
        .sc-row[hidden]{display:none}
        .sc-row:hover b{color:rgb(var(--m-gold))}
        .sc-row small{font-size:13px;color:rgb(var(--m-mu))}
        .sc-thumb{width:52px;height:52px;border-radius:14px}
        .sc-person{display:flex;gap:12px;align-items:center;padding:12px;border-radius:18px;background:rgb(var(--m-card));border:1px solid var(--m-line);color:rgb(var(--m-tx))}
        .sc-person:hover{border-color:rgb(var(--m-gold) / .5)}
        .sc-person small{font-size:13px;color:rgb(var(--m-gold))}
        .sc-face{width:56px;height:56px;border-radius:16px;flex:none}
        .sc-aside{padding:0 20px}
        @media (min-width:1024px){.sc-aside{padding:16px 0 16px 16px}}
        .sc-frame{position:relative;height:440px;border-radius:30px;overflow:hidden}
        @media (min-width:1024px){.sc-frame{position:sticky;top:92px;height:calc(100vh - 108px);min-height:560px;max-height:1300px}}
        .sc-frame__art{position:absolute;inset:0;border-radius:30px}
        .sc-frame__over{position:absolute;inset:0;padding:22px;display:flex;flex-direction:column;justify-content:space-between;color:#fff;background:linear-gradient(to top,rgb(0 0 0 / .38),transparent 45%)}
        @media (min-width:1024px){.sc-frame__over{padding:34px}}
        .sc-pill{background:rgb(0 0 0 / .28);padding:6px 14px;border-radius:999px;font-size:13.5px}
        .sc-pill--light{background:rgb(255 255 255 / .2)}
        .sc-thumbs{display:flex;gap:10px;overflow-x:auto;padding:4px 3px}
        .sc-tn{width:64px;height:64px;border-radius:14px;flex:none;padding:0;border:0;cursor:pointer;opacity:.75;overflow:hidden;background:transparent}
        .sc-tn[aria-pressed=true]{opacity:1;box-shadow:0 0 0 3px #fff}
        .sc-tn img{width:100%;height:100%;object-fit:cover}
        .sc-glass{background:rgb(255 255 255 / .14);backdrop-filter:blur(8px);border:1px solid rgb(255 255 255 / .25);border-radius:20px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px}
        .sc-glass a{background:#fff;color:rgb(var(--m-foot-bg));font-weight:800;padding:10px 20px;border-radius:12px;white-space:nowrap}
    </style>

    <div class="sc pt-1" dir="rtl" data-template-home="showcase">
        <div class="sc-grid">
            <div class="sc-main">
                <section class="sc-hero" aria-labelledby="sc-title">
                    <h1 id="sc-title">{{ $currentSalonTagline }}</h1>
                    @if ($currentSalonBio)
                        <p class="sc-lead">{{ \Illuminate\Support\Str::limit($currentSalonBio, 190) }}</p>
                    @endif
                    <div class="sc-next" data-sc-next>
                        @if ($scNearest)
                            <div><span>نزدیک‌ترین نوبت خالی</span><b>{{ $scNearest['day_label'] }}، {{ $scNearest['label'] }}</b></div>
                            <a href="{{ route('bookings.create', ['specialist' => $scNearest['specialist_id'], 'date' => $scNearest['date'], 'time' => $scNearest['time']]) }}" class="sc-btn">رزرو نوبت</a>
                        @else
                            <div><span>نوبت آنلاین</span><b>روز و ساعت دلخواه</b></div>
                            <a href="{{ route('bookings.create') }}" class="sc-btn">رزرو نوبت</a>
                        @endif
                    </div>
                </section>

                {{-- موبایل و تبلت: قاب گالری همین‌جا؛ از lg به بالا در ستون کناری --}}
                <div class="lg:hidden pt-8" data-sc-frame-slot="mobile"></div>

                @if ($scServices->isNotEmpty())
                    <section class="sc-sec" aria-labelledby="sc-services-title" data-sc-services>
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-2">
                            <h2 id="sc-services-title">خدمات</h2>
                            @if ($scChips->count() > 1)
                                <div class="sc-chips" role="group" aria-label="دسته‌ی خدمات">
                                    <button type="button" class="sc-chip" data-sc-cat="" aria-pressed="true">همه</button>
                                    @foreach ($scChips as $catId)
                                        <button type="button" class="sc-chip" data-sc-cat="{{ $catId }}" aria-pressed="false">{{ $scCategories[$catId] }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @foreach ($scServices as $s)
                            @php $scCount = (int) ($scSamples[$s['id']] ?? 0); @endphp
                            <a href="{{ route('bookings.create', ['service' => $s['id']]) }}" class="sc-row" data-sc-service data-sc-service-cat="{{ $s['category_id'] }}">
                                <x-palette-art class="sc-thumb" :src="$s['image'] ?? null" :variant="$loop->index" />
                                <span>
                                    <b class="block">{{ $s['name'] }}</b>
                                    <small>{{ fa_num($s['duration']) }} دقیقه@if ($scCount > 0)، <span data-sc-samples>{{ fa_num($scCount) }} نمونه‌کار</span>@endif</small>
                                </span>
                                <span class="text-[15px] whitespace-nowrap">از <b>{{ fa_money($s['price']) }}</b></span>
                            </a>
                        @endforeach
                        @if (($template['serviceCount'] ?? 0) > $scServices->count())
                            <a href="{{ route('services.index') }}" class="inline-block mt-4 text-m-gold font-bold">همه‌ی {{ fa_num($template['serviceCount']) }} خدمت</a>
                        @endif
                    </section>
                @endif

                @if (collect($specialists ?? [])->isNotEmpty())
                    <section class="pt-9" aria-labelledby="sc-team-title">
                        <h2 id="sc-team-title" class="mb-4">کارشناسان</h2>
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach ($specialists as $specialist)
                                <a href="{{ route('specialists.show', $specialist) }}" class="sc-person" data-sc-person>
                                    <x-palette-art class="sc-face" :src="$specialist->photoUrl()" :variant="$loop->index + 3" :alt="$specialist->name" />
                                    <span class="min-w-0">
                                        <b class="block truncate">{{ $specialist->name }}</b>
                                        @if (isset($scNext[$specialist->id]))
                                            <small>نوبت خالی: {{ $scNext[$specialist->id]['text'] }}</small>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="sc-aside hidden lg:block" aria-label="نمونه‌کارها" data-sc-frame-slot="desktop">
                <div class="sc-frame" data-sc-frame>
                    <x-palette-art class="sc-frame__art" :src="$scFirst['image'] ?? $scCover" :alt="$scFirst['caption'] ?? $currentSalonName" data-sc-frame-art />
                    <div class="sc-frame__over">
                        <div class="flex flex-wrap justify-between items-center gap-2">
                            @if ($scFirst)
                                <span class="sc-pill" data-sc-caption>نمونه‌کار {{ fa_num(1) }} از {{ fa_num($scFrames->count()) }}، {{ $scFirst['caption'] }}</span>
                                <span class="sc-pill sc-pill--light {{ $scFirst['specialist'] ? '' : 'hidden' }}" data-sc-expert>{{ $scFirst['specialist'] }}</span>
                            @else
                                <span class="sc-pill">{{ $currentSalonName }}</span>
                            @endif
                        </div>
                        <div class="flex flex-col gap-3.5">
                            @if ($scFrames->count() > 1)
                                <div class="sc-thumbs" role="group" aria-label="نمونه‌کارها">
                                    @foreach ($scFrames as $f)
                                        <button type="button" class="sc-tn" data-sc-thumb="{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="نمونه‌کار {{ fa_num($loop->iteration) }}، {{ $f['caption'] }}">
                                            <img src="{{ $f['image'] }}" alt="" loading="lazy">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="sc-glass">
                                <span data-sc-cta>{{ $scFirst['cta'] ?? 'نوبت بعدی‌تان را رزرو کنید' }}</span>
                                <a href="{{ $scFirst['book'] ?? route('bookings.create') }}" data-sc-book>رزرو</a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script @nonce>
        (function () {
            var root = document.querySelector('[data-template-home=showcase]');
            if (!root) return;
            var frame = root.querySelector('[data-sc-frame]');
            var aside = root.querySelector('[data-sc-frame-slot=desktop]');
            var mobileSlot = root.querySelector('[data-sc-frame-slot=mobile]');

            // یک قاب، دو جا: زیر lg بعد از کارت نوبت (ستون کناری پنهان است)، از lg در ستون کناری — بدون تکرار عکس‌ها در صفحه
            var wide = window.matchMedia('(min-width: 1024px)');
            function place() {
                if (wide.matches) { aside.appendChild(frame); } else { mobileSlot.appendChild(frame); }
            }
            place();
            (wide.addEventListener ? wide.addEventListener('change', place) : wide.addListener(place));

            // تراشه‌های دسته‌ی خدمات
            var chips = root.querySelectorAll('[data-sc-cat]');
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                    var cat = chip.getAttribute('data-sc-cat');
                    root.querySelectorAll('[data-sc-service]').forEach(function (row) {
                        row.hidden = cat !== '' && row.getAttribute('data-sc-service-cat') !== cat;
                    });
                });
            });

            // ورق زدن قاب نمونه‌کار
            var frames = @json($scFrames);
            var thumbs = root.querySelectorAll('[data-sc-thumb]');
            if (!frames.length || !thumbs.length) return;
            var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
            var art = frame.querySelector('[data-sc-frame-art]');
            var caption = frame.querySelector('[data-sc-caption]'), expert = frame.querySelector('[data-sc-expert]');
            var cta = frame.querySelector('[data-sc-cta]'), book = frame.querySelector('[data-sc-book]');
            function show(i) {
                var f = frames[i];
                var img = art.querySelector('img');
                if (img) { img.src = f.image; img.alt = f.caption; }
                caption.textContent = 'نمونه‌کار ' + fa(i + 1) + ' از ' + fa(frames.length) + '، ' + f.caption;
                expert.textContent = f.specialist || '';
                expert.classList.toggle('hidden', !f.specialist);
                cta.textContent = f.cta;
                book.href = f.book;
                thumbs.forEach(function (t, k) { t.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
            }
            thumbs.forEach(function (t) {
                t.addEventListener('click', function () { show(parseInt(t.getAttribute('data-sc-thumb'), 10)); });
            });
        })();
    </script>
@endsection
