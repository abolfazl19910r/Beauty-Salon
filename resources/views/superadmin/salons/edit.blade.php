@extends('layouts.superadmin')

@section('title', 'ویرایش سالن')

@section('content')
    <div class="sa-card p-6 max-w-2xl">
        <div class="mb-4 text-sm" style="color: var(--sa-text-dim);">
            آدرس: <span style="color: var(--sa-text);">/s/{{ $salon->slug }}</span>
            (غیرقابل‌تغییر)
        </div>

        {{-- ⭐ مالک سالن + ورود اضطراری (۲۰۲۶-۱۰-۱۵) --}}
        <div class="mb-5 p-4 rounded-lg text-sm" style="border:1px solid var(--sa-border);" data-owner-card>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <div class="font-bold">مالک سالن</div>
                    @if ($owner)
                        <div style="color: var(--sa-text-dim);">{{ $owner->name }} · <span dir="ltr">{{ $owner->phone }}</span></div>
                    @else
                        <div style="color: var(--sa-danger);">این سالن مالک ندارد.</div>
                    @endif
                </div>
                @if ($owner)
                    <form method="POST" action="{{ route('superadmin.salons.impersonate', $salon) }}">
                        @csrf
                        <button type="submit" class="sa-btn" title="با حساب مالک وارد پنل مدیر این سالن می‌شوید؛ در گزارش کارها ثبت می‌شود">ورود به پنل مدیر سالن</button>
                    </form>
                @endif
            </div>
            <details @if($errors->hasAny(['owner_name', 'owner_phone', 'owner_password']) || ! $owner) open @endif>
                <summary class="cursor-pointer" style="color: var(--sa-accent);">{{ $owner ? 'ویرایش مالک (نام، موبایل، رمز تازه)' : 'تعیین مالک' }}</summary>
                <form method="POST" action="{{ route('superadmin.salons.owner', $salon) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="sa-label" for="owner_name">نام مالک</label>
                        <input id="owner_name" name="owner_name" class="sa-input" value="{{ old('owner_name', $owner?->name) }}" required>
                        @error('owner_name')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="sa-label" for="owner_phone">موبایل مالک (شماره‌ی ورود)</label>
                        <input id="owner_phone" name="owner_phone" dir="ltr" class="sa-input" value="{{ old('owner_phone', $owner?->phone) }}" required>
                        @error('owner_phone')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="sa-label" for="owner_password">رمز تازه {{ $owner ? '(خالی = بدون تغییر)' : '' }}</label>
                        <input id="owner_password" type="password" name="owner_password" class="sa-input" autocomplete="new-password" {{ $owner ? '' : 'required' }}>
                        @error('owner_password')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="sa-label" for="owner_password_confirmation">تکرار رمز</label>
                        <input id="owner_password_confirmation" type="password" name="owner_password_confirmation" class="sa-input" autocomplete="new-password">
                    </div>
                    <div class="md:col-span-2"><button type="submit" class="sa-btn">ذخیره‌ی مالک</button></div>
                </form>
            </details>
        </div>

        {{-- پیامک‌های این ماه: سقف سالن در برابر کدهای تأیید (خرج پلتفرم) — برای دیدن مصرف غیرعادی --}}
        <div class="mb-5 p-4 rounded-lg text-sm persian-number" style="border:1px solid var(--sa-border); color: var(--sa-text);">
            <div class="font-bold mb-2">پیامک‌های این ماه</div>
            <div>از سقف سالن: {{ to_persian_num((string) $smsUsage['used']) }} از {{ to_persian_num((string) $smsUsage['quota']) }} قطعه</div>
            <div>کدهای تأیید (خرج پلتفرم، خارج از سقف): {{ to_persian_num((string) $smsUsage['otp']) }}</div>
            <div>اعتبار خریده‌شده‌ی باقی‌مانده: {{ to_persian_num(number_format($smsUsage['credit'])) }} قطعه</div>
        </div>

        @include('superadmin.salons.partials.plan-features')

        {{-- اعطای دستی اعتبار پیامک (هدیه/جبران) — ۲۰۲۶-۰۹-۳۰ --}}
        <form method="POST" action="{{ route('superadmin.salons.sms-credit', $salon) }}" class="mb-5 p-4 rounded-lg text-sm flex flex-wrap items-end gap-3" style="border:1px solid var(--sa-border);">
            @csrf
            <div>
                <label class="sa-label">افزودن اعتبار پیامک (قطعه)</label>
                <input type="number" name="parts" min="1" class="sa-input" required>
            </div>
            <div class="flex-1 min-w-[12rem]">
                <label class="sa-label">توضیح (اختیاری)</label>
                <input type="text" name="note" maxlength="255" class="sa-input" placeholder="مثلاً جبران قطعی پیامک">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-white" style="background: var(--sa-accent, #4f46e5);">افزودن</button>
        </form>

        <form method="POST" action="{{ route('superadmin.salons.update', $salon) }}" class="space-y-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div>
                <label class="sa-label">نام سالن</label>
                <input type="text" name="name" value="{{ old('name', $salon->name) }}" class="sa-input" required>
            </div>

            <div>
                <label class="sa-label">
                    شعار کوتاه سالن
                    <span style="color: var(--sa-text-dim);">(اختیاری — مثلاً روی صفحه‌ی خدمات نشون داده می‌شه)</span>
                </label>
                <input type="text" name="tagline" value="{{ old('tagline', $salon->tagline) }}" class="sa-input" maxlength="255">
            </div>

            <div>
                <label class="sa-label">
                    معرفی سالن
                    <span style="color: var(--sa-text-dim);">(اختیاری — پاراگراف «درباره‌ی ما» در صفحه‌ی اصلی)</span>
                </label>
                <textarea name="bio" rows="4" class="sa-input" maxlength="2000">{{ old('bio', $salon->bio) }}</textarea>
            </div>

            @include('superadmin.salons.partials.contact-fields', ['salon' => $salon])

            <div>
                <label class="sa-label">
                    سقف تعداد متخصص
                    <span style="color: var(--sa-text-dim);">(فعلاً {{ $salon->specialists()->count() }} متخصص ثبت‌شده)</span>
                </label>
                <input type="number" name="max_specialists_count" value="{{ old('max_specialists_count', $salon->max_specialists_count) }}" min="0" class="sa-input" required>
            </div>

            <div>
                <label class="sa-label">
                    سهمیه‌ی پیامک ماهانه (قطعه)
                    @php $defaultQuota = (int) ($salon->effectivePlan()?->sms_quota_per_month ?? config('billing.sms_quota_per_month')); @endphp
                    <span style="color: var(--sa-text-dim);">— خالی = سهمیه‌ی پلن سالن، {{ to_persian_num(number_format($defaultQuota)) }} قطعه</span>
                </label>
                <input type="number" name="sms_quota_per_month" value="{{ old('sms_quota_per_month', $salon->sms_quota_per_month) }}" min="0" class="sa-input"
                       placeholder="{{ $defaultQuota }}">
                <p class="text-xs mt-1" style="color: var(--sa-text-dim);">فقط برای سالنی که سهمیه‌ی متفاوت می‌خواهد. سالن آزمایشی تا اولین خرید سهمیه‌ی آزمایشی دارد.</p>
            </div>

            <div>
                <label class="sa-label">دسترسی‌های ماژولار</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @php $currentModules = old('module_permissions', $salon->module_permissions ?? []); @endphp
                    @foreach (['blog' => 'وبلاگ', 'gallery' => 'گالری', 'announcements' => 'اطلاعیه‌ها', 'loyalty' => 'وفاداری', 'discount_codes' => 'کد تخفیف', 'reports' => 'گزارشات', 'wallet_settings' => 'تنظیمات کیف‌پول'] as $key => $label)
                        <label class="flex items-center gap-2 text-sm" style="color: var(--sa-text-dim);">
                            <input type="checkbox" name="module_permissions[]" value="{{ $key }}"
                                   {{ in_array($key, $currentModules) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="sa-label">
                    مرچنت آیدی اختصاصی زرین‌پال
                    <span style="color: var(--sa-text-dim);">(اختیاری — خالی بمونه یعنی از حساب مشترک پلتفرم استفاده می‌شه)</span>
                </label>
                <input type="text" name="zarinpal_merchant_id" value="{{ old('zarinpal_merchant_id', $salon->zarinpal_merchant_id) }}" class="sa-input" dir="ltr">
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('superadmin.salons.invoices', $salon) }}" style="color: var(--sa-accent);">تاریخچه‌ی فاکتورها →</a>
            </div>

            <button type="submit" class="sa-btn">ذخیره تغییرات</button>
        </form>
    </div>
@endsection
