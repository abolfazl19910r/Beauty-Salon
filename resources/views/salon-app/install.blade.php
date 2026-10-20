{{--
    صفحه‌ی «اپ» سالن (/app، بسته‌ی ۵ و ۶). سه بخش؛ بخش پیش‌فرض از User-Agent ($platform) و اسکریپت پایین دقیقش می‌کند.
    ورودی: $salon، $links (App\Support\CustomerAppLink)، $platform (android|ios|desktop).
--}}
@php($salonTitle = \App\Support\SalonTitle::for($salon->name))
<x-guest-layout>
    <div data-salon-app-page data-platform="{{ $platform }}">
        <div class="text-center mb-5">
            <h2 class="text-xl font-bold" style="color:rgb(var(--m-gold2))">{{ $salonTitle }} روی گوشی شما</h2>
            <p class="text-sm text-m-tx/60 mt-1">رزرو، پرداخت و پیگیری نوبت‌ها، بدون گشتن دنبال آدرس سایت</p>
        </div>

        <p class="hidden text-sm rounded-lg p-3 mb-4 bg-m-ok/10 text-m-ok text-center" data-installed-note>
            این صفحه همین الان از صفحه‌ی اصلی گوشی باز شده است؛ نصب انجام شده.
        </p>

        {{-- اندروید: اپ «ماهرو» (بدون مارکت؛ APK از دامنه‌ی ماهرو) --}}
        <section data-install-section="android" @if ($platform !== 'android') hidden @endif>
            <div class="flex items-center gap-3 mb-4">
                <img src="{{ asset('brand/mahru-customer-app-192.png') }}" alt="" width="52" height="52" class="rounded-xl shrink-0">
                <div>
                    <div class="font-bold text-m-tx">اپ {{ config('brand.name') }} برای اندروید</div>
                    <div class="text-xs text-m-tx/60">نوبت‌های {{ $salonTitle }} و اعلان یادآوری</div>
                </div>
            </div>

            @if ($links['download_url'])
                <a href="{{ $links['download_url'] }}" data-install-download
                   class="block w-full text-center py-3 rounded-lg font-semibold bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30">
                    دانلود اپ {{ config('brand.name') }}
                </a>
            @else
                <p class="text-center text-sm text-m-tx/70 rounded-lg p-3 bg-m-gold/10" data-install-soon>اپ اندروید به‌زودی منتشر می‌شود؛ تا آن موقع از همین سایت نوبت بگیرید.</p>
            @endif

            <a href="{{ $links['intent_url'] }}" data-install-open-app
               class="block w-full text-center py-2.5 mt-2 rounded-lg text-sm font-semibold border border-m-gold/50 text-m-gold2 hover:bg-m-gold/10">
                اپ را دارم؛ باز کردن {{ $salonTitle }} در اپ
            </a>

            <div class="mt-5 rounded-xl p-3 text-center border border-m-gold/30" data-install-salon-code>
                <p class="text-sm text-m-tx/70">بعد از نصب، در اپ این کد را وارد کنید یا QR سالن را اسکن کنید:</p>
                <p class="mt-1 text-2xl font-bold text-m-gold2" dir="ltr">{{ $salon->slug }}</p>
            </div>

            <ol class="space-y-2 text-sm text-m-tx/80 leading-7 mt-5">
                <li class="flex gap-3"><span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۱</span><span>«دانلود» را بزنید و فایل را باز کنید.</span></li>
                <li class="flex gap-3"><span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۲</span><span>اگر گوشی پرسید «نصب از منبع ناشناس»، به مرورگرتان اجازه بدهید و برگردید.</span></li>
                <li class="flex gap-3"><span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۳</span><span>«نصب» را بزنید، اپ را باز کنید و کد بالا را وارد کنید.</span></li>
            </ol>
            <a href="{{ $links['download_page_url'] }}" class="block text-center text-xs text-m-gold2 mt-3">صفحه‌ی دانلود اپ {{ config('brand.name') }} و راهنمای کامل</a>
        </section>

        {{-- آیفون: اپ نداریم (اپ‌استور برای توسعه‌دهنده‌ی ایرانی بسته است) ← «افزودن به صفحه‌ی اصلی» (PWA) --}}
        <section data-install-section="ios" @if ($platform !== 'ios') hidden @endif>
            <div class="flex items-center gap-3 mb-4">
                <img src="{{ asset('apple-touch-icon.png') }}" alt="" width="52" height="52" class="rounded-xl shrink-0" data-ios-icon>
                <div>
                    <div class="font-bold text-m-tx">{{ $salonTitle }} روی صفحه‌ی اصلی آیفون</div>
                    <div class="text-xs text-m-tx/60">مثل یک اپ باز می‌شود، بدون نوار مرورگر</div>
                </div>
            </div>

            <ol class="space-y-3 text-sm text-m-tx/80 leading-7" data-ios-steps>
                <li class="flex gap-3">
                    <span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۱</span>
                    <span>این صفحه را در <b>Safari</b> باز کنید. اگر از داخل اینستاگرام یا تلگرام آمده‌اید، از منوی «⋯» گزینه‌ی «باز کردن در مرورگر» را بزنید.</span>
                </li>
                <li class="flex gap-3">
                    <span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۲</span>
                    <span>
                        دکمه‌ی <b>اشتراک‌گذاری</b>
                        <svg class="inline w-5 h-5 align-text-bottom text-m-gold2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="اشتراک‌گذاری"><path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        را بزنید (پایین صفحه در Safari؛ در Chrome آیفون کنار نوار آدرس).
                    </span>
                </li>
                <li class="flex gap-3">
                    <span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۳</span>
                    <span>کمی پایین بروید و <b>«افزودن به صفحه‌ی اصلی»</b> (<span dir="ltr">Add to Home Screen</span>) را بزنید.</span>
                </li>
                <li class="flex gap-3">
                    <span class="w-7 h-7 rounded-full bg-m-gold/15 text-m-gold2 font-bold flex items-center justify-center shrink-0">۴</span>
                    <span><b>«افزودن»</b> (<span dir="ltr">Add</span>) را بزنید. آیکون {{ $salonTitle }} روی صفحه‌ی گوشی می‌آید؛ یک بار وارد حساب شوید.</span>
                </li>
            </ol>
            <p class="text-xs text-m-tx/50 mt-4 leading-6">یادآوری و خبر نوبت‌ها روی آیفون با پیامک می‌آید.</p>
        </section>

        {{-- کامپیوتر: QR همین صفحه تا روی گوشی باز شود --}}
        <section data-install-section="desktop" class="text-center" @if ($platform !== 'desktop') hidden @endif>
            <p class="text-sm text-m-tx/75 leading-7">این QR را با دوربین گوشی اسکن کنید تا همین صفحه روی گوشی باز شود:</p>
            <div class="w-48 h-48 mx-auto mt-3 rounded-lg p-2" style="background:#fff;" data-salon-qr="{{ $links['install_url'] }}" role="img" aria-label="QR"></div>
            <p class="text-sm text-m-tx/70 mt-4">کد سالن در اپ {{ config('brand.name') }}: <b dir="ltr" class="text-m-gold2">{{ $salon->slug }}</b></p>
            <div class="flex justify-center gap-4 text-xs mt-4">
                <button type="button" class="text-m-gold2" data-install-show="android">راهنمای اندروید</button>
                <button type="button" class="text-m-gold2" data-install-show="ios">راهنمای آیفون</button>
            </div>
        </section>

        <a href="{{ route('home', ['salon_slug' => $salon->slug]) }}" class="block text-center text-sm text-m-tx/60 hover:text-m-gold mt-6">بازگشت به سایت سالن</a>
    </div>

    <script>
        (function () {
            var page = document.querySelector('[data-salon-app-page]');
            var ua = navigator.userAgent;
            var platform = page.dataset.platform;
            // iPadOS خودش را مک معرفی می‌کند
            if (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) platform = 'ios';
            var show = function (name) {
                page.querySelectorAll('[data-install-section]').forEach(function (s) { s.hidden = s.dataset.installSection !== name; });
            };
            show(platform);
            page.querySelectorAll('[data-install-show]').forEach(function (b) {
                b.addEventListener('click', function () { show(b.dataset.installShow); });
            });
            if (window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)) {
                page.querySelector('[data-installed-note]').classList.remove('hidden');
            }
        })();
    </script>
    @vite('resources/js/salon-qr.js')
</x-guest-layout>
