@extends('layouts.app')
@section('title', $course->name)

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    @php
        $statusLabels = ['pending' => 'منتظر تایید', 'confirmed' => 'تایید شده', 'completed' => 'انجام شد', 'cancelled' => 'لغو شد', 'no_show' => 'نیامد', 'pending_payment' => 'در انتظار پرداخت'];
    @endphp
    <div class="max-w-3xl mx-auto fade-in">
        <a href="{{ route('my-courses.index') }}" class="text-sm" style="{{ $dim }}">→ دوره‌های من</a>
        <div class="rounded-xl p-6 mt-3" style="{{ $card }}">
            @include('courses._flash')
            <div class="flex justify-between items-start gap-3">
                <div>
                    <h1 class="text-2xl font-bold" style="{{ $gold }}">{{ $course->name }}</h1>
                    <p class="text-sm mt-1" style="{{ $dim }}">{{ $course->servicesLabel() }}@if ($course->specialist) — {{ $course->specialist->name }}@endif</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full" style="background: rgba(201,162,75,0.12); color: var(--rasta-gold);">{{ $course->statusLabel() }}</span>
            </div>

            <div class="mt-5 h-3 rounded-full overflow-hidden" style="background: rgba(201,162,75,0.12);">
                <div class="h-full" style="width: {{ round($course->sessions_used * 100 / max(1, $course->sessions_total)) }}%; background: var(--rasta-gold);"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm persian-number">
                <div class="rounded-lg p-3" style="{{ $inner }}"><div style="{{ $dim }}">انجام‌شده</div><div class="font-bold mt-1" style="color: var(--rasta-cream);">{{ to_persian_num((string) $course->sessions_used) }} از {{ to_persian_num((string) $course->sessions_total) }}</div></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><div style="{{ $dim }}">مانده (رزرونشده)</div><div class="font-bold mt-1" style="color: var(--rasta-cream);">{{ to_persian_num((string) $remaining) }}</div></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><div style="{{ $dim }}">موعد جلسه‌ی بعد</div><div class="font-bold mt-1" style="color: var(--rasta-cream);">{{ $course->next_due_at && $course->status === 'active' ? verta($course->next_due_at)->format('Y/m/d') : '—' }}</div></div>
                <div class="rounded-lg p-3" style="{{ $inner }}"><div style="{{ $dim }}">مهلت تا</div><div class="font-bold mt-1" style="color: var(--rasta-cream);">{{ $course->expires_at ? verta($course->expires_at)->format('Y/m/d') : '—' }}</div></div>
            </div>

            <div class="mt-4 rounded-lg p-3 text-sm persian-number flex flex-wrap justify-between gap-2" style="{{ $inner }}">
                <span style="color: var(--rasta-cream);">پرداخت‌شده: {{ number_format($course->amount_paid) }} از {{ number_format($course->price_total) }} تومان</span>
                @if ($course->balanceDue() > 0 && ! in_array($course->status, ['cancelled', 'completed', 'expired'], true))
                    <a href="{{ route('my-courses.pay', $course) }}" class="font-bold" style="color: var(--rasta-gold);">پرداخت {{ $course->status === 'pending_payment' ? '' : 'باقی ' }}({{ number_format($course->balanceDue()) }} تومان)</a>
                @endif
            </div>

            <div class="mt-5">
                @if ($blocker)
                    <p class="text-sm" style="{{ $dim }}">{{ $blocker }}</p>
                @else
                    <a href="{{ route('my-courses.book', $course) }}" class="inline-block px-6 py-3 rounded-lg font-bold" style="{{ $btn }}">رزرو جلسه‌ی بعد</a>
                @endif
            </div>
        </div>

        <div class="rounded-xl p-6 mt-5" style="{{ $card }}">
            <h2 class="font-bold mb-4" style="{{ $gold }}">جلسه‌ها</h2>
            @forelse ($bookings as $booking)
                <div class="py-3 {{ $loop->last ? '' : 'border-b' }}" style="border-color: rgba(201,162,75,0.12);" data-course-session="{{ $booking->id }}">
                    <div class="flex justify-between gap-3 text-sm persian-number">
                        <span style="color: var(--rasta-cream);">{{ $booking->course_session_number ? $course->sessionLabel($booking->course_session_number) : 'جلسه' }} — {{ verta($booking->booking_time)->format('Y/m/d') }} ساعت {{ $booking->booking_time->format('H:i') }}</span>
                        <span style="{{ $dim }}">{{ $booking->course_session_burned ? 'سوخت (لغو دیرهنگام)' : ($statusLabels[$booking->status] ?? $booking->status) }}</span>
                    </div>
                    @if ($notes->has($booking->id) && $notes[$booking->id]->note)
                        <p class="text-sm mt-2 leading-7" style="{{ $dim }}">یادداشت: {{ $notes[$booking->id]->note }}</p>
                    @endif
                    @if ($photos->has($booking->id))
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($photos[$booking->id] as $photo)
                                <div class="text-center">
                                    <a href="{{ route('my-courses.photos.show', [$course, $photo]) }}" target="_blank">
                                        <img src="{{ route('my-courses.photos.show', [$course, $photo]) }}" alt="عکس {{ \App\Models\CourseSessionPhoto::KINDS[$photo->kind] ?? '' }}" class="w-20 h-20 object-cover rounded-lg" loading="lazy">
                                    </a>
                                    <form method="POST" action="{{ route('my-courses.photos.destroy', [$course, $photo]) }}" onsubmit="return confirm('این عکس برای همیشه حذف شود؟');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs mt-1" style="color: #FCA5A5;">{{ \App\Models\CourseSessionPhoto::KINDS[$photo->kind] ?? '' }} · حذف</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm" style="{{ $dim }}">هنوز جلسه‌ای رزرو نشده است.</p>
            @endforelse
        </div>

        @if ($payments->isNotEmpty())
            <div class="rounded-xl p-6 mt-5" style="{{ $card }}">
                <h2 class="font-bold mb-3" style="{{ $gold }}">پرداخت‌ها</h2>
                @foreach ($payments as $payment)
                    <div class="flex justify-between text-sm py-2 persian-number" style="color: var(--rasta-cream);">
                        <span>{{ verta($payment->created_at)->format('Y/m/d') }} — {{ $payment->methodLabel() }}</span>
                        <span>{{ number_format($payment->amount) }} تومان</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
