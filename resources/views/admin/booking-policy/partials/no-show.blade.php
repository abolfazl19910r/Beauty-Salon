{{-- بسته‌ی 5ه، تصمیم ۱۶: مشتری بدقول --}}
<form action="{{ route('admin.booking-policy.no-show') }}" method="POST" class="{{ $card }}" style="{{ $cardStyle }}" id="booking-no-show">
    @csrf @method('PUT')
    <h2 class="font-bold" style="color: var(--admin-accent);">مشتری بدقول</h2>
    <p class="text-sm leading-7" style="color: var(--admin-text-dim);">
        مشتری‌ای که چند بار «نیامد» ثبت شده، در پنل شما و پنل متخصص برچسب «بدقول» می‌گیرد.
        @if ($canPrepay)
            چون سالن شما درگاه فعال دارد، چنین مشتری‌ای (حتی در حالت بدون پیش‌پرداخت) فقط با پیش‌پرداخت آنلاین رزرو می‌کند.
        @else
            چون سالن شما درگاه فعال ندارد، رزرو او بسته نمی‌شود (مشتری از دست نمی‌رود)، ولی دو محافظ زیر برایش فعال است.
        @endif
        محدودیت هر مشتری را از صفحه‌ی همان مشتری («مشتری‌ها») برمی‌دارید. آستانه‌ی خالی یا صفر یعنی خاموش.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1" for="no_show_threshold" style="color: var(--admin-text-dim);">بدقول با این تعداد «نیامد»</label>
            <input type="number" min="0" max="50" step="1" id="no_show_threshold" name="no_show_threshold"
                   value="{{ old('no_show_threshold', $policy->no_show_threshold) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
        <div>
            <label class="block text-sm mb-1" for="no_show_window_days" style="color: var(--admin-text-dim);">در این چند روز گذشته</label>
            <input type="number" min="1" max="730" step="1" id="no_show_window_days" name="no_show_window_days"
                   value="{{ old('no_show_window_days', $policy->no_show_window_days) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
    </div>
    <div class="rounded-lg p-3 space-y-3" style="border:1px solid var(--admin-border);">
        <p class="text-sm font-bold" style="color: var(--admin-text);">وقتی پیش‌پرداخت از مشتری بدقول ممکن نیست (سالن بی درگاه)</p>
        <label class="flex items-start gap-2 text-sm" style="color: var(--admin-text);">
            <input type="hidden" name="flagged_force_pending" value="0">
            <input type="checkbox" name="flagged_force_pending" value="1" class="mt-1" @checked(old('flagged_force_pending', $policy->flagged_force_pending))>
            <span>تأیید دستی اجباری — نوبتش همیشه «منتظر تأیید» ثبت می‌شود، حتی با متخصصی که تأیید خودکار دارد.</span>
        </label>
        <div>
            <label class="block text-sm mb-1" for="flagged_active_limit" style="color: var(--admin-text-dim);">سقف کمتر: حداکثر نوبت‌های آینده‌ی فعال مشتری بدقول</label>
            <input type="number" min="0" max="50" step="1" id="flagged_active_limit" name="flagged_active_limit"
                   value="{{ old('flagged_active_limit', $policy->flagged_active_limit) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ذخیره‌ی مشتری بدقول</button>
</form>
