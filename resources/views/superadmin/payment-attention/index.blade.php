@extends('layouts.superadmin')

@section('title', 'پرداخت‌های نیازمند بررسی')

@section('content')
    {{--
        ⭐ ۲۰۲۶-۱۰-۱۵: پرداخت اشتراک و بسته‌ی پیامک به درگاه خود پلتفرم می‌رود؛ وقتی بررسی خودکار (payments:reconcile) کنار می‌کشد،
        قبلاً این ردیف‌ها در پنل مدیر سالن ظاهر می‌شدند. حالا فقط اینجا.
    --}}
    <div class="sa-card p-5 mb-5 text-sm leading-7" style="color: var(--sa-text-dim);">
        این پرداخت‌ها به درگاه زرین‌پال پلتفرم رفته‌اند و پاسخ تأیید نرسیده است. در پنل زرین‌پال ببینید مبلغ گرفته شده یا نه.
        اگر گرفته شده، از صفحه‌ی ویرایش همان سالن <b style="color: var(--sa-text);">اشتراک را تمدید کنید</b> یا <b style="color: var(--sa-text);">اعتبار پیامک بدهید</b>،
        بعد اینجا نتیجه را ثبت کنید. با ثبت، هیچ پولی خودکار جابه‌جا نمی‌شود.
    </div>

    <div class="space-y-4">
        @forelse ($transactions as $tx)
            <div class="sa-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-bold">{{ $tx->purpose === 'subscription' ? 'خرید/تمدید اشتراک' : 'خرید بسته‌ی پیامک' }} · {{ number_format(intdiv((int) $tx->amount_rial, 10)) }} تومان</div>
                        <div class="text-sm mt-1" style="color: var(--sa-text-dim);">
                            سالن: <a href="{{ route('superadmin.salons.edit', $tx->salon_id) }}" style="color: var(--sa-accent);">{{ $salonNames[$tx->salon_id] ?? '#'.$tx->salon_id }}</a>
                            · {{ $tx->user?->name ?? '—' }} · {{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($tx->created_at))->format('Y/m/d H:i') }} · تراکنش #{{ $tx->id }} ({{ $tx->status }})
                        </div>
                        <div class="text-sm mt-2">{{ $tx->attentionReason() }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('superadmin.payment-attention.resolve', $tx->id) }}" class="mt-4 flex flex-wrap gap-3 items-end">
                    @csrf
                    <div class="flex-1" style="min-width: 16rem;">
                        <label class="sa-label" for="note-{{ $tx->id }}">نتیجه‌ی بررسی</label>
                        <input id="note-{{ $tx->id }}" name="note" class="sa-input" required minlength="3" maxlength="500" placeholder="مثلاً: مبلغ گرفته نشده بود / اشتراک دستی تمدید شد">
                    </div>
                    <button type="submit" class="sa-btn">رسیدگی شد</button>
                </form>
            </div>
        @empty
            <div class="sa-card p-8 text-center" style="color: var(--sa-text-dim);">پرداختی برای بررسی نیست.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
@endsection
