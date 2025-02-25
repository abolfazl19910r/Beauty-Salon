@extends('layouts.admin')

@section('title', 'مدیریت امتیازات')

@section('content')
    <div class="space-y-6">
        <div id="admin-loyalty"
             data-routes="{{ json_encode([
                'points' => route('api.loyalty.points'),
                'rewards' => route('api.loyalty.rewards'),
                'history' => route('api.loyalty.history'),
                'redeemReward' => route('api.loyalty.redeem-reward'),
                'export' => route('api.loyalty.export')
            ]) }}"
        >
            <!-- Fallback loading spinner -->
            <div class="flex justify-center items-center min-h-[400px]">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">آمار کلی</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>کل امتیازات فعال:</span>
                        <span>{{ number_format($totalActivePoints) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>کل کاربران دارای امتیاز:</span>
                        <span>{{ number_format($totalPointUsers) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>میانگین امتیاز هر کاربر:</span>
                        <span>{{ number_format($averageUserPoints) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    @vite('resources/css/app.css')
@endpush

@push('scripts')
    <script>
        window.initialData = {
            stats: {
                totalActivePoints: {{ $totalActivePoints }},
                totalPointUsers: {{ $totalPointUsers }},
                averageUserPoints: {{ $averageUserPoints }},
                totalRedeemedRewards: {{ $totalRedeemedRewards }}
            },
            routes: {
                points: '{{ route('api.loyalty.points') }}',
                rewards: '{{ route('api.loyalty.rewards') }}',
                history: '{{ route('api.loyalty.history') }}',
                redeemReward: '{{ route('api.loyalty.redeem-reward') }}',
                export: '{{ route('api.loyalty.export') }}'
            }
        };
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
