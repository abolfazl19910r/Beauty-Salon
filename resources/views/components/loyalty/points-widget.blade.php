<div class="bg-white rounded-lg shadow p-4">
    <div class="flex justify-between items-center">
        <div class="text-gray-600">موجودی امتیاز</div>
        <div class="text-2xl font-bold">{{ number_format(auth()->user()->getCurrentPoints()) }}</div>
    </div>

    @if($expiringPoints = auth()->user()->getExpiringPoints())
        <div class="mt-2 text-sm text-red-500">
            {{ number_format($expiringPoints) }} امتیاز در حال انقضا
        </div>
    @endif

    @if($nextReward = auth()->user()->getNextReward())
        <div class="mt-4">
            <div class="text-sm text-gray-500 mb-1">تا پاداش بعدی</div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-blue-600 h-1.5 rounded-full"
                     style="width: {{ min(($userPoints / $nextReward->required_points) * 100, 100) }}%">
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('loyalty.index') }}"
           class="text-blue-600 hover:text-blue-800 text-sm">
            مشاهده جزئیات →
        </a>
    </div>
</div>
