@extends('layouts.admin')
@section('title', 'آمار نظرات')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold" style="color:var(--admin-text);">آمار نظرات و ارزیابی‌ها</h1>
            <a href="{{ route('admin.reviews.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        {{--General cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل نظرات</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">میانگین امتیاز</p>
                <p class="text-2xl font-bold persian-number" style="color:#F59E0B;">{{ number_format($stats['average'] ?? 0, 1) }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">تایید شده</p>
                <p class="text-2xl font-bold persian-number" style="color:#16A34A;">{{ $stats['approved'] ?? 0 }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">ویژه</p>
                <p class="text-2xl font-bold persian-number" style="color:#1D4ED8;">{{ $stats['featured'] ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Distribution of points --}}
            <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                    توزیع امتیازها
                </div>
                <div class="p-4 space-y-3">
                    @for($i=5; $i>=1; $i--)
                        @php
                            $count = $stats['by_rating'][$i] ?? 0;
                            $total = $stats['total'] ?? 1;
                            $pct   = $total > 0 ? ($count / $total) * 100 : 0;
                        @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-12 text-left persian-number" style="color:var(--admin-text-dim);">{{ $i }} ★</span>
                            <div class="flex-1 h-2 rounded-full" style="background:var(--admin-border);">
                                <div class="h-2 rounded-full transition-all"
                                     style="width:{{ $pct }}%; background:{{ $i>=4 ? '#16A34A' : ($i>=3 ? '#F59E0B' : '#DC2626') }};"></div>
                            </div>
                            <span class="w-10 persian-number text-xs" style="color:var(--admin-text-dim);">{{ $count }}</span>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Top experts --}}
            <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                    برترین متخصصین (بر اساس نظرات)
                </div>
                <div class="divide-y" style="border-color:var(--admin-border);">
                    @forelse($stats['top_specialists'] ?? [] as $specialist)
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                     style="background:var(--admin-accent); color:#fff;">
                                    {{ mb_substr($specialist->name, 0, 1) }}
                                </div>
                                <span style="color:var(--admin-text);">{{ $specialist->name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="persian-number text-xs" style="color:var(--admin-text-dim);">{{ $specialist->reviews_count }} نظر</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold persian-number" style="background:#FFFBEB; color:#92400E;">
                                ★ {{ number_format($specialist->reviews_avg_overall_rating ?? 0, 1) }}
                            </span>
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-sm" style="color:var(--admin-text-dim);">داده‌ای موجود نیست</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
