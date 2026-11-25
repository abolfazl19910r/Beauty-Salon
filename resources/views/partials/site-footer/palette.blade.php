{{--
    بسته‌ی ۵الف — پانویس قالب‌های پولی (قاعده‌ی مشترک ۲ بوم «۴ قالب ترکیبی»): همیشه تیره و به رنگ پالت (--m-foot-bg از
    partials/theme-tokens)، نشان پیش‌فرض به رنگ روشن پالت، دکمه‌ی «دریافت اپ» با رنگ روشن پالت. همان داده‌ی پانویس پیش‌فرض:
    درباره، ساعت کاری، تماس + مسیریابی و نقشه (همان data-location-* و اسکریپت layouts/app)، اپ سالن، قوانین پلتفرم.
--}}
@php
    $__footSalon = app(\App\Support\CurrentSalon::class)->get();
    $__directions = $__footSalon ? \App\Support\SalonMapLocation::directionsUrl($__footSalon) : null;
    $__osm = $__footSalon ? \App\Support\SalonMapLocation::osmEmbedUrl($__footSalon) : null;
@endphp
<footer class="m-foot mt-auto" data-palette-footer>
    <div class="max-w-[1200px] mx-auto px-6 pt-11 pb-7 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr] gap-8 text-sm leading-7">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="m-foot__mark"><x-brand-emblem class="h-8 w-auto max-w-[120px]" loading="lazy" /></span>
                <b class="m-foot__name">{{ $currentSalonName }}</b>
            </div>
            @if ($currentSalonBio)
                <p class="mt-2.5 m-foot__dim max-w-[34ch]">{{ \Illuminate\Support\Str::limit($currentSalonBio, 160) }}</p>
            @endif
            @if (($__reviewsCount ?? 0) > 0)
                <a href="{{ route('salon.reviews') }}" class="inline-block mt-2 underline underline-offset-4 m-foot__dim hover:text-white" data-footer-reviews>نظر مشتری‌ها ({{ fa_num($__reviewsCount) }})</a>
            @endif
        </div>
        <div>
            <b class="m-foot__h">ساعت کاری</b>
            <div class="m-foot__dim persian-number">
                @forelse ($currentSalonHours as $line)
                    <div>{{ $line }}</div>
                @empty
                    <div>برای هماهنگی با سالن تماس بگیرید.</div>
                @endforelse
            </div>
        </div>
        <div>
            <b class="m-foot__h">تماس</b>
            <div class="m-foot__dim persian-number">
                @if ($currentSalonAddress)
                    <div>{{ \App\Support\Fa::digits($currentSalonAddress) }}</div>
                @endif
                @if ($currentSalonPhone)
                    <a href="tel:{{ $currentSalonPhone }}" class="hover:underline" dir="ltr">{{ \App\Support\Fa::digits($currentSalonPhone) }}</a>
                @endif
                @unless ($currentSalonAddress || $currentSalonPhone)
                    <div>اطلاعات تماس سالن به‌زودی اضافه می‌شود.</div>
                @endunless
            </div>
            @if ($__directions || $__osm)
                <div class="flex flex-wrap gap-2 mt-3" data-salon-location>
                    @if ($__directions)
                        <a href="{{ $__directions }}" target="_blank" rel="noopener" class="m-foot__btn" data-location-directions>مسیریابی</a>
                    @endif
                    @if ($__osm)
                        <button type="button" class="m-foot__ghost" data-location-map-load aria-expanded="false">نمایش نقشه</button>
                    @endif
                </div>
                @if ($__osm)
                    <div class="hidden mt-3 rounded-xl overflow-hidden h-44 relative" data-location-map data-src="{{ $__osm }}"></div>
                @endif
            @endif
        </div>
        @if ($__footSalon)
            <div data-salon-app-promo>
                <b class="m-foot__h">اپ سالن</b>
                <div class="m-foot__dim">رزرو سریع‌تر و یادآوری نوبت</div>
                <a href="{{ \App\Support\CustomerAppLink::forSalon($__footSalon)['install_url'] }}" class="m-foot__btn mt-2 inline-block">دریافت اپ</a>
            </div>
        @endif
    </div>
    <div class="max-w-[1200px] mx-auto px-6 pt-3.5 pb-6 m-foot__bar flex flex-col sm:flex-row items-center justify-between gap-2 text-[12.5px]">
        <span>© {{ fa_jdate(now(), 'Y') }} {{ $currentSalonTitle }}</span>
        <div class="m-foot__legal">@include('partials.platform-legal-links')</div>
    </div>
</footer>
