<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ثبت‌نام سالن جدید — {{ config('app.name', 'راستا') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">

    {{--
        ⭐ عمداً یک صفحه‌ی کاملاً self-contained (بدون @vite، بدون x-guest-layout) — دقیقاً
        هم‌الگو با resources/views/central/placeholder.blade.php. x-guest-layout به route('home')
        و $currentSalonName وابسته‌ست که هر دو فقط داخل یک درخواست /s/{slug} معنی دارن؛ این صفحه
        global است (بیرون از هر سالن)، پس اون layout اینجا کرش می‌کنه.
    --}}
    <style>
        :root {
            --rasta-gold: #C9A24B;
            --rasta-gold-light: #E6CD8A;
            --rasta-cream: #F8F3E9;
            --rasta-dark: #1A1410;
            --rasta-brown: #2E2117;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--rasta-dark);
            color: var(--rasta-cream);
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            padding: 2.5rem 1rem;
        }
        .wrap { max-width: 40rem; margin: 0 auto; }
        h1 { color: var(--rasta-gold-light); font-size: 1.6rem; text-align: center; margin-bottom: .5rem; }
        .sub { text-align: center; opacity: .75; margin-bottom: 2rem; line-height: 1.8; }
        .card {
            background-color: var(--rasta-brown);
            border: 1px solid rgba(201, 162, 75, 0.15);
            border-radius: 1rem;
            padding: 1.75rem;
        }
        fieldset { border: none; padding: 0; margin: 0 0 1.5rem; }
        legend { color: var(--rasta-gold); font-weight: bold; margin-bottom: .75rem; padding: 0; }
        label { display: block; font-size: .875rem; margin-bottom: .35rem; opacity: .9; }
        .row { margin-bottom: 1rem; }
        input[type=text], input[type=password], input[type=tel], input[type=number], input[type=time], textarea {
            width: 100%;
            background-color: rgba(248, 243, 233, 0.04);
            border: 1px solid rgba(201, 162, 75, 0.25);
            color: var(--rasta-cream);
            border-radius: .5rem;
            padding: .6rem .75rem;
            font-size: .95rem;
        }
        input:focus { outline: none; border-color: var(--rasta-gold); box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.2); }
        textarea { resize: vertical; min-height: 4.5rem; font-family: inherit; line-height: 1.8; }
        .hint { font-size: .78rem; opacity: .6; margin-top: .3rem; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }
        @media (max-width: 520px) { .two { grid-template-columns: 1fr; } }
        .hours { display: grid; gap: .45rem; }
        .hours-row { display: grid; grid-template-columns: 5.5rem auto 1fr 1fr; gap: .5rem; align-items: center; }
        .hours-row .day { font-size: .9rem; }
        .hours-row .closed { display: flex; align-items: center; gap: .3rem; font-size: .8rem; margin: 0; opacity: .85; white-space: nowrap; }
        .hours-row input[type=time] { padding: .4rem .5rem; direction: ltr; }
        .hours-row.is-closed input[type=time] { opacity: .35; }
        @media (max-width: 520px) { .hours-row { grid-template-columns: 4.5rem auto 1fr 1fr; gap: .35rem; } }
        .err { color: #ff8a8a; font-size: .8rem; margin-top: .3rem; }
        .check-status { font-size: .78rem; margin-top: .35rem; min-height: 1em; }
        .check-status.ok { color: #7ee0a6; }
        .check-status.bad { color: #ff8a8a; }
        .check-status.pending { color: rgba(248, 243, 233, 0.45); }
        .flash-err {
            background: rgba(220,38,38,.15); border-right: 4px solid #dc2626;
            padding: .75rem 1rem; border-radius: .4rem; margin-bottom: 1.25rem; font-size: .875rem;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold));
            color: var(--rasta-dark);
            border: none;
            border-radius: .6rem;
            padding: .8rem;
            font-size: 1rem;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>ساخت سالن خودتان روی راستا</h1>
        <p class="sub">
            در چند دقیقه، پنل مدیریت اختصاصی سالن خودتان را راه‌اندازی کنید.
            @if ($trialDays > 0)
                <br>بعد از تایید شماره موبایل، {{ to_persian_num((string) $trialDays) }} روز رایگان از همه‌ی امکانات استفاده می‌کنید؛
                خرید اشتراک بعداً از داخل پنل سالن انجام می‌شود.
            @else
                <br>بعد از تایید شماره موبایل، اشتراک را از داخل پنل سالن خریداری می‌کنید.
            @endif
        </p>

        <div class="card">
            @if ($errors->any())
                <div class="flash-err">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('salon-signup.store') }}">
                @csrf

                <fieldset>
                    <legend>مشخصات سالن</legend>
                    <div class="row">
                        <label for="name">نام سالن</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                    </div>
                    <div class="row">
                        <label for="slug">آدرس اختصاصی سالن (بعداً غیرقابل‌تغییر)</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', request()->query('slug')) }}" required
                               maxlength="100" pattern="[a-zA-Z0-9_-]+" placeholder="مثلاً: almas-beauty" dir="ltr"
                               autocomplete="off">
                        <div id="slug-status" class="check-status"></div>
                    </div>
                </fieldset>

                {{-- ⭐ ۲۰۲۶-۰۹-۲۳: اطلاعات تماس و فعالیت همین سالن — بعد از ساخت، همون‌جاهایی که قبلاً متن
                     ثابت مشترک بین همه‌ی سالن‌ها بود (فوتر سایت مشتری، آمار صفحه‌ی اصلی، صفحه‌ی تشکر
                     بعد از ثبت نظر) با همین مقادیر پر می‌شن. --}}
                <fieldset>
                    <legend>اطلاعات تماس و فعالیت سالن</legend>
                    <div class="row">
                        <label for="salon_address">آدرس سالن</label>
                        <textarea id="salon_address" name="salon_address" required minlength="10" maxlength="500"
                                  placeholder="مثلاً: تهران، خیابان ولیعصر، بالاتر از میدان ونک، پلاک ۱۲، طبقه‌ی دوم">{{ old('salon_address') }}</textarea>
                    </div>
                    <div class="two">
                        <div class="row">
                            <label for="salon_phone">شماره تماس سالن</label>
                            <input type="tel" id="salon_phone" name="salon_phone" value="{{ old('salon_phone') }}" required
                                   maxlength="15" dir="ltr" placeholder="02112345678">
                            <div class="hint">ثابت یا موبایل؛ روی سایت سالن به مشتری‌ها نمایش داده می‌شود.</div>
                        </div>
                        <div class="row">
                            <label for="experience_years">سابقه‌ی کاری سالن (سال)</label>
                            <input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years') }}"
                                   required min="0" max="80" step="1" dir="ltr" placeholder="مثلاً ۵">
                            <div class="hint">هر سال به‌طور خودکار یکی اضافه می‌شود.</div>
                        </div>
                    </div>

                    @php
                        $hoursInput = old('working_hours');
                        $hoursDefaults = \App\Support\SalonWorkingHours::defaults();
                    @endphp
                    <div class="row" style="margin-bottom:0;">
                        <label>ساعات کاری سالن</label>
                        <div class="hours">
                            @foreach (\App\Support\SalonWorkingHours::WEEK_ORDER as $day)
                                @php
                                    $default = $hoursDefaults[$day];
                                    $row = is_array($hoursInput) ? ($hoursInput[$day] ?? []) : [
                                        'closed' => $default === null,
                                        'open' => $default['open'] ?? '09:00',
                                        'close' => $default['close'] ?? '21:00',
                                    ];
                                    $isClosed = ! empty($row['closed']);
                                @endphp
                                <div class="hours-row {{ $isClosed ? 'is-closed' : '' }}" data-hours-row>
                                    <span class="day">{{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}</span>
                                    <label class="closed">
                                        <input type="checkbox" name="working_hours[{{ $day }}][closed]" value="1" data-closed-toggle @checked($isClosed)>
                                        تعطیل
                                    </label>
                                    <input type="time" name="working_hours[{{ $day }}][open]" value="{{ $row['open'] ?? '09:00' }}"
                                           aria-label="ساعت شروع {{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}" @disabled($isClosed)>
                                    <input type="time" name="working_hours[{{ $day }}][close]" value="{{ $row['close'] ?? '21:00' }}"
                                           aria-label="ساعت پایان {{ \App\Support\SalonWorkingHours::DAY_NAMES[$day] }}" @disabled($isClosed)>
                                </div>
                            @endforeach
                        </div>
                        <div class="hint">فقط برای نمایش به مشتری‌هاست؛ ساعت‌های قابل رزرو را برنامه‌ی کاری هر متخصص تعیین می‌کند.</div>
                    </div>
                </fieldset>

                {{-- ⭐ تصمیم ابوالفضل (۲۰۲۶-۰۹-۲۳): انتخاب پلن از فرم ساخت سالن حذف شد — برای همه، نه فقط
                     وقتی دوره‌ی آزمایشی روشنه. پلن فقط یک‌جا انتخاب می‌شه: صفحه‌ی خرید داخل پنل
                     (admin.billing.index). ?plan= از کارت‌های صفحه‌ی فروش فقط به‌عنوان پیش‌انتخاب همون
                     صفحه‌ی خرید ذخیره می‌شه (salons.subscription_type). --}}
                <input type="hidden" name="subscription_type" value="{{ old('subscription_type', $selectedPlan) }}">


                <fieldset>
                    <legend>مشخصات مدیر سالن (شما)</legend>
                    <div class="row">
                        <label for="owner_name">نام و نام خانوادگی</label>
                        <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required maxlength="255">
                    </div>
                    <div class="row">
                        <label for="owner_phone">شماره موبایل</label>
                        <input type="tel" id="owner_phone" name="owner_phone" value="{{ old('owner_phone') }}"
                               required maxlength="11" dir="ltr" placeholder="09xxxxxxxxx" autocomplete="off">
                        <div id="owner_phone-status" class="check-status"></div>
                    </div>
                    <div class="row">
                        <label for="owner_password">رمز عبور</label>
                        <input type="password" id="owner_password" name="owner_password" required minlength="8">
                    </div>
                    <div class="row">
                        <label for="owner_password_confirmation">تکرار رمز عبور</label>
                        <input type="password" id="owner_password_confirmation" name="owner_password_confirmation" required minlength="8">
                    </div>
                </fieldset>

                <button type="submit">ادامه و ارسال کد تایید</button>
            </form>
        </div>
    </div>

    {{--
        ⭐ مورد ۴ (نشست ۲۰۲۶-۰۹-۲۰): چک یکتایی زنده — فقط یک راهنمایی سریع به کاربره، تصمیم نهایی
        همیشه همون validation سرور در store() هست (این fetch هیچ‌جا جای اون رو نمی‌گیره). Vanilla
        JS بدون هیچ کتابخونه‌ای — این صفحه هیچ @vite/jQuery‌ای نداره (به docblock بالای فایل نگاه
        کن)، پس نباید هیچ‌کدوم رو فرض کرد.
    --}}
    <script>
        (function () {
            function debounce(fn, wait) {
                var t;
                return function () {
                    var args = arguments;
                    clearTimeout(t);
                    t = setTimeout(function () { fn.apply(null, args); }, wait);
                };
            }

            function wireCheck(inputId, checkUrl, paramName, messages) {
                var input = document.getElementById(inputId);
                var status = document.getElementById(inputId + '-status');
                if (!input || !status) return;

                var run = debounce(function () {
                    var value = input.value.trim();
                    if (!value) { status.textContent = ''; status.className = 'check-status'; return; }

                    status.textContent = messages.pending;
                    status.className = 'check-status pending';

                    fetch(checkUrl + '?' + paramName + '=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' },
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (input.value.trim() !== value) return; // کاربر تا اون موقع ادامه داده
                            if (data.available) {
                                status.textContent = messages.ok;
                                status.className = 'check-status ok';
                            } else if (data.reason === 'invalid') {
                                status.textContent = '';
                                status.className = 'check-status';
                            } else {
                                status.textContent = messages.taken;
                                status.className = 'check-status bad';
                            }
                        })
                        .catch(function () {
                            status.textContent = '';
                            status.className = 'check-status';
                        });
                }, 500);

                input.addEventListener('input', run);
            }

            wireCheck('slug', '{{ route('salon-signup.check-slug') }}', 'slug', {
                pending: 'در حال بررسی…',
                ok: '✓ این آدرس آزاد است',
                taken: '✗ این آدرس قبلاً استفاده شده است',
            });

            wireCheck('owner_phone', '{{ route('salon-signup.check-phone') }}', 'phone', {
                pending: 'در حال بررسی…',
                ok: '✓ این شماره آزاد است',
                taken: '✗ ادمینی با این شماره قبلاً ثبت‌نام کرده است',
            });
        })();
    </script>
    <script>
        document.querySelectorAll('[data-closed-toggle]').forEach(function (box) {
            box.addEventListener('change', function () {
                var row = box.closest('[data-hours-row]');
                row.classList.toggle('is-closed', box.checked);
                row.querySelectorAll('input[type=time]').forEach(function (input) { input.disabled = box.checked; });
            });
        });
    </script>
</body>
</html>
