@extends('layouts.admin')
@section('title', 'پرونده‌ی '.$customer->name)

@section('content')
    <div class="fade-in" style="max-width: 72rem;">
        @php extract(\App\Support\Courses\CourseUi::admin()) @endphp
        @include('admin.courses._ui')
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">{{ $customer->name }}</h1>
                <p class="text-sm mt-1" style="{{ $dim }}"><span dir="ltr">{{ $customer->phone }}</span> · عضو از {{ verta($customer->created_at)->format('Y/m/d') }}@if ($customer->marketing_sms_opted_out_at) · از پیامک‌های پیگیری انصراف داده@endif</p>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">همه‌ی مشتری‌ها</a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">نوبت انجام‌شده</div><div class="text-2xl font-bold mt-1 persian-number">{{ to_persian_num((string) $stats['completed']) }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">آخرین مراجعه</div><div class="text-lg font-bold mt-1 persian-number">{{ $stats['last_visit'] ? verta($stats['last_visit'])->format('Y/m/d') : '—' }}</div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">کیف پول</div><div class="text-lg font-bold mt-1 persian-number">{{ to_persian_num(number_format($walletBalance)) }} <span class="text-xs">تومان</span></div></div>
            <div class="rounded-xl p-4" style="{{ $box }}"><div class="text-xs" style="{{ $dim }}">امتیاز باشگاه</div><div class="text-2xl font-bold mt-1 persian-number">{{ to_persian_num(number_format($points)) }}</div></div>
        </div>

        {{-- دوره‌های درمانی (بسته‌ی ب) --}}
        @if ($courses->isNotEmpty() || $canSellCourses)
            <div class="rounded-xl overflow-hidden mb-5" style="{{ $box }}">
                <div class="px-4 py-3 text-sm font-bold flex justify-between items-center" style="background:var(--admin-accent-light); color:var(--admin-text);">
                    <span>دوره‌های درمانی</span>
                    @if ($canSellCourses && $customer->phone)
                        <a href="{{ route('admin.courses.sell', ['phone' => $customer->phone]) }}" class="text-xs font-medium" style="color:var(--admin-accent);">+ فروش دوره به این مشتری</a>
                    @endif
                </div>
                @forelse ($courses as $course)
                    <a href="{{ route('admin.courses.show', $course) }}" class="block px-4 py-3" style="border-top:1px solid var(--admin-border); color:var(--admin-text);">
                        <div class="flex justify-between items-center gap-3">
                            <span class="font-medium text-sm">{{ $course->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--admin-accent-light); color:var(--admin-accent);">{{ $course->statusLabel() }}</span>
                        </div>
                        <div class="text-xs mt-1 persian-number" style="{{ $dim }}">
                            {{ to_persian_num((string) $course->sessions_used) }} از {{ to_persian_num((string) $course->sessions_total) }} جلسه
                            · پرداخت‌شده {{ to_persian_num(number_format($course->amount_paid)) }} از {{ to_persian_num(number_format($course->price_total)) }} تومان
                            @if ($course->expires_at) · مهلت تا {{ verta($course->expires_at)->format('Y/m/d') }}@endif
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm" style="{{ $dim }}">این مشتری دوره‌ای ندارد.</p>
                @endforelse
            </div>
        @endif

        <div class="rounded-xl overflow-hidden" style="{{ $box }}">
            <div class="px-4 py-3 text-sm font-bold flex justify-between items-center" style="background:var(--admin-accent-light); color:var(--admin-text);">
                <span>آخرین نوبت‌ها</span>
                <a href="{{ route('admin.bookings.index', ['user_id' => $customer->id]) }}" class="text-xs font-medium" style="color:var(--admin-accent);">همه‌ی نوبت‌ها</a>
            </div>
            @forelse ($bookings as $booking)
                <a href="{{ route('admin.bookings.show', $booking) }}" class="flex justify-between items-center gap-3 px-4 py-3 text-sm" style="border-top:1px solid var(--admin-border); color:var(--admin-text);">
                    <span>
                        {{ $booking->service?->name ?? '—' }} · {{ $booking->specialist?->name ?? '—' }}
                        @if ($label = $booking->courseSessionLabel()) <span class="text-xs" style="color:var(--admin-accent);">({{ $label }})</span>@endif
                    </span>
                    <span class="persian-number text-xs" style="{{ $dim }}">{{ verta($booking->booking_time)->format('Y/m/d H:i') }} · {{ ['pending_payment' => 'در انتظار پرداخت', 'pending' => 'در انتظار تأیید', 'confirmed' => 'تأییدشده', 'completed' => 'انجام‌شده', 'cancelled' => 'لغوشده', 'no_show' => 'لغوشده'][$booking->status] ?? $booking->status }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm" style="{{ $dim }}">نوبتی ثبت نشده.</p>
            @endforelse
        </div>
    </div>
@endsection
