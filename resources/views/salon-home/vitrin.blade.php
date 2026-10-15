@extends('layouts.app')

{{-- ⭐ بسته‌ی «و» — چیدمان «ویترین»: رزرومحور؛ تیتر کنار تصویر، فرم کوچک رزرو بالای صفحه، خدمات در کارت با زبانه‌ی دسته. --}}

@section('title', $currentSalonName)

@section('full-width', true)

@section('content')
    @php
        $vServices = collect($services ?? []);
        $vSpecialists = collect($specialists ?? []);
        $vCategories = \App\Models\Category::query()->whereIn('id', $vServices->pluck('category_id')->filter()->unique())->pluck('name', 'id');
        $vHero = $vServices->first(fn ($s) => $s->image_url);
    @endphp
    <style>
        .v-hero{display:grid;gap:2.5rem;align-items:center}
        @media (min-width:1024px){.v-hero{grid-template-columns:1.05fr 1fr}}
        .v-book{background:rgb(var(--m-bg2));border:1px solid var(--m-line2);border-radius:22px;padding:14px;display:grid;gap:10px;box-shadow:var(--m-sh-soft)}
        @media (min-width:640px){.v-book{grid-template-columns:1fr auto;align-items:end}}
        .v-book label{font-size:12px;color:rgb(var(--m-mu));font-weight:600;display:block;margin:0 4px 5px}
        .v-book select{width:100%;min-height:46px;border-radius:14px;border:1px solid var(--m-line);background:rgb(var(--m-bg));color:rgb(var(--m-tx));padding:0 12px;font:inherit;font-weight:600}
        .v-col{display:grid;grid-template-columns:1.25fr 1fr;grid-template-rows:1fr 1fr;gap:12px;height:420px}
        .v-ph{background:rgb(var(--m-bg3));border-radius:24px;overflow:hidden;display:flex;align-items:flex-end;color:rgb(var(--m-mu));font-size:12px}
        .v-ph img{width:100%;height:100%;object-fit:cover}
        .v-card{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:22px;overflow:hidden;transition:transform .3s,box-shadow .3s}
        .v-card:hover{transform:translateY(-3px);box-shadow:var(--m-sh-soft)}
        .v-tab{padding:8px 16px;border-radius:999px;font-size:13px;font-weight:600;border:1px solid var(--m-line2);color:rgb(var(--m-mu));white-space:nowrap}
        .v-tab[aria-pressed=true]{background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold));border-color:transparent}
    </style>

    <div class="mx-[calc(50%-50vw)] w-screen pt-2" dir="rtl">
        <section class="max-w-7xl mx-auto px-4 pt-8 pb-10 v-hero">
            <div>
                @if ($currentSalonHours)
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-m-ok bg-m-bg3 px-4 py-2 rounded-full persian-number">
                        <span class="w-2 h-2 rounded-full bg-m-ok"></span>{{ $currentSalonHours[0] }}
                    </span>
                @endif
                <h1 class="font-serif-fa text-4xl md:text-5xl leading-snug mt-5 mb-4 text-m-tx">{{ $currentSalonTagline }}<br><span class="text-m-gold">{{ $currentSalonName }}</span></h1>
                <p class="text-m-mu leading-8 mb-6 max-w-xl">{{ \Illuminate\Support\Str::limit($currentSalonBio, 170) }}</p>

                <form method="GET" action="{{ route('bookings.create') }}" class="v-book" aria-label="رزرو سریع">
                    <div>
                        <label for="v-service">خدمت</label>
                        <select id="v-service" name="service">
                            <option value="">همه‌ی خدمات</option>
                            @foreach ($vServices as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-gold px-6 py-3 text-sm" style="border-radius:14px;min-height:46px">دیدن ساعت‌های خالی</button>
                </form>

                <div class="flex flex-wrap gap-6 mt-5 text-sm text-m-mu persian-number">
                    @if (($reviewStats['count'] ?? 0) > 0)
                        <span><b class="text-m-tx">{{ to_persian_num(number_format($reviewStats['avg'], 1)) }}</b> از {{ to_persian_num((string) $reviewStats['count']) }} نظر</span>
                    @endif
                    @if ($vSpecialists->isNotEmpty())
                        <span><b class="text-m-tx">{{ to_persian_num((string) $vSpecialists->count()) }}</b> متخصص</span>
                    @endif
                    @if ($currentSalonExperienceYears)
                        <span><b class="text-m-tx">{{ to_persian_num((string) $currentSalonExperienceYears) }}</b> سال تجربه</span>
                    @endif
                </div>
            </div>
            <div class="v-col">
                <div class="v-ph" style="grid-row:span 2">
                    @if ($vHero)<img src="{{ $vHero->image_url }}" alt="{{ $vHero->name }}" loading="lazy">@else<span class="p-3">{{ $currentSalonName }}</span>@endif
                </div>
                @foreach ($vServices->filter(fn ($s) => $s->image_url)->skip(1)->take(2) as $service)
                    <div class="v-ph"><img src="{{ $service->image_url }}" alt="{{ $service->name }}" loading="lazy"></div>
                @endforeach
                @for ($i = $vServices->filter(fn ($s) => $s->image_url)->skip(1)->take(2)->count(); $i < 2; $i++)
                    <div class="v-ph"></div>
                @endfor
            </div>
        </section>

        <section id="services" class="max-w-7xl mx-auto px-4 py-10" data-v-services>
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <h2 class="font-serif-fa text-2xl md:text-3xl text-m-tx">خدمات</h2>
                @if ($vCategories->count() > 1)
                    <div class="flex flex-wrap gap-2" role="group" aria-label="دسته‌ها">
                        <button type="button" class="v-tab" aria-pressed="true" data-v-cat="">همه</button>
                        @foreach ($vCategories as $id => $name)
                            <button type="button" class="v-tab" aria-pressed="false" data-v-cat="{{ $id }}">{{ $name }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse ($vServices as $service)
                    <a href="{{ route('bookings.create', ['service' => $service->id]) }}" class="v-card block" data-v-item="{{ $service->category_id }}">
                        <div class="h-40 bg-m-bg3 flex items-center justify-center text-m-gold/60">
                            @if ($service->image_url)
                                <img src="{{ $service->image_url }}" alt="{{ $service->name }}" loading="lazy" class="w-full h-full object-cover">
                            @else
                                {{-- بی‌عکس: نشانه‌ی رنگ پالت (عکس جایگزین قدیمی طلایی ثابت بود) --}}
                                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="6" cy="7" r="3"/><circle cx="6" cy="17" r="3"/><path d="M8.5 8.5L20 18M8.5 15.5L20 6"/></svg>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-m-tx mb-2">{{ $service->name }}</h3>
                            <div class="flex justify-between text-sm text-m-mu persian-number">
                                <span>{{ to_persian_num((string) $service->duration) }} دقیقه</span>
                                <span class="font-bold text-m-tx">{{ to_persian_num(number_format($service->price)) }} تومان</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-m-mu">فهرست خدمات به‌زودی اضافه می‌شود.</p>
                @endforelse
            </div>
            <div class="mt-6"><a href="{{ route('services.index') }}" class="text-m-gold font-bold text-sm">همه‌ی خدمات و قیمت‌ها ←</a></div>
        </section>

        @if ($vSpecialists->isNotEmpty())
            <section id="specialists" class="max-w-7xl mx-auto px-4 py-10">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-serif-fa text-2xl md:text-3xl text-m-tx">متخصص‌ها</h2>
                    <a href="{{ route('specialists.search') }}" class="text-m-gold font-bold text-sm">همه ←</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($vSpecialists as $specialist)
                        <a href="{{ route('specialists.show', $specialist) }}" class="v-card flex items-center gap-3 p-3">
                            @if ($specialist->photoUrl())
                                <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" loading="lazy" class="w-14 h-14 rounded-full object-cover">
                            @else
                                <span class="w-14 h-14 rounded-full bg-m-gold/15 text-m-gold flex items-center justify-center font-bold text-xl">{{ mb_substr($specialist->name, 0, 1) }}</span>
                            @endif
                            <span><b class="block text-m-tx">{{ $specialist->name }}</b><span class="text-xs text-m-mu">مشاهده‌ی پروفایل و نوبت</span></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="max-w-7xl mx-auto px-4 pb-16 pt-4">
            <div class="v-card p-6 md:p-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-serif-fa text-xl md:text-2xl text-m-tx mb-1">نوبت بگیرید، بی‌تماس و بی‌انتظار</h2>
                    @if ($currentSalonAddress)<p class="text-m-mu text-sm">{{ $currentSalonAddress }}</p>@endif
                </div>
                <a href="{{ route('bookings.create') }}" class="btn-gold px-8 py-3">رزرو نوبت</a>
            </div>
        </section>
    </div>

    @if ($appSalon = app(\App\Support\CurrentSalon::class)->get())
        @include('partials.open-in-app', ['appSalon' => $appSalon])
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-v-cat]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var cat = tab.dataset.vCat;
                document.querySelectorAll('[data-v-cat]').forEach(function (t) { t.setAttribute('aria-pressed', t === tab ? 'true' : 'false'); });
                document.querySelectorAll('[data-v-item]').forEach(function (card) {
                    card.style.display = !cat || card.dataset.vItem === cat ? '' : 'none';
                });
            });
        });
    </script>
@endpush
