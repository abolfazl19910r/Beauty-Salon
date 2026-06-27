@extends('layouts.admin')
@section('title', 'بررسی درخواست برداشت')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold" style="color:var(--admin-text);">درخواست برداشت
                    <span class="text-base font-normal persian-number" style="color:var(--admin-text-dim);">#{{ $withdrawal->id }}</span>
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">{{ $withdrawal->wallet?->specialist?->name ?? '—' }}</p>
            </div>
            <a href="{{ route('admin.wallet.withdrawals') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);"
               onmouseover="this.style.background='var(--admin-border)'"
               onmouseout="this.style.background='var(--admin-accent-light)'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                بازگشت
            </a>
        </div>

        {{-- بنر وضعیت --}}
        @php
            $statusMap=['pending'=>['در انتظار بررسی','#FFFBEB','#92400E','#FCD34D'],'approved'=>['تایید شده','#EFF6FF','#1D4ED8','#93C5FD'],'paid'=>['پرداخت شده','#F0FDF4','#166534','#86EFAC'],'rejected'=>['رد شده','#FEF2F2','#991B1B','#FCA5A5']];
            $st=$statusMap[$withdrawal->status]??[$withdrawal->status,'#F1F5F9','#475569','#CBD5E1'];
        @endphp
        <div class="rounded-xl px-5 py-3 mb-5 flex items-center justify-between"
             style="background:{{ $st[1] }}; border:1px solid {{ $st[3] }};">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium" style="color:{{ $st[2] }};">وضعیت:</span>
                <span class="text-sm font-bold" style="color:{{ $st[2] }};">{{ $st[0] }}</span>
            </div>
            <span class="persian-number font-bold text-lg" style="color:{{ $st[2] }};">{{ number_format($withdrawal->amount) }} تومان</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- اطلاعات درخواست --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات متخصص</h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">نام</p>
                            <p class="font-medium" style="color:var(--admin-text);">{{ $withdrawal->wallet?->specialist?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">شماره تماس</p>
                            <p dir="ltr" style="color:var(--admin-text);">{{ $withdrawal->wallet?->specialist?->phone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">موجودی کیف پول</p>
                            <p class="persian-number font-bold" style="color:#16A34A;">{{ number_format($withdrawal->wallet?->balance ?? 0) }} تومان</p>
                        </div>
                        <div>
                            <p class="mb-1" style="color:var(--admin-text-dim);">تاریخ درخواست</p>
                            <p class="persian-number" style="color:var(--admin-text);">{{ verta($withdrawal->created_at)->format('Y/m/d H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">اطلاعات بانکی</h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">شماره شبا</span>
                            <span class="font-mono font-bold text-xs" dir="ltr" style="color:var(--admin-text);">{{ $withdrawal->iban }}</span>
                        </div>
                        <div class="flex justify-between py-2" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">نام صاحب حساب</span>
                            <span style="color:var(--admin-text);">{{ $withdrawal->account_holder_name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between py-2" style="border-bottom:1px dashed var(--admin-border);">
                            <span style="color:var(--admin-text-dim);">نام بانک</span>
                            <span style="color:var(--admin-text);">{{ $withdrawal->bank_name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span style="color:var(--admin-text-dim);">مبلغ نهایی پس از کارمزد</span>
                            <span class="persian-number font-bold" style="color:var(--admin-text);">{{ number_format($withdrawal->final_amount) }} تومان</span>
                        </div>
                    </div>
                    @if($withdrawal->notes)
                        <div class="mt-4 p-3 rounded-lg text-sm" style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                            <span class="font-medium" style="color:var(--admin-text);">یادداشت: </span>{{ $withdrawal->notes }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- عملیات --}}
            <div class="space-y-4">
                @if($withdrawal->status === 'pending')
                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">عملیات</h2>
                        <div class="space-y-2">
                            <form action="{{ route('admin.wallet.approve-withdrawal', $withdrawal) }}" method="POST">
                                @csrf @method('PUT')
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-white"
                                        style="background:#16A34A;"
                                        onmouseover="this.style.background='#15803D'"
                                        onmouseout="this.style.background='#16A34A'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    تایید و پردازش پرداخت
                                </button>
                            </form>
                            <form action="{{ route('admin.wallet.reject-withdrawal', $withdrawal) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="mb-2">
                            <textarea name="rejection_reason" rows="2" placeholder="دلیل رد (اختیاری)"
                                      class="w-full rounded-lg px-3 py-2 text-sm outline-none"
                                      style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text); font-family:inherit;"></textarea>
                                </div>
                                <button type="button"
                                        data-confirm-delete data-confirm-message="آیا از رد این درخواست اطمینان دارید؟"
                                        class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium"
                                        style="background:#FEF2F2; color:#991B1B;"
                                        onmouseover="this.style.background='#FEE2E2'"
                                        onmouseout="this.style.background='#FEF2F2'">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                    رد درخواست
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif($withdrawal->status === 'approved')
                    <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                        <h2 class="text-sm font-bold mb-4 pb-3" style="color:var(--admin-text); border-bottom:1px solid var(--admin-border);">تایید پرداخت</h2>
                        <form action="{{ route('admin.wallet.mark-paid', $withdrawal) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="block text-xs font-medium mb-1.5" style="color:var(--admin-text-dim);">شماره پیگیری</label>
                                <input type="text" name="tracking_number" dir="ltr"
                                       class="w-full rounded-lg px-3 py-2 text-sm outline-none"
                                       style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);"
                                       placeholder="شماره پیگیری واریز">
                            </div>
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-white"
                                    style="background:var(--admin-accent);"
                                    onmouseover="this.style.background='var(--admin-accent-hover)'"
                                    onmouseout="this.style.background='var(--admin-accent)'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                </svg>
                                تایید پرداخت
                            </button>
                        </form>
                    </div>
                @else
                    <div class="rounded-xl p-5" style="background:var(--admin-accent-light); border:1px solid var(--admin-border);">
                        <p class="text-sm font-medium" style="color:var(--admin-text);">این درخواست نهایی شده</p>
                        @if($withdrawal->paid_at)
                            <p class="text-xs mt-1 persian-number" style="color:var(--admin-text-dim);">
                                تاریخ پرداخت: {{ verta($withdrawal->paid_at)->format('Y/m/d H:i') }}
                            </p>
                        @endif
                        @if($withdrawal->tracking_number)
                            <p class="text-xs mt-1" style="color:var(--admin-text-dim);">
                                شماره پیگیری: <span dir="ltr">{{ $withdrawal->tracking_number }}</span>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
