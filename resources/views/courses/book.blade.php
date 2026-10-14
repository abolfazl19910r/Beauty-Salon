@extends('layouts.app')
@section('title', 'رزرو جلسه‌ی دوره')

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-2xl mx-auto fade-in">
        <a href="{{ route('my-courses.show', $course) }}" class="text-sm" style="{{ $dim }}">→ {{ $course->name }}</a>
        <div class="rounded-xl p-6 mt-3" style="{{ $card }}">
            @include('courses._flash')
            <h1 class="text-xl font-bold" style="{{ $gold }}">رزرو {{ $course->sessionLabel($course->sessions_used + $course->reservedCount() + 1) }}</h1>
            <p class="text-sm mt-1" style="{{ $dim }}">بدون پرداخت؛ هزینه‌ی جلسه با خرید دوره پرداخت شده است.
                @if ($course->min_interval_days > 0) زودترین روز مجاز: {{ verta($course->earliestNextSession())->format('Y/m/d') }}.@endif
            </p>

            <div class="mt-5">
                <div class="text-sm mb-2" style="color: var(--rasta-cream);">متخصص</div>
                <div class="flex flex-wrap gap-2">
                    @forelse ($specialists as $option)
                        <a href="{{ route('my-courses.book', [$course, 'specialist' => $option->id]) }}" class="px-4 py-2 rounded-lg text-sm"
                           style="{{ $specialist?->id === $option->id ? $btn : $ghost }}">{{ $option->name }}</a>
                    @empty
                        <p class="text-sm" style="{{ $dim }}">فعلاً متخصصی برای این خدمت نیست؛ با سالن تماس بگیرید.</p>
                    @endforelse
                </div>
            </div>

            @if ($specialist)
                <div class="mt-5">
                    <div class="text-sm mb-2" style="color: var(--rasta-cream);">روز</div>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($dates as $option)
                            <a href="{{ route('my-courses.book', [$course, 'specialist' => $specialist->id, 'date' => $option]) }}" class="px-3 py-2 rounded-lg text-sm persian-number"
                               style="{{ $date === $option ? $btn : $ghost }}">{{ verta($option)->format('l d F') }}</a>
                        @empty
                            <p class="text-sm" style="{{ $dim }}">در بازه‌ی مجاز روز آزادی برای این متخصص نیست.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($date)
                <div class="mt-5">
                    <div class="text-sm mb-2" style="color: var(--rasta-cream);">ساعت</div>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($slots as $slot)
                            <form method="POST" action="{{ route('my-courses.book.store', $course) }}">
                                @csrf
                                <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="time" value="{{ $slot }}">
                                <button type="submit" class="px-3 py-2 rounded-lg text-sm persian-number" style="{{ $ghost }}">{{ $slot }}</button>
                            </form>
                        @empty
                            <p class="text-sm" style="{{ $dim }}">ساعت خالی نمانده؛ روز دیگری انتخاب کنید.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
