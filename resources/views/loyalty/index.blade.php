@extends('layouts.app')

@section('title', 'امتیازات و پاداش‌ها')

@section('content')
    <div class="max-w-7xl mx-auto py-6">
        <!-- کارت نمایش امتیاز -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">امتیازات شما</h1>
                    <p class="text-gray-600">موجودی فعلی امتیاز</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($userPoints) }}</div>
                    @if($expiringPoints = auth()->user()->getExpiringPoints())
                        <div class="text-sm text-red-500 mt-1">
                            {{ number_format($expiringPoints) }} امتیاز در حال انقضا
                        </div>
                    @endif
                </div>
            </div>

            <!-- نوار پیشرفت تا پاداش بعدی -->
            @if($nextReward)
                <div class="mt-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span>تا پاداش بعدی ({{ $nextReward->title }})</span>
                        <span>{{ number_format($nextReward->required_points - $userPoints) }} امتیاز مانده</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min(($userPoints / $nextReward->required_points) * 100, 100) }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <!-- لیست پاداش‌ها -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach($rewards as $reward)
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg">{{ $reward->title }}</h3>
                            <p class="text-gray-600 text-sm">{{ $reward->description }}</p>
                        </div>
                        <div class="text-sm font-bold {{ $userPoints >= $reward->required_points ? 'text-green-600' : 'text-gray-500' }}">
                            {{ number_format($reward->required_points) }} امتیاز
                        </div>
                    </div>

                    <div class="text-sm mb-4">
                        <div>نوع تخفیف:
                            @if($reward->discount_type === 'fixed')
                                {{ number_format($reward->discount_amount) }} تومان
                            @else
                                {{ $reward->discount_amount }}٪
                            @endif
                        </div>
                        @if($reward->max_uses)
                            <div class="text-gray-500">
                                {{ $reward->max_uses - $reward->used_count }} عدد باقی مانده
                            </div>
                        @endif
                    </div>

                    <button onclick="redeemReward({{ $reward->id }})"
                            class="w-full py-2 px-4 rounded-lg {{ $userPoints >= $reward->required_points ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-200 text-gray-500 cursor-not-allowed' }}"
                        {{ $userPoints >= $reward->required_points ? '' : 'disabled' }}>
                        دریافت پاداش
                    </button>
                </div>
            @endforeach
        </div>

        <!-- تاریخچه امتیازات -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold">تاریخچه امتیازات</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-right">تاریخ</th>
                        <th class="px-6 py-3 text-right">شرح</th>
                        <th class="px-6 py-3 text-right">نوع</th>
                        <th class="px-6 py-3 text-right">امتیاز</th>
                        <th class="px-6 py-3 text-right">تاریخ انقضا</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($history as $point)
                        <tr>
                            <td class="px-6 py-4">{{ verta($point->created_at)->format('Y/m/d H:i') }}</td>
                            <td class="px-6 py-4">{{ $point->description }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-sm {{ $point->type === 'earned' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $point->type === 'earned' ? 'دریافت' : 'مصرف' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold {{ $point->type === 'earned' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $point->type === 'earned' ? '+' : '-' }}{{ number_format($point->points) }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $point->expires_at ? verta($point->expires_at)->format('Y/m/d') : '---' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $history->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function redeemReward(rewardId) {
                if (!confirm('آیا از دریافت این پاداش اطمینان دارید؟')) return;

                fetch(`/loyalty/rewards/${rewardId}/redeem`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.discount_code) {
                            alert(`پاداش با موفقیت دریافت شد. کد تخفیف شما: ${data.discount_code}`);
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        alert('خطا در دریافت پاداش. لطفا مجددا تلاش کنید.');
                    });
            }
        </script>
    @endpush
