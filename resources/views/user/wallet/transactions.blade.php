@extends('layouts.app')
@section('title', 'تراکنش‌های کیف پول')

@section('content')
    <style>
        .tx-icon { width:2.75rem; height:2.75rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .tx-deposit   { background:rgba(52,211,153,.12); color:#6EE7B7; }
        .tx-payment   { background:rgba(248,113,113,.12); color:#FCA5A5; }
        .tx-refund    { background:rgba(96,165,250,.12);  color:#93C5FD; }
        .tx-adjustment{ background:rgba(201,162,75,.12);  color:#E6CD8A; }
        .gold-select {
            background:rgba(248,243,233,0.04); border:1px solid rgba(201,162,75,0.2);
            color:#F8F3E9; border-radius:0.625rem; padding:0.5rem 0.875rem;
            font-size:0.875rem; -webkit-appearance:none;
        }
        .gold-select option { background:#2E2117; }
        .gold-input {
            background:rgba(248,243,233,0.04); border:1px solid rgba(201,162,75,0.2);
            color:#F8F3E9; border-radius:0.625rem; padding:0.5rem 0.875rem;
            font-size:0.875rem;
        }
        .gold-input::placeholder { color:rgba(248,243,233,0.3); }
        .gold-input:focus, .gold-select:focus { outline:none; border-color:#C9A24B; }
    </style>

    <div class="max-w-7xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">کیف پول</p>
                <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                    style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تراکنش‌های کیف پول</h1>
            </div>
            <a href="{{ route('wallet.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm border border-[#C9A24B]/25
                  text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors self-start">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                بازگشت به کیف پول
            </a>
        </div>

        {{-- Statistical cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            @foreach([
                ['label'=>'موجودی فعلی','val'=>number_format($wallet->balance),'color'=>'text-[#E6CD8A]','icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['label'=>'کل واریزی‌ها','val'=>number_format($wallet->total_deposited),'color'=>'text-emerald-400','icon'=>'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6'],
                ['label'=>'کل پرداختی‌ها','val'=>number_format($wallet->total_spent),'color'=>'text-red-400','icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ] as $stat)
                <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6">
                    <p class="text-sm text-[#F8F3E9]/55 mb-3">{{ $stat['label'] }}</p>
                    <p class="text-2xl font-bold {{ $stat['color'] }} persian-number">{{ $stat['val'] }}</p>
                    <p class="text-xs text-[#F8F3E9]/40 mt-1">تومان</p>
                </div>
            @endforeach
        </div>

        {{-- Filter --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-5 mb-6">
            <form method="GET" action="{{ route('wallet.transactions') }}" class="flex flex-wrap gap-3 items-end">
                <select name="type" class="gold-select">
                    <option value="">همه تراکنش‌ها</option>
                    <option value="deposit"    {{ request('type')==='deposit'    ? 'selected':'' }}>واریز</option>
                    <option value="payment"    {{ request('type')==='payment'    ? 'selected':'' }}>پرداخت</option>
                    <option value="refund"     {{ request('type')==='refund'     ? 'selected':'' }}>بازگشت وجه</option>
                    <option value="adjustment" {{ request('type')==='adjustment' ? 'selected':'' }}>تعدیل</option>
                </select>

                <input type="text" id="date_from" name="date_from"
                       value="{{ request('date_from') }}" placeholder="از تاریخ (YYYY/MM/DD)"
                       class="gold-input" dir="ltr" autocomplete="off">

                <input type="text" id="date_to" name="date_to"
                       value="{{ request('date_to') }}" placeholder="تا تاریخ (YYYY/MM/DD)"
                       class="gold-input" dir="ltr" autocomplete="off">

                <button type="submit"
                        class="px-5 py-2 rounded-xl text-sm font-semibold transition-all
                           bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                           hover:shadow-md hover:shadow-[#C9A24B]/20">
                    جستجو
                </button>

                @if(request()->hasAny(['type','date_from','date_to']))
                    <a href="{{ route('wallet.transactions') }}"
                       class="text-sm text-[#F8F3E9]/50 hover:text-[#F8F3E9] transition-colors self-center">
                        حذف فیلتر ×
                    </a>
                @endif
            </form>
        </div>

        {{-- Transaction list --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
            <div class="px-5 py-4 border-b border-[#C9A24B]/10 flex items-center justify-between">
                <h2 class="font-bold text-sm text-[#E6CD8A]">لیست تراکنش‌ها</h2>
                <span class="text-xs text-[#F8F3E9]/45 persian-number">{{ $transactions->total() }} مورد</span>
            </div>

            @forelse($transactions as $tx)
                @php
                    $typeClass = match($tx->type) {
                        'refund'     => 'tx-refund',
                        'payment'    => 'tx-payment',
                        'deposit'    => 'tx-deposit',
                        default      => 'tx-adjustment',
                    };
                    $icon = match($tx->type) {
                        'refund'  => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6',
                        'payment' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                        'deposit' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        default   => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
                    };
                @endphp
                <div class="flex items-center gap-4 px-5 py-4 border-b border-[#C9A24B]/8 hover:bg-[#C9A24B]/5 transition-colors">
                    <div class="tx-icon {{ $typeClass }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-[#F8F3E9] truncate">{{ $tx->description }}</p>
                        <div class="flex items-center gap-3 mt-1 text-xs text-[#F8F3E9]/45">
                            <span class="persian-number">{{ \Morilog\Jalali\Jalalian::forge($tx->created_at)->format('Y/m/d - H:i') }}</span>
                            @if($tx->booking)
                                <span>نوبت #{{ $tx->booking_id }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-left shrink-0">
                        <p class="font-bold persian-number {{ $tx->amount >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                            {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                        </p>
                        <p class="text-xs text-[#F8F3E9]/40 mt-0.5">موجودی: <span class="persian-number">{{ number_format($tx->balance_after) }}</span></p>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center">
                    <p class="text-[#F8F3E9]/40">تراکنشی یافت نشد</p>
                </div>
            @endforelse

            @if($transactions->hasPages())
                <div class="px-5 py-4 border-t border-[#C9A24B]/10">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://unpkg.com/persian-date@latest/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@latest/dist/js/persian-datepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            const opts = { format:'YYYY/MM/DD', autoClose:true, initialValue:false, observer:true,
                calendar:{ persian:{ locale:'fa' } } };
            $("#date_from").persianDatepicker({ ...opts,
                onSelect: function(unix) {
                    const pd = new persianDate(unix);
                    $("#date_to").persianDatepicker('destroy');
                    $("#date_to").persianDatepicker({ ...opts, minDate: pd });
                }
            });
            $("#date_to").persianDatepicker(opts);
        });
    </script>
@endpush
