@extends('layouts.app')
@section('title', 'نظرات '.$specialist->name)

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold mb-2" style="color: var(--rasta-gold-light);">
                نظرات مشتریان درباره‌ی {{ $specialist->name }}
            </h1>
            <div class="flex items-center justify-center gap-3">
                <span class="text-3xl font-bold" style="color: var(--rasta-gold);">{{ $stats['average'] }}</span>
                <span class="text-sm opacity-70">از ۵ ({{ $stats['total'] }} نظر)</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div class="rounded-xl p-4 text-center" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.15);">
                <div class="text-xs opacity-70 mb-1">کیفیت کار</div>
                <div class="font-bold" style="color: var(--rasta-gold);">{{ $stats['quality_avg'] }}</div>
            </div>
            <div class="rounded-xl p-4 text-center" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.15);">
                <div class="text-xs opacity-70 mb-1">رفتار</div>
                <div class="font-bold" style="color: var(--rasta-gold);">{{ $stats['behavior_avg'] }}</div>
            </div>
            <div class="rounded-xl p-4 text-center" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.15);">
                <div class="text-xs opacity-70 mb-1">تمیزی</div>
                <div class="font-bold" style="color: var(--rasta-gold);">{{ $stats['cleanliness_avg'] }}</div>
            </div>
            <div class="rounded-xl p-4 text-center" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.15);">
                <div class="text-xs opacity-70 mb-1">سرعت</div>
                <div class="font-bold" style="color: var(--rasta-gold);">{{ $stats['speed_avg'] }}</div>
            </div>
        </div>

        @forelse($reviews as $review)
            <div class="rounded-xl p-5 mb-4" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.1);">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold">{{ $review->user->name ?? 'کاربر رستا' }}</span>
                    <span style="color: var(--rasta-gold);">{{ str_repeat('★', $review->overall_rating) }}{{ str_repeat('☆', 5 - $review->overall_rating) }}</span>
                </div>
                @if($review->service)
                    <div class="text-xs opacity-60 mb-2">خدمت: {{ $review->service->name }}</div>
                @endif
                @if($review->comment)
                    <p class="text-sm opacity-90">{{ $review->comment }}</p>
                @endif
                @if($review->specialist_response)
                    <div class="mt-3 pt-3 text-sm" style="border-top: 1px dashed rgba(201,162,75,0.2);">
                        <span class="font-bold" style="color: var(--rasta-gold-light);">پاسخ متخصص:</span>
                        {{ $review->specialist_response }}
                    </div>
                @endif
            </div>
        @empty
            <p class="text-center opacity-60 py-10">هنوز نظری برای این متخصص ثبت نشده است.</p>
        @endforelse

        <div class="mt-6">
            {{ $reviews->links() }}
        </div>
    </div>
@endsection
