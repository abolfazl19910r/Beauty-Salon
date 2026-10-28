@extends('layouts.app')
@section('title', 'تأیید حضور')

{{-- لینک پیامکی تأیید حضور (بسته‌ی 5ه) — AttendanceLinkController --}}
@section('content')
    <div class="max-w-xl mx-auto fade-in" data-attendance-link>
        <div class="bg-m-card rounded-2xl border border-m-gold/10 p-6 space-y-4">
            @if (session('success'))
                <div class="rounded-xl px-4 py-3 text-sm bg-m-ok/15 text-m-ok">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl px-4 py-3 text-sm bg-m-bad/15 text-m-bad">{{ session('error') }}</div>
            @endif

            <h1 class="text-xl font-bold text-m-gold2">تأیید حضور در نوبت</h1>
            <div class="text-sm leading-8 text-m-tx/80">
                <div>{{ $booking->service?->name }} — {{ $booking->specialist?->name }}</div>
                <div class="persian-number">{{ verta($booking->booking_time)->format('l j F Y') }}، ساعت {{ to_persian_num($booking->booking_time->format('H:i')) }}</div>
            </div>

            @if ($booking->attendance_confirmed_at)
                <p class="text-sm text-m-ok" data-attendance-done>حضور شما تأیید شده است. منتظر دیدارتان هستیم.</p>
            @elseif (in_array($booking->status, ['pending', 'confirmed'], true) && $booking->booking_time->isFuture())
                <p class="text-sm text-m-tx/70">
                    اگر تا {{ to_persian_num((string) \App\Services\Booking\AttendanceCheckService::DEADLINE_HOURS) }} ساعت پیش از نوبت حضورتان را تأیید نکنید،
                    نوبت خودکار لغو و ساعت آن برای مشتری دیگری آزاد می‌شود.
                </p>
                <form method="POST" action="{{ route('attendance.confirm', ['token' => $token]) }}">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl text-sm font-bold bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold">
                        حضورم را تأیید می‌کنم
                    </button>
                </form>
            @else
                <p class="text-sm text-m-bad" data-attendance-closed>این نوبت دیگر فعال نیست؛ برای نوبت تازه با سالن تماس بگیرید یا دوباره رزرو کنید.</p>
            @endif
        </div>
    </div>
@endsection
