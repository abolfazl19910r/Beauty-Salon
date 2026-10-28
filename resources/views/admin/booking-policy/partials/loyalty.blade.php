{{-- بسته‌ی 5ه، تصمیم ۹: امتیاز نوبت‌های «بدون پیش‌پرداخت» — کلید مدیر (خاموش) و دو عدد اجباری بی مقدار پیش‌فرض --}}
@if ($hasLoyaltyFeature)
    @php($loyaltyOn = (bool) old('no_prepay_points_enabled', $loyalty['enabled']))
    <form action="{{ route('admin.booking-policy.loyalty') }}" method="POST" class="{{ $card }}" style="{{ $cardStyle }}" id="booking-loyalty">
        @csrf @method('PUT')
        <h2 class="font-bold" style="color: var(--admin-accent);">امتیاز باشگاه برای نوبت‌های بدون پیش‌پرداخت</h2>
        <p class="text-sm leading-7" style="color: var(--admin-text-dim);">
            نوبت با پیش‌پرداخت امتیازش را هنگام پرداخت می‌گیرد. برای نوبت بدون پیش‌پرداخت، اگر بخواهید، امتیاز فقط وقتی داده می‌شود که نوبت
            «انجام‌شده» ثبت شود (نه «نیامد» یا لغو) و بر اساس قیمت خدمت. هر دو عدد را خودتان وارد کنید.
        </p>
        <label class="flex items-center gap-2 text-sm" style="color: var(--admin-text);">
            <input type="hidden" name="no_prepay_points_enabled" value="0">
            <input type="checkbox" name="no_prepay_points_enabled" value="1" @checked($loyaltyOn)>
            امتیاز برای نوبت‌های بدون پیش‌پرداخت
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1" for="no_prepay_fixed_points" style="color: var(--admin-text-dim);">امتیاز ثابت هر نوبت انجام‌شده</label>
                <input type="number" min="0" step="1" id="no_prepay_fixed_points" name="no_prepay_fixed_points"
                       value="{{ old('no_prepay_fixed_points', $loyalty['fixed']) }}" class="{{ $input }}" style="{{ $inputStyle }}">
            </div>
            <div>
                <label class="block text-sm mb-1" for="no_prepay_amount_per_point" style="color: var(--admin-text-dim);">هر چند تومان از قیمت خدمت = ۱ امتیاز</label>
                <input type="number" min="1" step="1" id="no_prepay_amount_per_point" name="no_prepay_amount_per_point"
                       value="{{ old('no_prepay_amount_per_point', $loyalty['per_amount']) }}" class="{{ $input }}" style="{{ $inputStyle }}">
            </div>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ذخیره‌ی امتیاز</button>
    </form>
@endif
