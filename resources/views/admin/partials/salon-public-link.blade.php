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
    @endphp
    <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);" id="salon-public-link-card">
        <div class="px-4 py-3 text-sm font-bold flex items-center justify-between gap-2" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
            <span>آدرس اختصاصی رزرو آنلاین سالن شما</span>
            @if ($salon->isOnTrial())
                <span class="text-xs px-2 py-1 rounded-full" style="background:#FEF3C7; color:#92400E;">
                    دوره‌ی آزمایشی — <span class="persian-number">{{ $salon->trialDaysLeft() }}</span> روز باقی‌مانده
                </span>
            @elseif ($isLive)
                <span class="text-xs px-2 py-1 rounded-full" style="background:#DCFCE7; color:#166534;">فعال</span>
            @else
                <span class="text-xs px-2 py-1 rounded-full" style="background:#FEE2E2; color:#991B1B;">غیرفعال تا تمدید اشتراک</span>
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
                        class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background:var(--admin-accent);">کپی لینک</button>
                <a href="{{ $primaryUrl }}" target="_blank" rel="noopener"
                   class="px-4 py-2 rounded-lg text-sm font-bold text-center" style="border:1px solid var(--admin-border); color:var(--admin-text);">مشاهده</a>
            </div>
            @if ($legacyUrl !== $primaryUrl)
                <p class="text-xs" style="color:var(--admin-text-light);">
                    آدرس جایگزین (همیشه فعال): <span dir="ltr" class="font-mono">{{ $legacyUrl }}</span>
                </p>
            @endif
            @unless ($isLive)
                <p class="text-xs" style="color:#b91c1c;">
                    تا زمانی که اشتراک سالن فعال نباشد، این آدرس برای مشتری‌ها در دسترس نیست.
                </p>
            @endunless
        </div>
    </div>

    @once
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
