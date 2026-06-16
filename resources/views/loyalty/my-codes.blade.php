@extends('layouts.app')
@section('title', 'کدهای تخفیف من')

@section('content')
    <div class="max-w-6xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">باشگاه مشتریان</p>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">کدهای تخفیف من</h1>
                <p class="text-[#F8F3E9]/55 text-sm mt-1">لیست کدهای تخفیف دریافت شده از امتیازات</p>
            </div>
            <a href="{{ route('loyalty.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold
                  bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                  hover:shadow-lg hover:shadow-[#C9A24B]/25 transition-all self-start">
                بازگشت به پنل امتیازات
            </a>
        </div>

        @php
            $activeCodes = \App\Models\DiscountCode::where('user_id', auth()->id())
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->where('used_count', '<', \Illuminate\Support\Facades\DB::raw('max_uses'))
                ->latest()->get();

            $expiredCodes = \App\Models\DiscountCode::where('user_id', auth()->id())
                ->where(function($q) {
                    $q->where('is_active', false)
                        ->orWhere(function($q2) {
                            $q2->whereNotNull('expires_at')->where('expires_at', '<=', now());
                        })
                        ->orWhereRaw('used_count >= max_uses');
                })->latest()->get();

            $totalDiscount = $activeCodes->sum(fn($c) => $c->type === 'fixed' ? $c->amount : 0);
        @endphp

        {{-- Statistical cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="bg-[#2E2117] rounded-2xl border border-emerald-500/20 p-6">
                <p class="text-sm text-emerald-400 font-semibold mb-3">کدهای فعال</p>
                <p class="text-4xl font-bold text-emerald-400 persian-number">{{ $activeCodes->count() }}</p>
            </div>
            <div class="bg-[#2E2117] rounded-2xl border border-blue-500/20 p-6">
                <p class="text-sm text-blue-400 font-semibold mb-3">مجموع تخفیف‌ها</p>
                <p class="text-2xl font-bold text-blue-400 persian-number">{{ number_format($totalDiscount) }} تومان</p>
            </div>
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/15 p-6">
                <p class="text-sm text-[#F8F3E9]/55 font-semibold mb-3">استفاده شده / منقضی</p>
                <p class="text-4xl font-bold text-[#F8F3E9]/70 persian-number">{{ $expiredCodes->count() }}</p>
            </div>
        </div>

        {{-- Active codes --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-[#C9A24B]/10">
                <h2 class="font-bold text-sm text-[#E6CD8A]">کدهای تخفیف فعال ({{ $activeCodes->count() }})</h2>
            </div>

            @if($activeCodes->count() > 0)
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($activeCodes as $code)
                        <div class="relative border border-dashed border-emerald-500/25 rounded-xl p-5 bg-emerald-900/10">
                        <span class="absolute top-3 left-3 text-xs font-semibold px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-300">
                            {{ $code->type === 'percentage' ? 'درصدی' : 'مبلغی' }}
                        </span>
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="font-mono text-2xl font-bold text-emerald-400 tracking-wider">{{ $code->code }}</p>
                                    <p class="text-sm text-emerald-300/80 mt-1">
                                        @if($code->type === 'percentage')
                                            {{ $code->amount }}% تخفیف
                                        @else
                                            {{ number_format($code->amount) }} تومان
                                        @endif
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
                            <div class="flex justify-between items-center text-xs pt-3 border-t border-emerald-500/15 text-[#F8F3E9]/55">
                                <span class="persian-number">باقیمانده: {{ $code->max_uses - $code->used_count }} بار</span>
                                @if($code->expires_at)
                                    <span class="text-yellow-500/80 persian-number">تا {{ verta($code->expires_at)->format('Y/m/d') }}</span>
                                @endif
                            </div>
                            <a href="{{ route('bookings.create') }}"
                               class="mt-4 block text-center py-2 rounded-xl text-sm font-semibold
                                  bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 transition-colors">
                                استفاده در رزرو جدید
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-14 text-center">
                    <p class="text-[#F8F3E9]/45 mb-3">هنوز کد تخفیف فعالی ندارید</p>
                    <a href="{{ route('loyalty.index') }}" class="text-sm text-[#E6CD8A] hover:underline">
                        برای دریافت کد تخفیف به پنل امتیازات بروید ←
                    </a>
                </div>
            @endif
        </div>

        {{-- Expired/Used Codes --}}
        @if($expiredCodes->count() > 0)
            <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
                <div class="px-5 py-4 border-b border-[#C9A24B]/10">
                    <h2 class="font-bold text-sm text-[#E6CD8A]">کدهای استفاده‌شده یا منقضی ({{ $expiredCodes->count() }})</h2>
                </div>
                <div class="divide-y divide-[#C9A24B]/8">
                    @foreach($expiredCodes as $code)
                        <div class="flex items-center justify-between px-5 py-3.5 opacity-60">
                            <div>
                                <p class="font-mono text-sm font-bold text-[#F8F3E9]/60">{{ $code->code }}</p>
                                <p class="text-xs text-[#F8F3E9]/40 mt-0.5">
                                    @if($code->type === 'percentage') {{ $code->amount }}% تخفیف
                                    @else {{ number_format($code->amount) }} تومان
                                    @endif
                                </p>
                            </div>
                            @if($code->used_count >= $code->max_uses)
                                <span class="text-xs px-3 py-1 rounded-full bg-blue-500/15 text-blue-300">استفاده شده</span>
                            @elseif($code->expires_at && $code->expires_at->isPast())
                                <span class="text-xs px-3 py-1 rounded-full bg-red-500/15 text-red-300">منقضی شده</span>
                            @else
                                <span class="text-xs px-3 py-1 rounded-full bg-[#F8F3E9]/10 text-[#F8F3E9]/50">غیرفعال</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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
