@extends('layouts.app')

@section('title', 'فعالیت‌های امنیتی')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('security.dashboard') }}"
               class="w-9 h-9 rounded-xl bg-m-card border border-m-gold/15 flex items-center justify-center
                  text-m-tx/60 hover:text-m-gold2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-2xl font-bold text-m-gold2"
                    style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">فعالیت‌های امنیتی</h1>
            </div>
        </div>

        <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden">
            <div class="divide-y divide-m-gold/10">
                @forelse ($logs as $log)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $log->level === 'warning' ? 'bg-m-warn' : 'bg-m-ok' }}"></span>
                            <div>
                                <p class="text-sm text-m-tx/80">{{ $log->event_label }}</p>
                                @if (!empty($log->ip_address))
                                    <p class="text-xs text-m-tx/35 mt-0.5">{{ $log->ip_address }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-m-tx/40">{{ jalali_date($log->created_at, 'Y/m/d H:i') }}</span>
                    </div>
                @empty
                    <p class="px-6 py-10 text-sm text-m-tx/45 text-center">هنوز فعالیتی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-6">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
