@extends('layouts.admin')
@section('title', 'تنظیمات مالی')

@push('styles')
    <style>
        .form-label { display:block; font-size:0.875rem; font-weight:500; margin-bottom:6px; color:var(--admin-text-dim); }
        .form-input { width:100%; border:1px solid var(--admin-border); border-radius:8px; padding:9px 14px; font-size:0.875rem; background:var(--admin-bg); color:var(--admin-text); outline:none; transition:border-color 0.15s; font-family:inherit; }
        .form-input:focus { border-color:var(--admin-accent); }
        .form-hint { font-size:0.75rem; margin-top:4px; color:var(--admin-text-light); }
        .toggle-switch { position:relative; width:44px; height:24px; display:inline-block; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; cursor:pointer; inset:0; background:var(--admin-border); border-radius:9999px; transition:background 0.2s; }
        .toggle-slider:before { content:''; position:absolute; width:18px; height:18px; border-radius:50%; background:#fff; top:3px; right:3px; transition:transform 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2); }
        .toggle-switch input:checked + .toggle-slider { background:var(--admin-accent); }
        .toggle-switch input:checked + .toggle-slider:before { transform:translateX(-20px); }
    </style>
@endpush

@section('content')
    <div class="fade-in max-w-3xl">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                تنظیمات مالی و کیف پول
            </h1>
        </div>

        <form action="{{ route('admin.wallet.settings.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="space-y-5">

                {{-- Withdrawal rules --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        قوانین برداشت وجه
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label">درصد کارمزد برداشت (شبا)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="withdrawal_fee_percentage"
                                       value="{{ $settings->withdrawal_fee_percentage }}"
                                       class="form-input persian-number" style="padding-left:2.5rem;">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--admin-text-dim);">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">تأخیر تسویه (روز)</label>
                            <input type="number" name="settlement_delay_days"
                                   value="{{ $settings->settlement_delay_days }}"
                                   class="form-input persian-number">
                            <p class="form-hint">روزهایی که درآمد پس از خدمت قابل برداشت می‌شود.</p>
                        </div>
                        <div>
                            <label class="form-label">حداقل مبلغ برداشت (تومان)</label>
                            <input type="number" step="1000" name="minimum_withdrawal_amount"
                                   value="{{ $settings->minimum_withdrawal_amount }}"
                                   class="form-input persian-number">
                        </div>
                        <div>
                            <label class="form-label">حداکثر مبلغ برداشت (تومان)</label>
                            <input type="number" step="1000" name="maximum_withdrawal_amount"
                                   value="{{ $settings->maximum_withdrawal_amount }}"
                                   class="form-input persian-number">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">درصد کمیسیون ادمین</label>
                            <div class="relative max-w-xs">
                                <input type="number" step="0.01" name="admin_commission_percentage"
                                       value="{{ $settings->admin_commission_percentage }}"
                                       class="form-input persian-number" style="padding-left:2.5rem;">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--admin-text-dim);">%</span>
                            </div>
                            <p class="form-hint">درصدی از پیش‌پرداخت که به عنوان کمیسیون سالن کسر می‌شود.</p>
                        </div>
                    </div>
                </div>

                {{-- Cancellation rules --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        قوانین لغو نوبت
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="form-label">بازه زمانی جریمه لغو مشتری (ساعت)</label>
                            <input type="number" name="cancellation_before_hours"
                                   value="{{ $settings->cancellation_before_hours }}"
                                   class="form-input persian-number max-w-xs">
                            <p class="form-hint">اگر مشتری کمتر از این مقدار مانده به نوبت لغو کند، جریمه اعمال می‌شود. این آستانه فقط برای مشتری است؛ آستانه‌ی متخصص جدا و پایین‌تر تنظیم می‌شود.</p>
                        </div>
                        <div>
                            <label class="form-label">درصد جریمه کاربر</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="customer_cancellation_fee_percentage"
                                       value="{{ $settings->customer_cancellation_fee_percentage }}"
                                       class="form-input persian-number" style="padding-left:2.5rem;">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--admin-text-dim);">%</span>
                            </div>
                            <p class="form-hint">کل این مبلغ به کیف‌پول ادمین اضافه می‌شود؛ مابقی به مشتری برمی‌گردد.</p>
                        </div>
                        <div>
                            <label class="form-label">درصد جریمه متخصص</label>
                            <div class="relative">
                                <input type="number" step="0.1" name="specialist_cancellation_penalty_percentage"
                                       value="{{ $settings->specialist_cancellation_penalty_percentage }}"
                                       class="form-input persian-number" style="padding-left:2.5rem;">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--admin-text-dim);">%</span>
                            </div>
                            <p class="form-hint">در صورت لغو نوبت توسط متخصص؛ از مبلغی که قرار بود به مشتری برگردد کسر و به کیف‌پول ادمین اضافه می‌شود.</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">بازه زمانی جریمه لغو متخصص (ساعت)</label>
                            <input type="number" name="specialist_cancellation_before_hours"
                                   value="{{ $settings->specialist_cancellation_before_hours }}"
                                   class="form-input persian-number max-w-xs">
                            <p class="form-hint">اگر متخصص زودتر از این مقدار قبل از نوبت لغو کند، جریمه‌ای اعمال نمی‌شود؛ فقط لغو نزدیک به زمان نوبت جریمه دارد.</p>
                        </div>
                        <div class="sm:col-span-2 pt-2" style="border-top:1px dashed var(--admin-border);">
                            <p class="text-sm font-bold mb-3" style="color:var(--admin-text);">جریمه‌ی تشدیدی برای لغو مکرر متخصص (اختیاری)</p>
                        </div>
                        <div>
                            <label class="form-label">آستانه‌ی تعداد لغو</label>
                            <input type="number" name="specialist_repeat_cancellation_threshold"
                                   value="{{ $settings->specialist_repeat_cancellation_threshold }}"
                                   class="form-input persian-number max-w-xs">
                            <p class="form-hint">اگر متخصص در بازه‌ی زیر به این تعداد یا بیشتر نوبت لغو کند، جریمه‌ی همان لغو افزایش می‌یابد. مقدار ۰ یعنی این قابلیت غیرفعال است.</p>
                        </div>
                        <div>
                            <label class="form-label">بازه‌ی زمانی شمارش (روز)</label>
                            <input type="number" name="specialist_repeat_cancellation_window_days"
                                   value="{{ $settings->specialist_repeat_cancellation_window_days }}"
                                   class="form-input persian-number max-w-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">درصد جریمه‌ی اضافه (روی درصد جریمه‌ی معمولی متخصص)</label>
                            <div class="relative max-w-xs">
                                <input type="number" step="0.1" name="specialist_repeat_cancellation_extra_percentage"
                                       value="{{ $settings->specialist_repeat_cancellation_extra_percentage }}"
                                       class="form-input persian-number" style="padding-left:2.5rem;">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--admin-text-dim);">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Immediate withdrawal --}}
                <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3 flex items-center justify-between text-sm font-bold"
                         style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                        <span>تنظیمات برداشت فوری</span>
                        <div class="flex items-center gap-2">
                            <input type="hidden" name="instant_withdrawal_enabled" value="0">
                            <label class="toggle-switch">
                                <input type="checkbox" name="instant_withdrawal_enabled" value="1" id="instant_toggle"
                                    {{ $settings->instant_withdrawal_enabled ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="text-xs font-normal" style="color:var(--admin-text-dim);">فعال‌سازی</span>
                        </div>
                    </div>
                    <div class="p-5" id="instant_settings"
                         style="{{ $settings->instant_withdrawal_enabled ? '' : 'opacity:0.4; pointer-events:none;' }}">
                        <label class="form-label">کارمزد ثابت برداشت فوری (تومان)</label>
                        <div class="relative max-w-xs">
                            <input type="number" step="1000" name="instant_withdrawal_fee"
                                   value="{{ $settings->instant_withdrawal_fee }}"
                                   class="form-input persian-number">
                        </div>
                    </div>
                </div>

                {{-- Save --}}
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white"
                            style="background:var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                        </svg>
                        ذخیره تنظیمات
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('instant_toggle').addEventListener('change', function() {
            const div = document.getElementById('instant_settings');
            div.style.opacity = this.checked ? '1' : '0.4';
            div.style.pointerEvents = this.checked ? '' : 'none';
        });
    </script>
@endpush
