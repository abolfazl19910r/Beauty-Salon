@extends('layouts.superadmin')

@section('title', 'ساخت سالن جدید')

@section('content')
    <div class="sa-card p-6 max-w-3xl">
        <form method="POST" action="{{ route('superadmin.salons.store') }}" class="space-y-6">
            @csrf

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">مشخصات سالن</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">نام سالن (نمایشی)</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">
                            آدرس یکتا (slug) — بعد از ساخت غیرقابل‌تغییر
                        </label>
                        <div class="flex items-center gap-2">
                            <span style="color: var(--sa-text-dim);">/s/</span>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" class="sa-input" required
                                   pattern="[a-zA-Z0-9_-]+" placeholder="مثلاً: almas" autocomplete="off">
                        </div>
                        <div id="slug-status" class="check-status"></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="sa-label">شعار کوتاه سالن (اختیاری)</label>
                        <input type="text" name="tagline" value="{{ old('tagline') }}" class="sa-input" maxlength="255">
                    </div>
                    <div>
                        <label class="sa-label">معرفی سالن (اختیاری)</label>
                        <textarea name="bio" rows="2" class="sa-input" maxlength="2000">{{ old('bio') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">اشتراک و سقف</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">مدت اشتراک</label>
                        <select name="subscription_type" class="sa-input" required>
                            <option value="1m" {{ old('subscription_type') == '1m' ? 'selected' : '' }}>۱ ماه</option>
                            <option value="3m" {{ old('subscription_type') == '3m' ? 'selected' : '' }}>۳ ماه</option>
                            <option value="6m" {{ old('subscription_type') == '6m' ? 'selected' : '' }}>۶ ماه</option>
                            <option value="12m" {{ old('subscription_type') == '12m' ? 'selected' : '' }}>۱۲ ماه</option>
                        </select>
                    </div>
                    <div>
                        <label class="sa-label">سقف تعداد متخصص</label>
                        <input type="number" name="max_specialists_count" value="{{ old('max_specialists_count', 5) }}" min="0" class="sa-input" required>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">مشخصات ادمین سالن</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="sa-label">نام ادمین</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">موبایل ادمین</label>
                        <input type="text" id="admin_phone" name="admin_phone" value="{{ old('admin_phone') }}" class="sa-input" maxlength="11" required autocomplete="off">
                        <div id="admin_phone-status" class="check-status"></div>
                    </div>
                    <div>
                        <label class="sa-label">رمز عبور</label>
                        <input type="password" name="admin_password" class="sa-input" required>
                    </div>
                    <div>
                        <label class="sa-label">تکرار رمز عبور</label>
                        <input type="password" name="admin_password_confirmation" class="sa-input" required>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-3" style="color: var(--sa-accent);">دسترسی‌های ماژولار</h3>
                {{-- ⭐ فقط ذخیره می‌شن — اعمال واقعی این محدودیت‌ها روی منوی پنل ادمین، طبق مستندات
                     SaaS ("بستن حفره دوم")، هنوز پیاده‌سازی نشده؛ فعلاً فقط اطلاعاتی هستن. --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach (['blog' => 'وبلاگ', 'gallery' => 'گالری', 'announcements' => 'اطلاعیه‌ها', 'loyalty' => 'وفاداری', 'discount_codes' => 'کد تخفیف', 'reports' => 'گزارشات', 'wallet_settings' => 'تنظیمات کیف‌پول'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm" style="color: var(--sa-text-dim);">
                            <input type="checkbox" name="module_permissions[]" value="{{ $key }}"
                                   {{ in_array($key, old('module_permissions', [])) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="sa-btn">ایجاد سالن</button>
        </form>
    </div>

    @push('styles')
        <style>
            .check-status { font-size: .78rem; margin-top: .3rem; min-height: 1em; }
            .check-status.ok { color: #4ADE80; }
            .check-status.bad { color: #F87171; }
            .check-status.pending { color: var(--sa-text-dim); }
        </style>
    @endpush

    {{-- ⭐ مورد ۴ (نشست ۲۰۲۶-۰۹-۲۰): همون چک یکتایی زنده‌ی فرم self-service
         (salon-signup.create)، از همون دو endpoint عمومی (SalonSignupController::checkSlug/
         checkPhone) — منطق یکتایی یکیه (جدول salons / user_type='staff')، پس یک endpoint
         مشترک به‌جای دو نسخه‌ی جدا. --}}
    @push('scripts')
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
                                if (input.value.trim() !== value) return;
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

                wireCheck('admin_phone', '{{ route('salon-signup.check-phone') }}', 'phone', {
                    pending: 'در حال بررسی…',
                    ok: '✓ این شماره آزاد است',
                    taken: '✗ ادمینی با این شماره قبلاً ثبت شده است',
                });
            })();
        </script>
    @endpush
@endsection
