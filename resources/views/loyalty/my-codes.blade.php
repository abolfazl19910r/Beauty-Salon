@extends('layouts.app')

@section('title', 'کدهای تخفیف من')

@section('content')
    <div class="max-w-6xl mx-auto fade-in">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent flex items-center">
                    <svg class="w-8 h-8 ml-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    کدهای تخفیف من
                </h1>
                <p class="text-gray-500 mt-1">لیست کدهای تخفیف دریافت شده از امتیازات</p>
            </div>
            <a href="{{ route('loyalty.index') }}" class="bg-gradient-to-r from-purple-500 to-pink-600 text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                بازگشت به پنل امتیازات
            </a>
        </div>

        @php
            $activeCodes = \App\Models\DiscountCode::where('user_id', auth()->id())
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->where('used_count', '<', \Illuminate\Support\Facades\DB::raw('max_uses'))
                ->latest()
                ->get();

            $expiredCodes = \App\Models\DiscountCode::where('user_id', auth()->id())
                ->where(function($q) {
                    $q->where('is_active', false)
                        ->orWhere(function($q2) {
                            $q2->whereNotNull('expires_at')
                                ->where('expires_at', '<=', now());
                        })
                        ->orWhereRaw('used_count >= max_uses');
                })
                ->latest()
                ->get();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90 mb-1">کدهای فعال</div>
                        <div class="text-4xl font-bold persian-number">{{ $activeCodes->count() }}</div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90 mb-1">مجموع تخفیف‌ها</div>
                        <div class="text-2xl font-bold persian-number">
                            @php
                                $totalDiscount = $activeCodes->sum(function($code) {
                                    return $code->type === 'fixed' ? $code->amount : 0;
                                });
                            @endphp
                            {{ number_format($totalDiscount) }} تومان
                        </div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-90 mb-1">استفاده شده</div>
                        <div class="text-4xl font-bold persian-number">{{ $expiredCodes->count() }}</div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                کدهای تخفیف فعال ({{ $activeCodes->count() }})
            </h2>

            @if($activeCodes->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($activeCodes as $code)
                        <div class="relative border-2 border-dashed border-green-300 rounded-lg p-5 bg-gradient-to-r from-green-50 to-emerald-50 hover:shadow-lg transition group">
                            <div class="absolute top-3 left-3">
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-bold">
                            {{ $code->type === 'percentage' ? 'درصدی' : 'مبلغی' }}
                        </span>
                            </div>

                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold ml-3 text-lg">
                                        {{ $code->type === 'percentage' ? '%' : '﷼' }}
                                    </div>
                                    <div>
                                        <div class="font-mono text-2xl font-bold text-green-700 tracking-wider">{{ $code->code }}</div>
                                        <div class="text-sm text-green-600 mt-1">
                                            @if($code->type === 'percentage')
                                                <span class="persian-number">{{ $code->amount }}% تخفیف</span>
                                            @else
                                                <span class="persian-number">{{ number_format($code->amount) }} تومان</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button onclick="copyCode('{{ $code->code }}')"
                                        class="text-green-600 hover:text-green-700 transition p-2 hover:bg-green-100 rounded-lg"
                                        title="کپی کد">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex justify-between items-center text-sm pt-3 border-t border-green-200">
                                <div class="flex items-center text-green-600">
                                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span class="persian-number">باقیمانده: {{ $code->max_uses - $code->used_count }} بار</span>
                                </div>
                                @if($code->expires_at)
                                    <div class="flex items-center text-orange-600">
                                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="persian-number">تا {{ verta($code->expires_at)->format('Y/m/d') }}</span>
                                    </div>
                                @endif
                            </div>

                            <a href="{{ route('bookings.create') }}"
                               class="mt-4 block w-full bg-green-500 text-white text-center py-2 rounded-lg hover:bg-green-600 transition font-bold">
                                استفاده در رزرو جدید
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    <p class="text-gray-500 text-lg">هنوز کد تخفیف فعالی ندارید</p>
                    <a href="{{ route('loyalty.index') }}" class="inline-block mt-4 text-pink-600 hover:text-pink-700 font-bold">
                        برای دریافت کد تخفیف به پنل امتیازات بروید ←
                    </a>
                </div>
            @endif
        </div>

        @if($expiredCodes->count() > 0)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-6 h-6 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    کدهای استفاده شده یا منقضی ({{ $expiredCodes->count() }})
                </h2>

                <div class="space-y-3">
                    @foreach($expiredCodes as $code)
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 opacity-75">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold ml-3">
                                        {{ $code->type === 'percentage' ? '%' : '﷼' }}
                                    </div>
                                    <div>
                                        <div class="font-mono text-lg font-bold text-gray-600">{{ $code->code }}</div>
                                        <div class="text-sm text-gray-500">
                                            @if($code->type === 'percentage')
                                                {{ $code->amount }}% تخفیف
                                            @else
                                                {{ number_format($code->amount) }} تومان
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-left">
                                    @if($code->used_count >= $code->max_uses)
                                        <span class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded-full">استفاده شده</span>
                                    @elseif($code->expires_at && $code->expires_at->isPast())
                                        <span class="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full">منقضی شده</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">غیرفعال</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <script>
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center';
                toast.innerHTML = `
            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            کد ${code} کپی شد!
        `;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.3s';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            });
        }
    </script>
@endsection
