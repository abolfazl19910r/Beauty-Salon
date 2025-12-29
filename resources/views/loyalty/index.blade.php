@extends('layouts.app')

@section('title', 'پنل امتیازات وفاداری')

@section('content')
    <div class="max-w-7xl mx-auto fade-in">
        <div class="bg-gradient-to-r from-pink-500 to-purple-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center">
                        <svg class="w-8 h-8 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        باشگاه مشتریان وفادار
                    </h1>
                    <p class="text-pink-100">از هر خرید امتیاز بگیرید و جوایز ویژه دریافت کنید!</p>
                </div>
                <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl p-6">
                    <div class="text-5xl font-bold persian-number">{{ number_format($userPoints) }}</div>
                    <div class="text-sm mt-1">امتیاز فعال شما</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @if($expiringPoints > 0)
                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6 hover-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-yellow-600 font-semibold mb-1">⏰ در حال انقضا</div>
                            <div class="text-3xl font-bold text-yellow-700 persian-number">{{ number_format($expiringPoints) }}</div>
                            <div class="text-sm text-yellow-600 mt-1">امتیاز تا 30 روز آینده</div>
                        </div>
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 hover-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-green-600 font-semibold mb-1">🎟️ کدهای تخفیف</div>
                        <div class="text-3xl font-bold text-green-700 persian-number">{{ $activeCodes->count() }}</div>
                        <div class="text-sm text-green-600 mt-1">کد فعال</div>
                    </div>
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </div>
                </div>
            </div>

            @if($nextReward)
                <div class="bg-purple-50 border-2 border-purple-200 rounded-xl p-6 hover-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="text-purple-600 font-semibold mb-1">🎁 هدف بعدی</div>
                            <div class="text-sm text-purple-700 font-medium mb-2">{{ $nextReward->title }}</div>
                            <div class="w-full bg-purple-200 rounded-full h-2.5">
                                <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ min(($userPoints / $nextReward->required_points) * 100, 100) }}%"></div>
                            </div>
                            <div class="text-xs text-purple-600 mt-1 persian-number">
                                {{ number_format($nextReward->required_points - $userPoints) }} امتیاز مانده
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if($activeCodes->count() > 0)
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <svg class="w-6 h-6 ml-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    کدهای تخفیف فعال شما
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($activeCodes as $code)
                        <div class="border-2 border-dashed border-green-300 rounded-lg p-4 bg-green-50 hover:bg-green-100 transition">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold ml-3">
                                        {{ $code->type === 'percentage' ? '%' : '﷼' }}
                                    </div>
                                    <div>
                                        <div class="font-mono text-xl font-bold text-green-700">{{ $code->code }}</div>
                                        <div class="text-sm text-green-600">
                                            @if($code->type === 'percentage')
                                                {{ $code->amount }}% تخفیف
                                            @else
                                                {{ number_format($code->amount) }} تومان تخفیف
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <button onclick="copyCode('{{ $code->code }}')" class="text-green-600 hover:text-green-700 transition">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex justify-between text-xs text-green-600 mt-2 pt-2 border-t border-green-200">
                                <span>باقیمانده: {{ $code->max_uses - $code->used_count }} بار</span>
                                @if($code->expires_at)
                                    <span class="persian-number">تا {{ verta($code->expires_at)->format('Y/m/d') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-6 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        جوایز موجود
                    </h2>

                    <div class="space-y-4">
                        @forelse($rewards as $reward)
                            <div class="border-2 {{ $reward->isAvailableForUser(auth()->user()) ? 'border-pink-200 bg-pink-50' : 'border-gray-200 bg-gray-50' }} rounded-xl p-6 hover-shadow transition">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $reward->title }}</h3>
                                        <p class="text-sm text-gray-600 mb-3">{{ $reward->description }}</p>

                                        <div class="flex items-center gap-4 text-sm">
                                            <div class="flex items-center text-purple-600">
                                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/>
                                                </svg>
                                                <span class="persian-number">{{ number_format($reward->required_points) }} امتیاز</span>
                                            </div>

                                            <div class="flex items-center text-green-600">
                                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                @if($reward->discount_type === 'percentage')
                                                    <span class="persian-number">{{ $reward->discount_amount }}% تخفیف</span>
                                                @else
                                                    <span class="persian-number">{{ number_format($reward->discount_amount) }} تومان</span>
                                                @endif
                                            </div>

                                            @if($reward->max_uses)
                                                <div class="text-gray-500">
                                                    <span class="persian-number">باقیمانده: {{ $reward->max_uses - $reward->used_count }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mr-4">
                                        @if($reward->isAvailableForUser(auth()->user()))
                                            <form action="{{ route('loyalty.redeem', $reward) }}" method="POST" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این پاداش را دریافت کنید؟');">
                                                @csrf
                                                <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition flex items-center whitespace-nowrap">
                                                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    دریافت
                                                </button>
                                            </form>
                                        @else
                                            <div class="text-center">
                                                <div class="text-gray-400 text-sm mb-2">نیاز به</div>
                                                <div class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg font-bold persian-number">
                                                    {{ number_format($reward->required_points - $userPoints) }}
                                                </div>
                                                <div class="text-gray-400 text-xs mt-1">امتیاز بیشتر</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if(!$reward->isAvailableForUser(auth()->user()))
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-pink-500 to-purple-600 h-2 rounded-full transition-all"
                                                 style="width: {{ min(($userPoints / $reward->required_points) * 100, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p>در حال حاضر پاداشی موجود نیست</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold mb-6 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        تاریخچه اخیر
                    </h2>

                    <div class="space-y-3 max-h-[600px] overflow-y-auto">
                        @forelse($history as $point)
                            <div class="border-r-4 {{ $point->type === 'earned' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' }} rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <div class="font-semibold {{ $point->type === 'earned' ? 'text-green-700' : 'text-red-700' }}">
                                            @if($point->type === 'earned')
                                                + {{ number_format($point->points) }}
                                            @else
                                                - {{ number_format(abs($point->points)) }}
                                            @endif
                                            <span class="text-xs">امتیاز</span>
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">{{ $point->description }}</div>
                                    </div>
                                    <div class="text-xs text-gray-400 whitespace-nowrap mr-2">
                                        {{ verta($point->created_at)->formatDifference() }}
                                    </div>
                                </div>

                                @if($point->booking)
                                    <div class="text-xs text-gray-500 mt-2 pt-2 border-t {{ $point->type === 'earned' ? 'border-green-200' : 'border-red-200' }}">
                                        <span>نوبت: {{ $point->booking->service->name ?? 'نامشخص' }}</span>
                                    </div>
                                @endif

                                @if($point->expires_at && $point->type === 'earned')
                                    <div class="text-xs text-yellow-600 mt-1 flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        انقضا: {{ verta($point->expires_at)->format('Y/m/d') }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm">تاریخچه‌ای وجود ندارد</p>
                            </div>
                        @endforelse
                    </div>

                    @if($history->hasPages())
                        <div class="mt-4 pt-4 border-t">
                            {{ $history->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('کد تخفیف کپی شد: ' + code);
            });
        }
    </script>
@endsection
