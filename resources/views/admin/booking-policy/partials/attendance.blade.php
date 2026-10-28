{{-- بسته‌ی 5ه، تصمیم ۱۶-۳: تأیید حضور با لینک (پیش‌فرض خاموش) --}}
@php $attendance = old('attendance_check', $policy->attendance_check); @endphp
<form action="{{ route('admin.booking-policy.attendance') }}" method="POST" class="{{ $card }}" style="{{ $cardStyle }}" id="booking-attendance">
    @csrf @method('PUT')
    <h2 class="font-bold" style="color: var(--admin-accent);">تأیید حضور با لینک</h2>
    <p class="text-sm leading-7" style="color: var(--admin-text-dim);">
        {{ to_persian_num((string) \App\Services\Booking\AttendanceCheckService::REQUEST_HOURS) }} ساعت پیش از نوبت، پیامکی با لینک «حضورم را تأیید می‌کنم»
        برای مشتری می‌رود (یا پوش، اگر اپ دارد). اگر تا {{ to_persian_num((string) \App\Services\Booking\AttendanceCheckService::DEADLINE_HOURS) }} ساعت
        مانده تأیید نکند، نوبت خودکار لغو و ساعت آزاد می‌شود و به مشتری و متخصص خبر داده می‌شود. فقط برای نوبت‌های بدون پیش‌پرداخت.
        هر پیامک از سهمیه‌ی پیامک سالن کم می‌شود؛ اگر پیامک نرود (مثلاً سهمیه تمام شده باشد) نوبت لغو نمی‌شود.
    </p>
    @foreach ([
        \App\Models\BookingPolicy::ATTENDANCE_OFF => 'خاموش',
        \App\Models\BookingPolicy::ATTENDANCE_FLAGGED => 'فقط برای مشتری‌های بدقول',
        \App\Models\BookingPolicy::ATTENDANCE_ALL => 'برای همه‌ی مشتری‌ها',
    ] as $value => $label)
        <label class="flex items-center gap-2 text-sm" style="color: var(--admin-text);">
            <input type="radio" name="attendance_check" value="{{ $value }}" @checked($attendance === $value)>
            {{ $label }}
        </label>
    @endforeach
    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ذخیره‌ی تأیید حضور</button>
</form>
