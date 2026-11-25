{{--
    کار اصلی یک نوبت در پنل کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵) — همان نام و همان رنگ در داشبورد و فهرست نوبت‌ها.
    $action از SpecialistDashboardService::primaryAction(): تأیید / انجام شد (هر دو با پرسش) / جزئیات.
    $size: 'lg' (کارت نوبت بعدی) یا پیش‌فرض.
--}}
@php
    $size ??= 'md';
    $who = $booking->user?->name ?? 'مشتری';
    $when = fa_date($booking->booking_time, true);
    $question = match ($action['kind']) {
        'confirm' => "نوبت {$who} ({$when}) تأیید شود؟ به مشتری پیامک می‌رود.",
        'complete' => "نوبت {$who} ({$when}) انجام شد؟",
        default => null,
    };
@endphp
@if ($action['route'])
    <form method="POST" action="{{ $action['route'] }}" class="{{ $size === 'lg' ? 'flex-1' : 'inline' }}" data-ui-confirm="{{ $question }}" data-spec-action="{{ $action['kind'] }}">
        @csrf @method('PUT')
        <button type="submit" class="spec-act {{ $size === 'lg' ? 'spec-act--lg w-full' : '' }}">{{ $action['kind'] === 'confirm' && $size === 'lg' ? 'تأیید نوبت' : $action['label'] }}</button>
    </form>
@else
    <a href="{{ route('specialist.bookings.show', $booking) }}" class="spec-act spec-act--ghost {{ $size === 'lg' ? 'spec-act--lg flex-1' : '' }}" data-spec-action="details">{{ $action['label'] }}</a>
@endif
