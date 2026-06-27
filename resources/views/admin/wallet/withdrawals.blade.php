@extends('layouts.admin')
@section('title', 'درخواست‌های برداشت')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                درخواست‌های برداشت
            </h1>
            <a href="{{ route('admin.wallet.index') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                کیف پول‌ها
            </a>
        </div>

        {{-- Statistics --}}
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #F59E0B;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">در انتظار تایید</p>
                <p class="text-2xl font-bold persian-number" style="color:#D97706;">{{ $pendingCount }}</p>
                <p class="text-xs mt-0.5 persian-number" style="color:var(--admin-text-light);">{{ number_format($pendingAmount) }} تومان</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #16A34A;">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">تکمیل شده امروز</p>
                <p class="text-2xl font-bold persian-number" style="color:#16A34A;">{{ $completedToday }}</p>
            </div>
            <div class="rounded-xl p-4" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid var(--admin-accent);">
                <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کل درخواست‌ها</p>
                <p class="text-2xl font-bold persian-number" style="color:var(--admin-text);">{{ $withdrawals->total() }}</p>
            </div>
        </div>

        {{-- Status filter --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach([
                ''           => 'همه',
                'pending'    => 'در انتظار',
                'processing' => 'در حال پردازش',
                'completed'  => 'تکمیل شده',
                'failed'     => 'ناموفق',
            ] as $val => $label)
                <a href="{{ route('admin.wallet.withdrawals', array_filter(['status' => $val])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                   style="{{ request('status', '') === $val ? 'background:var(--admin-accent);color:#fff;' : 'background:var(--admin-accent-light);color:var(--admin-text-dim);' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Table  --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border);">
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">#</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">متخصص</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">مبلغ</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">شبا</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">تاریخ</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium" style="color:var(--admin-text-dim);">عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($withdrawals as $item)
                        @php
                            $statusMap = [
                                'pending'    => ['در انتظار',       '#FFFBEB', '#92400E'],
                                'processing' => ['در حال پردازش',   '#EFF6FF', '#1D4ED8'],
                                'completed'  => ['تکمیل شده',       '#F0FDF4', '#166534'],
                                'failed'     => ['ناموفق',           '#FEF2F2', '#991B1B'],
                            ];
                            $st = $statusMap[$item->status] ?? [$item->status, '#F1F5F9', '#475569'];
                        @endphp
                        <tr style="border-bottom:1px solid var(--admin-border);"
                            onmouseover="this.style.background='var(--admin-accent-light)'"
                            onmouseout="this.style.background=''">
                            <td class="px-4 py-3 text-xs persian-number" style="color:var(--admin-text-dim);">{{ $item->id }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                         style="background:var(--admin-accent); color:#fff;">
                                        {{ mb_substr($item->specialist?->name ?? '؟', 0, 1) }}
                                    </div>
                                    <span style="color:var(--admin-text);">{{ $item->specialist?->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-bold persian-number" style="color:var(--admin-text);">
                                {{ number_format($item->amount) }}
                                <span class="text-xs font-normal" style="color:var(--admin-text-light);"> ت</span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono" dir="ltr" style="color:var(--admin-text-dim);">
                                {{ $item->formatted_iban ?? $item->iban ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs persian-number" style="color:var(--admin-text-dim);">
                                {{ verta($item->created_at)->format('Y/m/d') }}
                            </td>
                            <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  style="background:{{ $st[1] }}; color:{{ $st[2] }};">{{ $st[0] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.wallet.withdrawals.show', $item) }}"
                                   class="text-xs px-2.5 py-1 rounded-lg"
                                   style="color:var(--admin-accent); background:var(--admin-accent-light);"
                                   onmouseover="this.style.background='var(--admin-border)'"
                                   onmouseout="this.style.background='var(--admin-accent-light)'">بررسی</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm" style="color:var(--admin-text-dim);">درخواستی یافت نشد</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($withdrawals->hasPages())
                <div class="px-4 py-3" style="border-top:1px solid var(--admin-border);">
                    {{ $withdrawals->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
