@extends('layouts.admin')
@section('title', 'پرداخت‌های نیازمند بررسی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:#D97706;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                پرداخت‌های نیازمند بررسی
            </h1>
            <a href="{{ route('admin.payment-gateways.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium"
               style="background:var(--admin-accent-light); color:var(--admin-text-dim);">درگاه‌های پرداخت</a>
        </div>

        <div class="rounded-xl p-4 mb-5 text-sm leading-7" style="background:var(--admin-surface); border:1px solid var(--admin-border); color:var(--admin-text-dim);">
            سیستم پرداخت‌هایی را که پاسخ درگاهشان نرسید خودش پیگیری می‌کند (برگشت به کارت یا کیف پول). این‌ها مواردی‌اند که
            پیگیری خودکار به نتیجه نرسید. برای هر مورد، با <strong>شناسه‌ی درگاه</strong> در پنل همان درگاه جست‌وجو کنید، بعد:
            <ul class="list-disc pr-5 mt-1">
                <li>اگر مبلغ از مشتری گرفته شده و به حساب سالن آمده است: <strong>واریز به کیف پول مشتری</strong> (پیامک هم ارسال می‌شود).</li>
                <li>اگر گرفته نشده، یا درگاه خودش به کارت برگردانده، یا کار دیگری لازم نیست: <strong>رسیدگی شد</strong>.</li>
            </ul>
        </div>

        @forelse($transactions as $tx)
            @php
                $toman = intdiv((int) $tx->amount_rial, 10);
                $subject = $tx->purpose === 'booking' ? 'پیش‌پرداخت نوبت #'.$tx->payable_id : ($tx->purpose === 'wallet_charge' ? 'شارژ کیف پول' : $tx->purpose);
                $canCredit = in_array($tx->purpose, ['booking', 'wallet_charge'], true) && $tx->user_id && in_array($tx->status, ['failed', 'reconciling', 'reversing'], true);
            @endphp
            <div class="rounded-xl p-4 mb-3" style="background:var(--admin-surface); border:1px solid var(--admin-border); border-right:3px solid #F59E0B;" data-attention="{{ $tx->id }}">
                <div class="flex flex-wrap justify-between gap-2 mb-2">
                    <div class="font-bold" style="color:var(--admin-text);">
                        {{ $subject }}
                        <span class="text-xs font-normal persian-number" style="color:var(--admin-text-light);">— تراکنش #{{ $tx->id }}</span>
                    </div>
                    <div class="font-bold persian-number" style="color:var(--admin-text);">{{ number_format($toman) }} <span class="text-xs font-normal">تومان</span></div>
                </div>

                <p class="text-sm mb-3" style="color:#92400E;">{{ $tx->attentionReason() }}</p>

                <dl class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-xs mb-3" style="color:var(--admin-text-dim);">
                    <div><dt>درگاه</dt><dd style="color:var(--admin-text);">{{ \App\Payments\GatewayCatalog::label($tx->driver) }}</dd></div>
                    <div><dt>شناسه‌ی درگاه</dt><dd class="font-mono" dir="ltr" style="color:var(--admin-text);">{{ $tx->gateway_receipt ?: $tx->token ?: '—' }}</dd></div>
                    <div><dt>مشتری</dt><dd style="color:var(--admin-text);">{{ $tx->user?->name ?? '—' }} <span class="persian-number" dir="ltr">{{ $tx->user?->phone }}</span></dd></div>
                    <div><dt>آخرین تلاش</dt><dd class="persian-number" style="color:var(--admin-text);">{{ verta($tx->updated_at)->format('Y/m/d H:i') }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.payment-attention.resolve', $tx->id) }}" class="flex flex-col lg:flex-row gap-2">
                    @csrf
                    <input type="text" name="note" required minlength="3" maxlength="500" placeholder="نتیجه‌ی بررسی پنل درگاه (مثلاً: در پنل زیبال پرداخت‌شده بود)"
                           class="flex-1 rounded-lg px-3 py-2 text-sm" style="border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);">
                    @if($canCredit)
                        <button type="submit" name="action" value="wallet_credit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background:#16A34A;"
                                onclick="return confirm('{{ number_format($toman) }} تومان به کیف پول مشتری واریز شود؟ فقط اگر در پنل درگاه دیده‌اید مبلغ گرفته شده.')">
                            واریز به کیف پول مشتری
                        </button>
                    @endif
                    <button type="submit" name="action" value="resolved" class="px-4 py-2 rounded-lg text-sm font-medium" style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                        رسیدگی شد
                    </button>
                </form>
            </div>
        @empty
            <div class="rounded-xl p-10 text-center text-sm" style="background:var(--admin-surface); border:1px solid var(--admin-border); color:var(--admin-text-dim);">
                پرداختی نیازمند بررسی نیست.
            </div>
        @endforelse

        @if($transactions->hasPages())
            <div class="mt-3">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection
