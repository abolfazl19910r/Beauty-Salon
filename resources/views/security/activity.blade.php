@extends('layouts.app')

@section('title', 'فعالیت‌های امنیتی')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('security.dashboard') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">فعالیت‌های امنیتی</h1>
            </div>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
            <div class="divide-y divide-[#C9A24B]/10">
                @forelse ($logs as $log)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $log->level === 'warning' ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                            <div>
                                <p class="text-sm text-[#F8F3E9]/80">{{ $log->event_label }}</p>
                                @if (!empty($log->ip_address))
                                    <p class="text-xs text-[#F8F3E9]/35 mt-0.5">{{ $log->ip_address }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-[#F8F3E9]/40">{{ jalali_date($log->created_at, 'Y/m/d H:i') }}</span>
                    </div>
                @empty
                    <p class="px-6 py-10 text-sm text-[#F8F3E9]/45 text-center">هنوز فعالیتی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
