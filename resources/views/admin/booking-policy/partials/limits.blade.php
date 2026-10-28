{{-- بسته‌ی 5ه، تصمیم‌های ۱۲ تا ۱۵: سقف نوبت آنلاین هر مشتری — خالی یا صفر = بی‌سقف --}}
<form action="{{ route('admin.booking-policy.limits') }}" method="POST" class="{{ $card }}" style="{{ $cardStyle }}" id="booking-limits">
    @csrf @method('PUT')
    <h2 class="font-bold" style="color: var(--admin-accent);">سقف نوبت هر مشتری</h2>
    <p class="text-sm leading-7" style="color: var(--admin-text-dim);">
        جلوی پر کردن ساعت‌های متخصص با رزروهای پشت‌سرهم یا فیک را می‌گیرد. «روز» یعنی روز خود نوبت. فقط نوبت‌های فعال (در انتظار پرداخت،
        در انتظار تأیید، تأییدشده) شمرده می‌شوند؛ لغوشده، انجام‌شده و «نیامد» نه. جلسه‌های دوره‌ی درمانی هم شمرده می‌شوند. جابه‌جایی نوبت به
        روزی که مشتری نوبت دیگری دارد هم بسته است. مشتری با رسیدن به سقف پیام «با سالن تماس بگیرید» می‌بیند.
        نوبت دستی شما و منشی معاف است و فقط هشدار «این مشتری در همین روز نوبت دارد» نشان داده می‌شود. خالی یا صفر یعنی بی‌سقف.
    </p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1" for="daily_limit" style="color: var(--admin-text-dim);">حداکثر نوبت آنلاین در یک روز</label>
            <input type="number" min="0" max="50" step="1" id="daily_limit" name="daily_limit"
                   value="{{ old('daily_limit', $policy->daily_limit) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
        <div>
            <label class="block text-sm mb-1" for="active_limit" style="color: var(--admin-text-dim);">حداکثر نوبت‌های آینده‌ی فعال</label>
            <input type="number" min="0" max="200" step="1" id="active_limit" name="active_limit"
                   value="{{ old('active_limit', $policy->active_limit) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ذخیره‌ی سقف‌ها</button>
</form>
