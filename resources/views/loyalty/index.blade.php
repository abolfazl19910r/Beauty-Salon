@extends('layouts.app')
@section('title', 'باشگاه مشتریان')

@section('content')
    <div class="max-w-7xl mx-auto fade-in">

        {{-- Header --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/20 p-8 mb-8 overflow-hidden relative">
            <div class="absolute inset-0 opacity-5">
                <div class="absolute top-0 left-0 w-64 h-64 rounded-full bg-[#C9A24B] -translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 rounded-full bg-[#C9A24B] translate-x-1/2 translate-y-1/2"></div>
            </div>
            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-2">سالن راستا</p>
                    <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A] mb-2"
                        style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">باشگاه مشتریان وفادار</h1>
                    <p class="text-[#F8F3E9]/60 text-sm">از هر خرید امتیاز بگیرید و جوایز ویژه دریافت کنید</p>
                </div>
                <div class="text-center bg-[#C9A24B]/15 border border-[#C9A24B]/30 rounded-2xl p-6 shrink-0">
                    <p class="text-5xl font-bold text-[#E6CD8A] persian-number">{{ number_format($userPoints) }}</p>
                    <p class="text-sm text-[#F8F3E9]/60 mt-1">امتیاز فعال</p>
                </div>
            </div>
        </div>

        {{-- Statistical cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            @if($expiringPoints > 0)
                <div class="bg-[#2E2117] rounded-2xl border border-yellow-500/20 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm text-yellow-400 font-semibold">در حال انقضا</p>
                        <div class="w-10 h-10 rounded-xl bg-yellow-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-yellow-400 persian-number">{{ number_format($expiringPoints) }}</p>
                    <p class="text-xs text-[#F8F3E9]/45 mt-1">امتیاز تا ۳۰ روز آینده</p>
                </div>
            @endif

            <div class="bg-[#2E2117] rounded-2xl border border-emerald-500/20 p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm text-emerald-400 font-semibold">کدهای تخفیف فعال</p>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-emerald-400 persian-number">{{ $activeCodes->count() }}</p>
                <p class="text-xs text-[#F8F3E9]/45 mt-1">کد قابل استفاده</p>
            </div>

            @if($nextReward)
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/20 p-6">
                    <p class="text-sm text-[#C9A24B] font-semibold mb-2">هدف بعدی</p>
                    <p class="font-medium text-[#F8F3E9] mb-3 text-sm">{{ $nextReward->title }}</p>
                    <div class="w-full bg-[#1A1410] rounded-full h-2 mb-2">
                        <div class="bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] h-2 rounded-full transition-all"
                             style="width:{{ min(($userPoints/$nextReward->required_points)*100,100) }}%"></div>
                    </div>
                    <p class="text-xs text-[#F8F3E9]/45 persian-number">
                        {{ number_format($nextReward->required_points - $userPoints) }} امتیاز مانده
                    </p>
                </div>
            @endif
        </div>

        {{-- Active discount codes --}}
        @if($activeCodes->count() > 0)
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-[#C9A24B]/10 flex items-center justify-between">
                    <h2 class="font-bold text-sm text-[#E6CD8A] flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                        کدهای تخفیف فعال شما
                    </h2>
                    <a href="{{ route('loyalty.my-codes') }}" class="text-xs text-[#C9A24B] hover:text-[#E6CD8A] transition-colors">مشاهده همه</a>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($activeCodes as $code)
                        <div class="border border-dashed border-emerald-500/25 rounded-xl p-4 bg-emerald-900/10 flex items-center justify-between">
                            <div>
                                <p class="font-mono text-lg font-bold text-emerald-400">{{ $code->code }}</p>
                                <p class="text-xs text-[#F8F3E9]/55 mt-1">
                                    @if($code->type==='percentage') {{ $code->amount }}% تخفیف
                                    @else {{ number_format($code->amount) }} تومان
                                    @endif
                                    · باقیمانده: {{ $code->max_uses - $code->used_count }} بار
                                </p>
                            </div>
                            <button onclick="copyCode('{{ $code->code }}')"
                                    class="w-9 h-9 rounded-xl bg-emerald-500/15 flex items-center justify-center text-emerald-400 hover:bg-emerald-500/25 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Awards --}}
            <div class="lg:col-span-2 bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-4 border-b border-[#C9A24B]/10">
                    <h2 class="font-bold text-sm text-[#E6CD8A]">جوایز موجود</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    @forelse($rewards as $reward)
                        @php $available = $reward->isAvailableForUser(auth()->user()); @endphp
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="font-bold text-[#F8F3E9] mb-1">{{ $reward->title }}</h3>
                                    <p class="text-xs text-[#F8F3E9]/55 mb-3">{{ $reward->description }}</p>
                                    <div class="flex flex-wrap gap-3 text-xs">
                                        <span class="text-[#C9A24B] persian-number">{{ number_format($reward->required_points) }} امتیاز</span>
                                        <span class="text-emerald-400">
                                        @if($reward->discount_type==='percentage') {{ $reward->discount_amount }}% تخفیف
                                            @else {{ number_format($reward->discount_amount) }} تومان
                                            @endif
                                    </span>
                                    </div>
                                    @if(!$available)
                                        <div class="mt-3 w-full bg-[#1A1410] rounded-full h-1.5">
                                            <div class="bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] h-1.5 rounded-full"
                                                 style="width:{{ min(($userPoints/$reward->required_points)*100,100) }}%"></div>
                                        </div>
                                    @endif
                                </div>
                                <div class="shrink-0">
                                    @if($available)
                                        <form action="{{ route('loyalty.redeem', $reward) }}" method="POST"
                                              onsubmit="return confirm('آیا می‌خواهید این پاداش را دریافت کنید؟')">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all
                                                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                                                       hover:shadow-lg hover:shadow-[#C9A24B]/25">
                                                دریافت
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-center">
                                            <p class="text-xs text-[#F8F3E9]/40 mb-1">نیاز به</p>
                                            <p class="text-sm font-bold text-[#F8F3E9]/60 persian-number">
                                                {{ number_format($reward->required_points - $userPoints) }}
                                            </p>
                                            <p class="text-xs text-[#F8F3E9]/40">امتیاز بیشتر</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-14 text-center text-[#F8F3E9]/40 text-sm">در حال حاضر پاداشی موجود نیست</div>
                    @endforelse
                </div>
            </div>

            {{-- History --}}
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden sticky top-4 self-start">
                <div class="px-5 py-4 border-b border-[#C9A24B]/10">
                    <h2 class="font-bold text-sm text-[#E6CD8A]">تاریخچه اخیر</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8 max-h-[520px] overflow-y-auto">
                    @forelse($history as $point)
                        <div class="px-5 py-3.5">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold {{ $point->type==='earned' ? 'text-emerald-400' : 'text-red-400' }} persian-number">
                                        {{ $point->type==='earned' ? '+' : '-' }}{{ number_format(abs($point->points)) }} امتیاز
                                    </p>
                                    <p class="text-xs text-[#F8F3E9]/55 mt-0.5 truncate">{{ $point->description }}</p>
                                    @if($point->expires_at && $point->type==='earned')
                                        <p class="text-xs text-yellow-500/70 mt-0.5 persian-number">انقضا: {{ verta($point->expires_at)->format('Y/m/d') }}</p>
                                    @endif
                                </div>
                                <p class="text-xs text-[#F8F3E9]/40 whitespace-nowrap mr-3">{{ verta($point->created_at)->formatDifference() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-[#F8F3E9]/40 text-sm">تاریخچه‌ای وجود ندارد</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                const t = document.createElement('div');
                t.className = 'fixed bottom-5 right-5 bg-[#2E2117] border border-[#C9A24B]/30 text-[#E6CD8A] px-5 py-3 rounded-xl text-sm shadow-xl z-50';
                t.textContent = 'کد ' + code + ' کپی شد ✓';
                document.body.appendChild(t);
                setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity 0.4s'; setTimeout(()=>t.remove(),400); }, 2500);
            });
        }
    </script>
@endsection
