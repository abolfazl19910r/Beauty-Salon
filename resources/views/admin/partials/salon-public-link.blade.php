{{--
    ⭐ کارت «آدرس اختصاصی سالن شما» (۲۰۲۶-۰۹-۲۳) — تا قبل از این، آدرس عمومی سالن هیچ‌جای پنل به
    مالکش نشون داده نمی‌شد؛ یعنی کسی که از صفحه‌ی ثبت‌نام عمومی اشتراک می‌گرفت، لینکی برای دادن
    به مشتری‌هاش دریافت نمی‌کرد. روی داشبورد و صفحه‌ی billing include می‌شه.
    ورودی: $salon (App\Models\Salon|null) — سوپرادمینی که مستقیم /admin رو باز کنه CurrentSalon
    نداره، پس کارت اصلاً رندر نمی‌شه.
--}}
@if ($salon)
    @php
        $primaryUrl = $salon->publicUrl();
        $legacyUrl = $salon->legacyPublicUrl();
        $isLive = $salon->hasActiveSubscription();
        // آدرسی که فقط روی همین کامپیوتر باز می‌شود (پنل با 127.0.0.1/localhost یا دامنه‌ی .test باز شده): QR آن روی گوشی کار نمی‌کند
        $linkHost = strtolower((string) parse_url($primaryUrl, PHP_URL_HOST));
        $localOnly = in_array($linkHost, ['127.0.0.1', 'localhost', '::1', '[::1]', '0.0.0.0'], true)
            || str_starts_with($linkHost, '127.') || str_ends_with($linkHost, '.localhost') || str_ends_with($linkHost, '.test');
    @endphp
    <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);" id="salon-public-link-card">
        <div class="px-4 py-3 text-sm font-bold flex items-center justify-between gap-2" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
            <span>آدرس اختصاصی رزرو آنلاین سالن شما</span>
            @if ($salon->isOnTrial())
                <span class="text-xs px-2 py-1 rounded-full" style="background:rgb(var(--m-warn) / .14); color:rgb(var(--m-warn));">
                    دوره‌ی آزمایشی — <span class="persian-number">{{ $salon->trialDaysLeft() }}</span> روز باقی‌مانده
                </span>
            @elseif ($isLive)
                <span class="text-xs px-2 py-1 rounded-full" style="background:rgb(var(--m-ok) / .14); color:rgb(var(--m-ok));">فعال</span>
            @else
                <span class="text-xs px-2 py-1 rounded-full" style="background:rgb(var(--m-bad) / .14); color:rgb(var(--m-bad));">غیرفعال تا تمدید اشتراک</span>
            @endif
        </div>
        <div class="p-5 text-sm space-y-3">
            <p style="color:var(--admin-text-dim);">
                این لینک را در اینستاگرام، واتساپ یا روی کارت ویزیت سالن قرار دهید تا مشتری‌ها بتوانند
                خدمات و متخصص‌ها را ببینند و آنلاین نوبت رزرو کنند.
            </p>
            <div class="flex flex-col sm:flex-row gap-2 items-stretch">
                <input type="text" readonly dir="ltr" value="{{ $primaryUrl }}" data-salon-link-input
                       class="flex-1 rounded-lg px-3 py-2 font-mono text-sm" style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                <button type="button" data-copy-salon-link="{{ $primaryUrl }}"
                        class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">کپی لینک</button>
                <a href="{{ $primaryUrl }}" target="_blank" rel="noopener"
                   class="px-4 py-2 rounded-lg text-sm font-bold text-center" style="border:1px solid var(--admin-border); color:var(--admin-text);">مشاهده</a>
            </div>
            @if ($localOnly)
                <p class="text-xs leading-6 rounded-lg p-3" data-salon-link-local-only style="background:rgb(var(--m-warn) / .12); color:rgb(var(--m-warn));">
                    این لینک و QR فقط روی همین کامپیوتر باز می‌شوند (<span dir="ltr">{{ $linkHost }}</span>). برای آزمایش با گوشی، پنل را با آدرس
                    شبکه‌ی کامپیوتر باز کنید (مثلاً <span dir="ltr">http://192.168.1.2:8000/admin</span>) تا QR با همان آدرس ساخته شود. روی سرور واقعی
                    این پیام دیده نمی‌شود.
                </p>
            @endif
            {{-- QR و کد سالن برای اپ «ماهرو» (بسته‌ی ۳، ۲۰۲۶-۱۰-۱۱): مشتری با لینک، QR یا همین کد سالن را در اپ پیدا می‌کند --}}
            <div class="flex flex-col sm:flex-row gap-4 items-center pt-2" data-salon-qr-card>
                <div class="w-40 h-40 rounded-lg p-1 shrink-0" style="background:#fff; border:1px solid var(--admin-border);"
                     data-salon-qr="{{ $primaryUrl }}" data-salon-qr-name="{{ $salon->slug }}" data-salon-qr-title="{{ $salon->name }}"
                     role="img" aria-label="QR آدرس سالن"></div>
                <div class="space-y-2 text-center sm:text-right">
                    <p style="color:var(--admin-text-dim);">
                        این QR را روی میز پذیرش یا کارت ویزیت بگذارید. مشتری با دوربین گوشی صفحه‌ی سالن را باز می‌کند،
                        یا در اپ «ماهرو» آن را اسکن می‌کند.
                    </p>
                    <p style="color:var(--admin-text);">
                        کد سالن در اپ ماهرو: <span dir="ltr" class="font-mono font-bold">{{ $salon->slug }}</span>
                    </p>
                    <div class="flex gap-2 justify-center sm:justify-start">
                        <button type="button" data-salon-qr-download class="px-3 py-1.5 rounded-lg text-xs font-bold text-m-onsolid" style="background:var(--admin-accent);">دانلود QR</button>
                        <button type="button" data-salon-qr-print class="px-3 py-1.5 rounded-lg text-xs font-bold" style="border:1px solid var(--admin-border); color:var(--admin-text);">چاپ</button>
                    </div>
                </div>
            </div>
            @if ($legacyUrl !== $primaryUrl)
                <p class="text-xs" style="color:var(--admin-text-light);">
                    آدرس جایگزین (همیشه فعال): <span dir="ltr" class="font-mono">{{ $legacyUrl }}</span>
                </p>
            @endif
            @unless ($isLive)
                <p class="text-xs" style="color:rgb(var(--m-bad));">
                    تا زمانی که اشتراک سالن فعال نباشد، این آدرس برای مشتری‌ها در دسترس نیست.
                </p>
            @endunless
        </div>
    </div>

    @once
        @vite('resources/js/salon-qr.js')
        @push('scripts')
            <script>
                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('[data-copy-salon-link]');
                    if (!btn) return;
                    var text = btn.getAttribute('data-copy-salon-link');
                    var done = function () {
                        var old = btn.textContent;
                        btn.textContent = 'کپی شد ✓';
                        setTimeout(function () { btn.textContent = old; }, 1800);
                    };
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(done);
                    } else {
                        var input = btn.parentElement.querySelector('[data-salon-link-input]');
                        input.select();
                        document.execCommand('copy');
                        done();
                    }
                });
            </script>
        @endpush
    @endonce
@endif
