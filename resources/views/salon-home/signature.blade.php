@extends('layouts.app')

{{--
    ⭐ بسته‌ی ۵الف — قالب «شاهکار» (signature = بوتیک + تقویم + پرتره؛ بوم «۴ قالب ترکیبی»، پیش‌نمایش رز موکا تیره):
    hero با قاب دوخطی نازک و نور ملایم رنگ اصلی، تیتر نسخ از tagline سالن، سه عدد فقط اگر واقعی‌اند؛ کنارش فرم کامل رزرو
    (خدمت، کارشناس «فرقی نمی‌کند»، نوار ۵ روز کاری، ساعت‌های خالی) که با همه‌ی انتخاب‌ها به bookings.create می‌رود؛ «منوی خدمات»
    دوستونه‌ی چاپی؛ کارشناسان در قاب طاقی (عکس یا طرح پالت) با «نوبت خالی: …». داده: HomeController + TemplateHomeData.
--}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $sgServices = collect($template['allServices'] ?? []);
        $sgDefault = $template['defaultService'] ?? null;
        $sgDays = $template['bookingDays'] ?? [];
        $sgFirstDay = collect($sgDays)->first(fn ($d) => $d['slots'] !== []);
        $sgFirstSlot = $sgFirstDay['slots'][0] ?? null;
        $sgCategories = collect($template['menu'] ?? [])->pluck('name')->unique()->take(3)->implode('، ');
        $sgYears = $currentSalonExperienceYears ?? null;
        $sgReviews = (int) ($reviewStats['count'] ?? 0);
        $sgNext = $template['nextFree'] ?? [];
        $sgSpecialistCount = (int) ($template['specialistCount'] ?? 0);
    @endphp
    <style>
        .sg{font-weight:300;color:rgb(var(--m-tx))}
        .sg-hero{position:relative;margin:0 16px;padding:40px 22px;background:radial-gradient(60% 90% at 75% 0%,rgb(var(--m-gold) / .26) 0%,transparent 70%),rgb(var(--m-bg));display:grid;gap:36px;align-items:center}
        @media (min-width:1024px){.sg-hero{margin:0 32px;padding:70px;grid-template-columns:1fr 460px;gap:60px}}
        .sg-frame{position:absolute;inset:12px;border:1px solid rgb(var(--m-gold) / .33);pointer-events:none}
        .sg-frame::after{content:"";position:absolute;inset:6px;border:1px solid rgb(var(--m-gold) / .14)}
        @media (min-width:1024px){.sg-frame{inset:22px}}
        .sg-kicker{font-size:14px;color:rgb(var(--m-accent2))}
        .sg h1{font-family:var(--m-display);font-weight:500;font-size:40px;line-height:1.45;margin:12px 0 0}
        @media (min-width:768px){.sg h1{font-size:58px}}
        @media (min-width:1280px){.sg h1{font-size:66px;line-height:1.4}}
        .sg-lead{margin-top:16px;font-size:17px;line-height:2;color:rgb(var(--m-mu));max-width:46ch}
        .sg-stat b{font-family:var(--m-display);font-size:30px;font-weight:500;color:rgb(var(--m-gold))}
        .sg-stat div{font-size:13px;color:rgb(var(--m-mu))}
        a.sg-stat:hover div{text-decoration:underline}
        .sg-form{position:relative;background:rgb(var(--m-card));border:1px solid rgb(var(--m-gold) / .27);padding:26px;display:flex;flex-direction:column;gap:16px}
        .sg-form h2{font-family:var(--m-display);font-size:24px;font-weight:500;margin:0}
        .sg-field{display:flex;flex-direction:column;gap:6px;font-size:13px;color:rgb(var(--m-mu))}
        .sg-field select{appearance:none;font:inherit;font-size:15px;font-weight:400;color:rgb(var(--m-tx));background:transparent;border:1px solid rgb(var(--m-gold) / .2);padding:12px 14px;border-radius:0}
        .sg-field select option{background:rgb(var(--m-card));color:rgb(var(--m-tx))}
        .sg-days{display:flex;gap:6px;font-size:14px}
        .sg-chip{flex:1;text-align:center;padding:9px 0;border-radius:12px;border:1px solid rgb(var(--m-gold) / .2);color:rgb(var(--m-tx));background:transparent;font:inherit;cursor:pointer}
        .sg-chip span{display:block;font-size:11.5px;opacity:.8}
        .sg-chip[aria-pressed=true]{background:rgb(var(--m-gold));color:var(--m-on-gold);border-color:transparent}
        .sg-times{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
        .sg-time{text-align:center;padding:10px 0;border-radius:12px;font:inherit;font-size:15px;border:1px solid rgb(var(--m-gold) / .2);color:rgb(var(--m-tx));background:transparent;cursor:pointer}
        .sg-time[aria-pressed=true]{background:rgb(var(--m-tx));color:rgb(var(--m-bg));font-weight:700;border-color:transparent}
        .sg-submit{font:inherit;font-weight:600;font-size:16px;border:0;background:rgb(var(--m-gold));color:var(--m-on-gold);padding:14px;border-radius:999px;text-align:center;cursor:pointer}
        .sg-note{font-size:13px;color:rgb(var(--m-mu));line-height:1.9}
        .sg h2.sg-title{font-family:var(--m-display);font-size:36px;font-weight:500;margin:0}
        @media (min-width:768px){.sg h2.sg-title{font-size:44px}}
        .sg-menu{background:rgb(var(--m-card));border:1px solid rgb(var(--m-gold) / .2);padding:26px 22px;display:grid;gap:30px}
        @media (min-width:768px){.sg-menu{padding:34px 40px;grid-template-columns:1fr 1fr;gap:40px}}
        .sg-menu h3{font-family:var(--m-display);font-size:21px;font-weight:500;color:rgb(var(--m-gold));margin:0 0 4px}
        .sg-row{display:flex;align-items:baseline;gap:10px;padding:11px 0;border-bottom:1px solid rgb(var(--m-gold) / .13);color:rgb(var(--m-tx))}
        .sg-row:hover .sg-row__name{color:rgb(var(--m-gold))}
        .sg-row__name{font-size:16.5px}
        .sg-row__dur{font-size:13px;color:rgb(var(--m-mu));white-space:nowrap}
        .sg-row__price{margin-inline-start:auto;color:rgb(var(--m-accent2));white-space:nowrap}
        .sg-arch{height:230px;border-radius:120px 120px 4px 4px}
        .sg-person{display:block;color:rgb(var(--m-tx))}
        .sg-person b{display:block;margin-top:12px;font-family:var(--m-display);font-size:21px;font-weight:500}
        .sg-person span{font-size:13.5px;color:rgb(var(--m-gold))}
    </style>

    <div class="sg mx-[calc(50%-50vw)] w-screen pt-2" dir="rtl" data-template-home="signature">
        <section class="sg-hero" aria-labelledby="sg-title">
            <div class="sg-frame" aria-hidden="true"></div>
            <div class="relative">
                @if ($sgCategories !== '')
                    <span class="sg-kicker">{{ $sgCategories }}</span>
                @endif
                <h1 id="sg-title">{{ $currentSalonTagline }}</h1>
                <p class="sg-lead">{{ \Illuminate\Support\Str::limit($currentSalonBio, 170) }}</p>
                <div class="flex flex-wrap gap-8 mt-7" data-sg-stats>
                    @if ($sgReviews > 0)
                        <a href="{{ route('salon.reviews') }}" class="sg-stat" data-sg-reviews><b>{{ fa_num($reviewStats['avg'], 1) }}</b><div>از {{ fa_num($sgReviews) }} نظر</div></a>
                    @endif
                    @if ($sgYears)
                        <div class="sg-stat"><b>{{ fa_num($sgYears) }}</b><div>سال سابقه</div></div>
                    @endif
                    @if ($sgSpecialistCount > 0)
                        <div class="sg-stat"><b>{{ fa_num($sgSpecialistCount) }}</b><div>کارشناس</div></div>
                    @endif
                </div>
            </div>

            @if ($sgDefault)
                <form method="GET" action="{{ route('bookings.create') }}" class="sg-form" aria-label="رزرو نوبت" data-sg-form
                      data-slots-url="{{ route('salon.free-slots') }}">
                    <h2>رزرو نوبت</h2>
                    <label class="sg-field">خدمت
                        <select name="service" data-sg-service>
                            @foreach ($sgServices as $s)
                                <option value="{{ $s['id'] }}" @selected($s['id'] === (int) $sgDefault->id)>{{ $s['name'] }}، {{ fa_num($s['duration']) }} دقیقه</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="sg-field">کارشناس
                        <select data-sg-specialist>
                            <option value="">فرقی نمی‌کند</option>
                            @foreach ($template['serviceSpecialists'] ?? [] as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <input type="hidden" name="specialist" value="{{ $sgFirstSlot['specialist_id'] ?? '' }}" data-sg-specialist-id>
                    <input type="hidden" name="date" value="{{ $sgFirstDay['date'] ?? '' }}" data-sg-date>
                    <input type="hidden" name="time" value="{{ $sgFirstSlot['time'] ?? '' }}" data-sg-time>

                    <div class="sg-days" role="group" aria-label="روز" data-sg-days>
                        @foreach ($sgDays as $day)
                            <button type="button" class="sg-chip" data-sg-day="{{ $day['date'] }}" aria-pressed="{{ ($sgFirstDay['date'] ?? null) === $day['date'] ? 'true' : 'false' }}">
                                <span>{{ $day['label'] }}</span><b>{{ $day['day'] }}</b>
                            </button>
                        @endforeach
                    </div>
                    <div class="sg-times" role="group" aria-label="ساعت" data-sg-times>
                        @foreach (array_slice($sgFirstDay['slots'] ?? [], 0, 8) as $slot)
                            <button type="button" class="sg-time" data-sg-slot="{{ $slot['time'] }}" data-sg-slot-specialist="{{ $slot['specialist_id'] }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $slot['label'] }}</button>
                        @endforeach
                    </div>
                    <p class="sg-note {{ $sgFirstSlot ? 'hidden' : '' }}" data-sg-empty>در چند روز آینده ساعت خالی برای این خدمت نیست؛ روز دیگری را در فرم رزرو ببینید.</p>
                    <button type="submit" class="sg-submit" data-sg-submit>
                        @if ($sgFirstSlot)
                            ادامه و ثبت، {{ $sgFirstDay['label'] }} {{ $sgFirstSlot['label'] }}
                        @else
                            انتخاب روز و ساعت
                        @endif
                    </button>
                </form>
            @else
                <div class="sg-form">
                    <h2>رزرو نوبت</h2>
                    <p class="sg-note">خدمات سالن به‌زودی اضافه می‌شود.</p>
                </div>
            @endif
        </section>

        @if (collect($template['menu'] ?? [])->isNotEmpty())
            <section class="max-w-[1100px] mx-auto px-4 md:px-8 pt-20 pb-10 grid gap-10 lg:grid-cols-[1fr_1.6fr] lg:gap-14 items-start" aria-labelledby="sg-menu-title">
                <div>
                    <h2 class="sg-title" id="sg-menu-title">منوی خدمات</h2>
                    <p class="mt-2.5 leading-8 text-m-mu">قیمت و مدت هر خدمت؛ برای رزرو روی خدمت بزنید.</p>
                    @if (($template['serviceCount'] ?? 0) > 0)
                        <a href="{{ route('services.index') }}" class="inline-block mt-3 text-m-gold">همه‌ی {{ fa_num($template['serviceCount']) }} خدمت</a>
                    @endif
                </div>
                <div class="sg-menu">
                    @foreach ($template['menu'] as $group)
                        <div>
                            <h3>{{ $group['name'] }}</h3>
                            @foreach ($group['services'] as $service)
                                <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="sg-row">
                                    <span class="sg-row__name">{{ $service->name }}</span>
                                    <span class="sg-row__dur">{{ fa_num($service->duration) }} دقیقه</span>
                                    <span class="sg-row__price">{{ fa_money($service->price) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (collect($specialists ?? [])->isNotEmpty())
            <section class="max-w-[1100px] mx-auto px-4 md:px-8 pt-10 pb-20" aria-label="کارشناسان">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($specialists as $specialist)
                        <a href="{{ route('specialists.show', $specialist) }}" class="sg-person" data-sg-person>
                            <x-palette-art class="sg-arch" :src="$specialist->photoUrl()" :variant="$loop->index" :alt="$specialist->name" />
                            <b>{{ $specialist->name }}</b>
                            @if (isset($sgNext[$specialist->id]))
                                <span>نوبت خالی: {{ $sgNext[$specialist->id]['text'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @if ($sgDefault)
        <script @nonce>
            (function () {
                // فرم رزرو «شاهکار»: عوض کردن خدمت/کارشناس ← salon.free-slots؛ زدن روز و ساعت ← فیلدهای پنهان و متن دکمه
                var form = document.querySelector('[data-sg-form]');
                if (!form) return;
                var days = [], $ = function (s) { return form.querySelector(s); };
                var service = $('[data-sg-service]'), who = $('[data-sg-specialist]'), daysBox = $('[data-sg-days]'), timesBox = $('[data-sg-times]');
                var fDate = $('[data-sg-date]'), fTime = $('[data-sg-time]'), fSpec = $('[data-sg-specialist-id]'), submit = $('[data-sg-submit]'), empty = $('[data-sg-empty]');
                var label = {};

                function button(cls, attrs, html) {
                    var b = document.createElement('button');
                    b.type = 'button'; b.className = cls; b.innerHTML = html;
                    Object.keys(attrs).forEach(function (k) { b.setAttribute(k, attrs[k]); });
                    return b;
                }
                function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
                function update(day, slot) {
                    fDate.value = day ? day.date : '';
                    fTime.value = slot ? slot.time : '';
                    fSpec.value = slot ? (who.value || slot.specialist_id) : (who.value || '');
                    submit.textContent = slot ? 'ادامه و ثبت، ' + day.label + ' ' + slot.label : 'انتخاب روز و ساعت';
                    empty.classList.toggle('hidden', !!slot);
                }
                function renderTimes(day) {
                    timesBox.innerHTML = '';
                    var slots = day ? day.slots.slice(0, 8) : [];
                    slots.forEach(function (slot, i) {
                        var b = button('sg-time', { 'aria-pressed': i === 0 ? 'true' : 'false' }, esc(slot.label));
                        b.addEventListener('click', function () {
                            timesBox.querySelectorAll('.sg-time').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
                            b.setAttribute('aria-pressed', 'true');
                            update(day, slot);
                        });
                        timesBox.appendChild(b);
                    });
                    update(day, slots[0] || null);
                }
                function renderDays() {
                    daysBox.innerHTML = '';
                    var first = days.find(function (d) { return d.slots.length; }) || null;
                    days.forEach(function (day) {
                        var b = button('sg-chip', { 'aria-pressed': day === first ? 'true' : 'false' }, '<span>' + esc(day.label) + '</span><b>' + esc(day.day) + '</b>');
                        b.addEventListener('click', function () {
                            daysBox.querySelectorAll('.sg-chip').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
                            b.setAttribute('aria-pressed', 'true');
                            renderTimes(day);
                        });
                        daysBox.appendChild(b);
                    });
                    renderTimes(first);
                }
                function load(withSpecialists) {
                    var url = form.dataset.slotsUrl + '?service=' + encodeURIComponent(service.value) + (!withSpecialists && who.value ? '&specialist=' + encodeURIComponent(who.value) : '');
                    submit.disabled = true;
                    if (withSpecialists) who.innerHTML = '<option value="">فرقی نمی‌کند</option>';
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : { days: [] }; })
                        .then(function (data) {
                            // کارشناسان همین خدمت از همان پاسخ (مسیر بی ورود)
                            if (withSpecialists) (data.specialists || []).forEach(function (sp) { var o = document.createElement('option'); o.value = sp.id; o.textContent = sp.name; who.appendChild(o); });
                            days = Array.isArray(data.days) ? data.days : []; renderDays();
                        })
                        .catch(function () { days = []; renderDays(); })
                        .finally(function () { submit.disabled = false; });
                }
                service.addEventListener('change', function () { load(true); });
                who.addEventListener('change', function () { load(false); });
                // نمایش اول سمت سرور است؛ فقط رفتار دکمه‌ها وصل می‌شود
                days = @json($sgDays);
                renderDays();
            })();
        </script>
    @endif
@endsection
