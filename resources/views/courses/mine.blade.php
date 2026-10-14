@extends('layouts.app')
@section('title', 'دوره‌های من')

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-4xl mx-auto fade-in">
        <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
            <h1 class="text-2xl font-bold" style="{{ $gold }}">دوره‌های من</h1>
            @if (app(\App\Support\CurrentSalon::class)->get()?->hasFeature(\App\Support\Features\Feature::TreatmentCourses))
                <a href="{{ route('courses.index') }}" class="px-4 py-2 rounded-lg text-sm" style="{{ $ghost }}">خرید دوره</a>
            @endif
        </div>
        @include('courses._flash')

        @forelse ($courses as $course)
            @php $done = $course->sessions_used; @endphp
            <a href="{{ route('my-courses.show', $course) }}" class="block rounded-xl p-5 mb-4" style="{{ $card }}" data-my-course="{{ $course->id }}">
                <div class="flex justify-between items-center gap-3">
                    <h2 class="font-bold" style="{{ $gold }}">{{ $course->name }}</h2>
                    <span class="text-xs px-2 py-1 rounded-full" style="background: rgba(201,162,75,0.12); color: var(--rasta-gold);">{{ $course->statusLabel() }}</span>
                </div>
                <div class="mt-3 h-2 rounded-full overflow-hidden" style="background: rgba(201,162,75,0.12);">
                    <div class="h-full" style="width: {{ $course->sessions_total ? round($done * 100 / $course->sessions_total) : 0 }}%; background: var(--rasta-gold);"></div>
                </div>
                <div class="mt-2 flex justify-between text-xs persian-number" style="{{ $dim }}">
                    <span>{{ to_persian_num((string) $done) }} از {{ to_persian_num((string) $course->sessions_total) }} جلسه</span>
                    @if ($course->status === 'active' && $course->next_due_at)
                        <span>موعد جلسه‌ی بعد: {{ verta($course->next_due_at)->format('Y/m/d') }}</span>
                    @elseif ($course->status === 'pending_payment')
                        <span>در انتظار پرداخت</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-xl p-8 text-center" style="{{ $card }} {{ $dim }}">هنوز دوره‌ای نخریده‌اید.</div>
        @endforelse
    </div>
@endsection
