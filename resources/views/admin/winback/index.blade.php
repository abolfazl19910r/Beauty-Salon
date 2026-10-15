@extends('layouts.admin')
@section('title', 'پیگیری مشتریان غایب')

@section('content')
    @php
        $input = 'w-full rounded-lg px-3 py-2 text-sm';
        $inputStyle = 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);';
        $type = old('discount_type', $setting->discount_type ?? '');
    @endphp
    <div class="fade-in" style="max-width: 56rem;">
        <div class="mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">پیگیری خودکار مشتریان غایب</h1>
            <p class="text-sm mt-1" style="color:var(--admin-text-dim); line-height:1.9;">
                مشتری‌ای که مدتی نیامده و نوبت آینده هم ندارد، خودکار یک پیامک دعوت (و اگر بخواهید، کد تخفیف یک‌بارمصرف مخصوص خودش) می‌گیرد.
                هر مشتری در هر دوره‌ی غیبت فقط یک پیامک می‌گیرد؛ اگر برگشت و دوباره مدتی نیامد، دوباره. پیامک‌ها هر روز ساعت ۱۱ صبح از
                سهمیه‌ی پیامک سالن فرستاده می‌شوند و همیشه ۲۰٪ سهمیه‌ی ماه برای پیامک‌های نوبت می‌ماند. هر پیامک لینک انصراف دارد.
            </p>
        </div>

        {{-- بسته‌ی ج: کمپین دستی برای مشتری‌هایی که پنجره‌ی پیگیری خودکار را رد کرده‌اند --}}
        <div class="rounded-xl p-4 mb-5 flex justify-between items-center gap-3 flex-wrap" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div>
                <div class="font-bold text-sm" style="color:var(--admin-text);">ارسال دستی به مشتری‌های قدیمی</div>
                <p class="text-xs mt-1" style="color:var(--admin-text-dim);">پیگیری خودکار فقط مشتری‌هایی را می‌گیرد که تازه از مرز غیبت گذشته‌اند؛ برای قدیمی‌ترها (تا ۲ سال) کمپین دستی بسازید.</p>
            </div>
            @if (app(\App\Support\CurrentSalon::class)->get()?->hasFeature(\App\Support\Features\Feature::WinbackManual))
                <a href="{{ route('admin.winback.campaigns.index') }}" class="px-4 py-2 rounded-lg text-sm font-bold" style="background:var(--admin-accent); color:rgb(var(--m-on-solid));">کمپین‌ها</a>
            @else
                @include('admin.partials.feature-locked', ['feature' => \App\Support\Features\Feature::WinbackManual])
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="text-xs" style="color:var(--admin-text-dim);">پیامک ۳۰ روز اخیر</div>
                <div class="text-2xl font-bold mt-1" style="color:var(--admin-text);">{{ to_persian_num(number_format($stats['sent'])) }}</div>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="text-xs" style="color:var(--admin-text-dim);">بعد از پیامک نوبت گرفتند</div>
                <div class="text-2xl font-bold mt-1" style="color:var(--admin-accent);">
                    {{ to_persian_num(number_format($stats['returned'])) }}
                    @if ($stats['sent'] > 0)
                        <span class="text-sm font-medium" style="color:var(--admin-text-dim);">({{ to_persian_num(round($stats['returned'] * 100 / $stats['sent'])) }}٪)</span>
                    @endif
                </div>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="text-xs" style="color:var(--admin-text-dim);">در نوبت پیامک امروز</div>
                <div class="text-2xl font-bold mt-1" style="color:var(--admin-text);">{{ $setting->enabled ? to_persian_num(number_format($dueToday)) : '—' }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.winback.update') }}" class="rounded-xl p-5 mb-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            @csrf
            @method('PUT')

            <label class="flex items-center gap-3 mb-5 cursor-pointer">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" class="w-5 h-5 rounded" @checked(old('enabled', $setting->enabled))>
                <span class="font-bold" style="color:var(--admin-text);">پیگیری خودکار روشن باشد</span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="lapse_days" class="block text-sm font-medium mb-1" style="color:var(--admin-text);">بعد از چند روز غیبت؟</label>
                    <input id="lapse_days" name="lapse_days" type="number" min="14" max="365" required class="{{ $input }}" style="{{ $inputStyle }}"
                           value="{{ old('lapse_days', $setting->lapse_days) }}">
                    <p class="text-xs mt-1" style="color:var(--admin-text-dim);">از آخرین نوبت انجام‌شده. پیش‌فرض ۶۰ روز؛ برای خدماتی که فاصله‌ی کوتاه‌تری دارند (مثل ناخن) کمتر بگذارید.</p>
                    @error('lapse_days') <p class="text-sm mt-1" style="color:rgb(var(--m-bad));">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="discount_type" class="block text-sm font-medium mb-1" style="color:var(--admin-text);">کد تخفیف همراه پیامک</label>
                    <select id="discount_type" name="discount_type" class="{{ $input }}" style="{{ $inputStyle }}" data-discount-type>
                        <option value="" @selected($type === '' || $type === null)>بدون کد تخفیف</option>
                        <option value="percentage" @selected($type === 'percentage')>درصدی</option>
                        <option value="fixed" @selected($type === 'fixed')>مبلغ ثابت (تومان)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4" data-discount-fields>
                <div>
                    <label for="discount_amount" class="block text-sm font-medium mb-1" style="color:var(--admin-text);">مقدار تخفیف <span data-unit>{{ $type === 'fixed' ? '(تومان)' : '(درصد)' }}</span></label>
                    <input id="discount_amount" name="discount_amount" inputmode="numeric" class="{{ $input }}" style="{{ $inputStyle }}" dir="ltr"
                           value="{{ old('discount_amount', $setting->discount_amount !== null ? rtrim(rtrim((string) $setting->discount_amount, '0'), '.') : '') }}">
                    @error('discount_amount') <p class="text-sm mt-1" style="color:rgb(var(--m-bad));">{{ $message }}</p> @enderror
                </div>
                <div data-max-field>
                    <label for="discount_max_amount" class="block text-sm font-medium mb-1" style="color:var(--admin-text);">سقف تخفیف (تومان، اختیاری)</label>
                    <input id="discount_max_amount" name="discount_max_amount" inputmode="numeric" class="{{ $input }}" style="{{ $inputStyle }}" dir="ltr"
                           value="{{ old('discount_max_amount', $setting->discount_max_amount !== null ? (int) $setting->discount_max_amount : '') }}">
                    @error('discount_max_amount') <p class="text-sm mt-1" style="color:rgb(var(--m-bad));">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="discount_valid_days" class="block text-sm font-medium mb-1" style="color:var(--admin-text);">مهلت استفاده (روز)</label>
                    <input id="discount_valid_days" name="discount_valid_days" type="number" min="3" max="90" required class="{{ $input }}" style="{{ $inputStyle }}"
                           value="{{ old('discount_valid_days', $setting->discount_valid_days) }}">
                    @error('discount_valid_days') <p class="text-sm mt-1" style="color:rgb(var(--m-bad));">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-lg p-3 mt-5 text-sm" style="background:var(--admin-bg); color:var(--admin-text);">
                <div class="text-xs mb-1" style="color:var(--admin-text-dim);">نمونه‌ی پیامک با تنظیمات ذخیره‌شده — {{ to_persian_num($sampleParts) }} قطعه</div>
                <div style="white-space:pre-wrap;">{{ $sample }}</div>
                <a href="{{ route('admin.sms-templates.index') }}#sms-winback" class="inline-block text-xs mt-2 font-medium" style="color:var(--admin-accent);">ویرایش متن این پیامک ←</a>
            </div>

            <button type="submit" class="mt-5 px-5 py-2 rounded-lg text-sm font-medium text-m-onsolid" style="background-color: var(--admin-accent);">ذخیره</button>
        </form>

        <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <h2 class="font-bold mb-3" style="color:var(--admin-text);">پیامک‌های اخیر</h2>
            @if ($recent->isEmpty())
                <p class="text-sm" style="color:var(--admin-text-dim);">هنوز پیامکی فرستاده نشده است.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="color:var(--admin-text-dim);">
                                <th class="text-right font-medium py-2">مشتری</th>
                                <th class="text-right font-medium py-2">آخرین مراجعه</th>
                                <th class="text-right font-medium py-2">پیامک</th>
                                <th class="text-right font-medium py-2">کد</th>
                                <th class="text-right font-medium py-2">نتیجه</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $m)
                                <tr style="border-top:1px solid var(--admin-border); color:var(--admin-text);">
                                    <td class="py-2">{{ $m->user?->name ?? '—' }}</td>
                                    <td class="py-2">{{ to_persian_num(verta($m->last_visit_at)->format('Y/m/d')) }}</td>
                                    <td class="py-2">{{ to_persian_num(verta($m->sent_at)->format('Y/m/d')) }}</td>
                                    <td class="py-2" dir="ltr" style="text-align:right;">{{ $m->discountCode?->code ?? '—' }}</td>
                                    <td class="py-2">
                                        @if ($m->returned)
                                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgb(var(--m-ok) / .14); color:rgb(var(--m-ok));">نوبت گرفت</span>
                                        @else
                                            <span class="text-xs" style="color:var(--admin-text-dim);">هنوز نه</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var type = document.querySelector('[data-discount-type]');
            var fields = document.querySelector('[data-discount-fields]');
            var max = document.querySelector('[data-max-field]');
            var unit = document.querySelector('[data-unit]');
            function sync() {
                fields.style.display = type.value ? '' : 'none';
                max.style.display = type.value === 'percentage' ? '' : 'none';
                unit.textContent = type.value === 'fixed' ? '(تومان)' : '(درصد)';
            }
            type.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
