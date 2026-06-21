@extends('layouts.specialist')

@section('title', 'امتیازهای من')

@section('content')
    <div class="fade-in max-w-4xl mx-auto space-y-6">

        <div>
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa flex items-center gap-2">
                <svg class="w-6 h-6 text-[var(--specialist-plum-mid)]" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                امتیازهای من
            </h1>
            <p class="text-sm text-[var(--specialist-text-dim)] mt-1">امتیازهایی که از نوبت‌های شخصی خود (به‌عنوان مشتری) کسب کرده‌اید</p>
        </div>

        {{-- Balance card --}}
        <div class="specialist-cta rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80 mb-1">موجودی امتیاز فعلی</p>
                    <p class="text-3xl font-bold persian-number">{{ number_format($currentBalance) }}</p>
                    <p class="text-xs opacity-70 mt-1">امتیاز</p>
                </div>
                <svg class="w-12 h-12 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </div>
        </div>

        @if($expiringPoints > 0)
            <div class="rounded-lg p-4 flex items-start gap-3" style="background-color: rgba(251, 191, 36, 0.07); border: 1px solid var(--specialist-border);">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-[var(--specialist-text-dim)]">
                    <span class="font-bold text-amber-300 persian-number">{{ number_format($expiringPoints) }}</span>
                    امتیاز شما تا ۳۰ روز آینده منقضی می‌شود.
                </p>
            </div>
        @endif

        {{-- History --}}
        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">تاریخچه امتیازها</h2>
            </div>

            @if($history->isEmpty())
                <div class="p-12 text-center text-[var(--specialist-inactive)]">
                    <svg class="w-14 h-14 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <p>هنوز امتیازی کسب نکرده‌اید</p>
                </div>
            @else
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($history as $point)
                        <div class="p-5 flex items-center justify-between flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                      style="background-color: {{ $point->type === 'earned' ? 'rgba(216,174,224,0.12)' : 'rgba(224,137,137,0.12)' }};">
                                    @if($point->type === 'earned')
                                        <svg class="w-5 h-5 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" style="color: #E08989;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                                        </svg>
                                    @endif
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-[var(--specialist-text)]">{{ $point->description }}</p>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-[var(--specialist-plum-muted)] flex-wrap">
                                        <span class="persian-number">{{ verta($point->created_at)->format('Y/m/d H:i') }}</span>
                                        @if($point->booking)
                                            <span class="persian-number">نوبت #{{ $point->booking_id }}</span>
                                            @if($point->booking->service)
                                                <span>{{ $point->booking->service->name }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <span class="text-lg font-bold persian-number"
                                  style="color: {{ $point->type === 'earned' ? 'var(--specialist-plum-light)' : '#E08989' }};">
                                {{ $point->type === 'earned' ? '+' : '-' }}{{ number_format($point->points) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
