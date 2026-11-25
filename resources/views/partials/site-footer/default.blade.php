{{-- پانویس طلایی ماهرو (پیش‌فرض رایگان)؛ قالب‌های پولی: partials/site-footer/palette (بسته‌ی ۵الف) --}}
<footer class="bg-m-bg2 border-t border-m-gold/10 mt-auto">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div>
                @if ($currentSalonLogoUrl)
                    <x-brand-emblem class="h-14 w-auto max-w-[160px] mb-3" loading="lazy" />
                @endif
                <h3 class="font-serif-fa text-xl font-bold text-m-gold2 mb-3">{{ $currentSalonName }}</h3>
                <p class="text-m-tx/60 text-sm leading-7">
                    {{ $currentSalonBio }}
                </p>
            </div>

            <div>
                <h3 class="font-bold mb-4 flex items-center text-m-tx">
                    <svg class="w-5 h-5 ml-1 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    تماس با ما
                </h3>
                <div class="text-m-tx/60 text-sm space-y-2">
                    {{-- ⭐ ۲۰۲۶-۰۹-۲۳: از اطلاعات همین سالن (ViewComposer)، نه متن ثابت مشترک. ایمیل
                         ساختگی مشترک (info@rasta-salon.ir) حذف شد چون مال هیچ سالنی نبود. --}}
                    @if ($currentSalonAddress)
                        <p class="flex items-start">
                            <svg class="w-4 h-4 ml-2 mt-1 shrink-0 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="persian-number">آدرس: {{ \App\Support\Fa::digits($currentSalonAddress) }}</span>
                        </p>
                    @endif
                    @if ($currentSalonPhone)
                        <p class="flex items-center persian-number">
                            <svg class="w-4 h-4 ml-2 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            تلفن: <a href="tel:{{ $currentSalonPhone }}" class="mr-1 hover:text-m-gold2" dir="ltr">{{ \App\Support\Fa::digits($currentSalonPhone) }}</a>
                        </p>
                    @endif
                    @unless ($currentSalonAddress || $currentSalonPhone)
                        <p>اطلاعات تماس سالن به‌زودی اضافه می‌شود.</p>
                    @endunless
                </div>
                {{-- مسیریابی و نقشه در فوتر (آزمون ویندوز بسته‌ی ۴، تصمیم ابوالفضل؛ جای بخش جدای صفحه‌ی اصلی): همان لینکی که
                     مشتری در داشبورد می‌بیند. نقشه‌ی OpenStreetMap فقط با مختصات و فقط با کلیک بار می‌شود. --}}
                @php
                    $__locSalon = app(\App\Support\CurrentSalon::class)->get();
                    $__directions = $__locSalon ? \App\Support\SalonMapLocation::directionsUrl($__locSalon) : null;
                    $__osm = $__locSalon ? \App\Support\SalonMapLocation::osmEmbedUrl($__locSalon) : null;
                @endphp
                @if ($__directions || $__osm)
                    <div class="flex flex-wrap gap-2 mt-4" data-salon-location>
                        @if ($__directions)
                            <a href="{{ $__directions }}" target="_blank" rel="noopener" class="btn-gold rounded-full px-4 py-1.5 text-xs font-bold" data-location-directions>مسیریابی</a>
                        @endif
                        @if ($__osm)
                            <button type="button" class="rounded-full px-4 py-1.5 text-xs border border-m-gold/30 text-m-tx/80 hover:bg-m-gold/10" data-location-map-load aria-expanded="false">نمایش نقشه</button>
                        @endif
                    </div>
                    @if ($__osm)
                        <div class="hidden mt-3 rounded-xl overflow-hidden border border-m-gold/15 h-48 relative" data-location-map data-src="{{ $__osm }}"></div>
                    @endif
                @endif
            </div>

            <div>
                <h3 class="font-bold mb-4 flex items-center text-m-tx">
                    <svg class="w-5 h-5 ml-1 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ساعات کاری
                </h3>
                <div class="text-m-tx/60 text-sm space-y-2 persian-number">
                    @forelse ($currentSalonHours as $line)
                        <p>{{ $line }}</p>
                    @empty
                        <p>برای هماهنگی با سالن تماس بگیرید.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ۲۰۲۶-۱۱-۰۶: بنر «باز کردن در اپ» فقط روی اندروید و قابل‌بستن است؛ این نشانی همیشه و روی هر دستگاه دیده می‌شود --}}
        @if ($footerAppSalon = app(\App\Support\CurrentSalon::class)->get())
            <div class="mt-10 rounded-2xl border border-m-gold/20 bg-m-bg/60 px-5 py-4 flex flex-col sm:flex-row items-center gap-3 sm:gap-4 text-center sm:text-right" data-salon-app-promo>
                <svg class="w-9 h-9 shrink-0 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="6" y="2" width="12" height="20" rx="3" /><path stroke-linecap="round" d="M11 18h2" />
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-m-tx">اپ {{ $currentSalonTitle }} روی گوشی شما</p>
                    <p class="text-m-tx/60 text-sm leading-6">رزرو سریع‌تر و یادآوری نوبت‌ها؛ اندروید با نصب اپ، آیفون با افزودن به صفحه‌ی اصلی.</p>
                </div>
                <a href="{{ \App\Support\CustomerAppLink::forSalon($footerAppSalon)['install_url'] }}"
                   class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold border border-m-gold/50 text-m-gold2 hover:bg-m-gold/10 transition-colors">
                    دریافت اپ
                </a>
            </div>
        @endif

        <div class="border-t border-m-gold/10 mt-8 pt-6 text-center text-m-tx/40 text-sm">
            <p>© {{ fa_jdate(now(), 'Y') }} {{ $currentSalonTitle }}. تمامی حقوق محفوظ است.</p>
            @include('partials.platform-legal-links')
        </div>
    </div>
</footer>
