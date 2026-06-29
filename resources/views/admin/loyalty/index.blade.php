@extends('layouts.admin')
@section('title', 'مدیریت امتیازات')

@section('content')
    <div class="container px-6 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color:var(--admin-text)">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                مدیریت امتیازات
            </h1>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border transition-colors"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border)">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        {{-- Statistics cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @php
                $statCards = [
                    ['icon'=>'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z','bg'=>'#faf5ff','ic'=>'#7c3aed','label'=>'کل امتیازات فعال','value'=>number_format($totalActivePoints)],
                    ['icon'=>'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75','bg'=>'#eff6ff','ic'=>'#2563eb','label'=>'کاربران دارای امتیاز','value'=>number_format($totalPointUsers)],
                    ['icon'=>'M23 6 13.5 15.5 8.5 10.5 1 18M17 6h6v6','bg'=>'#f0fdf4','ic'=>'#16a34a','label'=>'میانگین امتیاز','value'=>number_format($averageUserPoints)],
                    ['icon'=>'M1 4h22v16H1zM1 10h22','bg'=>'#fffbeb','ic'=>'#d97706','label'=>'پاداش‌های استفاده شده','value'=>number_format($totalRedeemedRewards)],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="rounded-xl p-5 flex items-center gap-4" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
                    <div class="p-3 rounded-lg flex-shrink-0" style="background:{{ $card['bg'] }}">
                        <svg class="w-6 h-6" style="color:{{ $card['ic'] }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $card['icon'] }}"/></svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color:var(--admin-text-dim)">{{ $card['label'] }}</p>
                        <p class="text-2xl font-bold" style="color:var(--admin-text)">{{ $card['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- SPA mount point --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border)">
            <div class="p-6">
                <div id="admin-loyalty"
                     data-routes="{{ json_encode([
                    'points'       => route('admin.loyalty.points'),
                    'rewards'      => route('admin.loyalty.rewards'),
                    'history'      => route('admin.loyalty.history'),
                    'redeemReward' => url('admin/loyalty/rewards/:id/redeem'),
                    'export'       => route('admin.loyalty.export'),
                 ]) }}">
                    <div class="flex justify-center items-center min-h-96 gap-3">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2" style="border-color:var(--admin-accent)"></div>
                        <span class="text-sm" style="color:var(--admin-text-dim)">در حال بارگذاری...</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.initialData = {
                stats: {
                    totalActivePoints:    {{ $totalActivePoints }},
                    totalPointUsers:      {{ $totalPointUsers }},
                    averageUserPoints:    {{ $averageUserPoints }},
                    totalRedeemedRewards: {{ $totalRedeemedRewards }},
                },
                routes: {
                    points:       '{{ route("admin.loyalty.points") }}',
                    rewards:      '{{ route("admin.loyalty.rewards") }}',
                    history:      '{{ route("admin.loyalty.history") }}',
                    redeemReward: '{{ url("admin/loyalty/rewards/:id/redeem") }}',
                    export:       '{{ route("admin.loyalty.export") }}',
                    store:        '{{ route("admin.loyalty.store") }}',
                    edit:         '{{ url("admin/loyalty/:id/edit") }}',
                    destroy:      '{{ url("admin/loyalty/:id") }}',
                }
            };
        });
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
