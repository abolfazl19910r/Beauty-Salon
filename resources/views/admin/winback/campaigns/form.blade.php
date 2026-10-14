@extends('layouts.admin')
@section('title', 'کمپین تازه')

@section('content')
    <div class="fade-in" style="max-width: 56rem;">
        @php
            extract(\App\Support\Courses\CourseUi::admin());
            $v = fn (string $key, $default = null) => old($key, $values[$key] ?? $default);
            $type = $v('discount_type', '');
            $selected = array_map('intval', (array) $v('service_ids', []));
        @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">کمپین تازه: ارسال دستی به مشتری‌های قدیمی</h1>
            <a href="{{ route('admin.winback.campaigns.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">کمپین‌ها</a>
        </div>
        @if ($limitsError)
            <div class="rounded-lg p-3 text-sm mb-5" style="background:#FEF3C7; color:#92400E;">{{ $limitsError }}</div>
        @endif

        <form method="POST" action="{{ route('admin.winback.campaigns.store') }}" class="space-y-5">
            @csrf
            <div class="rounded-xl p-5 space-y-4" style="{{ $box }}">
                <div>
                    <label class="block text-sm font-medium mb-1" for="name">نام کمپین</label>
                    <input id="name" name="name" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('name') }}" required maxlength="100">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="last_visit_from_days">آخرین مراجعه، از چند روز پیش</label>
                        <input id="last_visit_from_days" name="last_visit_from_days" type="number" min="{{ $minFromDays }}" max="730" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('last_visit_from_days') }}" required>
                        <p class="text-xs mt-1" style="{{ $dim }}">دست‌کم {{ to_persian_num((string) $minFromDays) }} (کمتر از آن را پیگیری خودکار پوشش می‌دهد)</p>
                        @error('last_visit_from_days') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="last_visit_to_days">تا چند روز پیش</label>
                        <input id="last_visit_to_days" name="last_visit_to_days" type="number" min="{{ $minFromDays }}" max="730" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('last_visit_to_days') }}" required>
                        <p class="text-xs mt-1" style="{{ $dim }}">حداکثر ۷۳۰ (۲ سال)</p>
                        @error('last_visit_to_days') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="min_visits">حداقل تعداد مراجعه (اختیاری)</label>
                        <input id="min_visits" name="min_visits" type="number" min="1" max="100" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('min_visits') }}">
                    </div>
                </div>
                @if ($services->isNotEmpty())
                    <div>
                        <div class="text-sm font-medium mb-1">فقط کسانی که این خدمت‌ها را گرفته‌اند (اختیاری)</div>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($services as $service)
                                <label class="text-sm flex items-center gap-1"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selected, true))> {{ $service->name }}</label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl p-5 space-y-4" style="{{ $box }}">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="discount_type">کد تخفیف شخصی</label>
                        <select id="discount_type" name="discount_type" class="{{ $input }}" style="{{ $inputStyle }}">
                            <option value="" @selected(! $type)>بدون کد</option>
                            <option value="percentage" @selected($type === 'percentage')>درصدی</option>
                            <option value="fixed" @selected($type === 'fixed')>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="discount_amount">مقدار</label>
                        <input id="discount_amount" name="discount_amount" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('discount_amount') }}">
                        @error('discount_amount') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="discount_max_amount">سقف (درصدی)</label>
                        <input id="discount_max_amount" name="discount_max_amount" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('discount_max_amount') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="discount_valid_days">مهلت کد (روز)</label>
                        <input id="discount_valid_days" name="discount_valid_days" type="number" min="3" max="90" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('discount_valid_days', 14) }}" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" for="message">متن پیامک</label>
                    <textarea id="message" name="message" rows="3" maxlength="300" class="{{ $input }}" style="{{ $inputStyle }}" required>{{ $v('message') }}</textarea>
                    <p class="text-xs mt-1" style="{{ $dim }}">
                        جای‌خالی‌ها: @foreach (\App\Services\Marketing\WinbackCampaignService::PLACEHOLDERS as $name => [$label]) <code>{{ '{'.$name.'}' }}</code> {{ $label }}@if (! $loop->last)، @endif @endforeach
                        — <code>{لینک_انصراف}</code> لازم است.
                    </p>
                    @error('message') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="max_recipients">سقف گیرنده (جدیدترها اول)</label>
                        <input id="max_recipients" name="max_recipients" type="number" min="1" max="500" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('max_recipients', 500) }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="start_date">شروع ارسال (اختیاری، شمسی)</label>
                        <input id="start_date" name="start_date" dir="ltr" placeholder="1405/08/10" class="{{ $input }}" style="{{ $inputStyle }}" value="{{ $v('start_date') }}">
                        <p class="text-xs mt-1" style="{{ $dim }}">خالی = همین حالا (در ساعت ۹ تا ۲۰)؛ با تاریخ، از ساعت ۹ همان روز.</p>
                        @error('start_date') <p class="text-xs mt-1" style="color:#DC2626;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @if ($preview)
                <div class="rounded-xl p-5 space-y-3" style="{{ $box }}" data-preview>
                    <div class="font-bold">پیش‌نمایش</div>
                    @if ($preview['count'] === 0)
                        <p class="text-sm" style="color:#B45309;">با این فیلترها مشتری‌ای برای پیامک نیست.</p>
                    @else
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm persian-number">
                            <div><div class="text-xs" style="{{ $dim }}">گیرنده</div><b>{{ to_persian_num(number_format($preview['capped'])) }}</b>@if ($preview['count'] > $preview['capped']) <span class="text-xs" style="{{ $dim }}">از {{ to_persian_num(number_format($preview['count'])) }}</span>@endif</div>
                            <div><div class="text-xs" style="{{ $dim }}">قطعه‌ی هر پیامک</div><b>{{ to_persian_num((string) $preview['parts']) }}</b></div>
                            <div><div class="text-xs" style="{{ $dim }}">کل قطعه</div><b>{{ to_persian_num(number_format($preview['total_parts'])) }}</b></div>
                            <div><div class="text-xs" style="{{ $dim }}">سهمیه‌ی مانده بعد از کمپین</div><b>{{ to_persian_num(number_format($preview['after'])) }}</b> <span class="text-xs" style="{{ $dim }}">(۲۰٪ = {{ to_persian_num(number_format($preview['reserve'])) }} برای نوبت‌ها)</span></div>
                        </div>
                        <div class="rounded-lg p-3 text-sm" style="background:var(--admin-bg); border:1px dashed var(--admin-border); line-height:1.9;">{{ $preview['sample'] }}</div>
                        @if ($preview['allowed'] < $preview['capped'])
                            <div class="rounded-lg p-3 text-sm" style="background:#FEF3C7; color:#92400E;">
                                سهمیه‌ی این ماه (با نگه داشتن ۲۰٪) فقط برای {{ to_persian_num(number_format($preview['allowed'])) }} نفر جا دارد؛ باقی وقتی جا شد (بسته‌ی پیامک یا ماه بعد) فرستاده می‌شوند.
                                @if ($preview['allowed'] > 0)
                                    <button name="max_recipients" value="{{ $preview['allowed'] }}" class="underline font-bold">فقط {{ to_persian_num(number_format($preview['allowed'])) }} نفر جدیدتر</button>
                                @endif
                                @if ($preview['buy_parts'] > 0)
                                    یا <a href="{{ route('admin.billing.index') }}#sms" class="underline font-bold">بسته‌ی {{ to_persian_num(number_format($preview['buy_parts'])) }} قطعه‌ای ({{ to_persian_num(number_format($preview['buy_price'])) }} تومان)</a> بخرید.
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <div class="rounded-xl p-5 space-y-3" style="{{ $box }}">
                @if ($preview && $preview['count'] > 0 && ! $limitsError)
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirm_marketing" value="1" class="mt-1">
                        <span>می‌دانم این پیامک <b>تبلیغاتی</b> است و از خط خدماتی سالن فرستاده می‌شود؛ هر پیامک لینک انصراف دارد.</span></label>
                    @error('confirm_marketing') <p class="text-xs" style="color:#DC2626;">{{ $message }}</p> @enderror
                @endif
                <div class="flex gap-2 flex-wrap">
                    <button name="action" value="preview" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">پیش‌نمایش</button>
                    @if ($preview && $preview['count'] > 0 && ! $limitsError)
                        <button name="action" value="start" class="px-4 py-2 rounded-lg text-sm font-bold" style="{{ $btn }}">ساخت کمپین و شروع ارسال</button>
                    @endif
                </div>
            </div>
        </form>
    </div>
@endsection
