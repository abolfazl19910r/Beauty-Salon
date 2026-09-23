@extends('layouts.superadmin')

@section('title', 'جزئیات فاکتور #'.$invoice->id)

@php
    $labels = \App\Http\Controllers\SuperAdmin\SuperAdminPaymentController::labels();
    $toman = fn ($n) => to_persian_num(number_format((int) $n));
    $date = fn ($d, $f = 'Y/m/d H:i') => $d ? to_persian_num(jalali_date($d, $f)) : '—';
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="font-bold text-lg">جزئیات فاکتور #{{ to_persian_num((string) $invoice->id) }}</h2>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('superadmin.payments.index') }}" class="sa-btn">→ بازگشت</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="sa-card p-5 lg:col-span-2">
            <h3 class="font-bold mb-4" style="color: var(--sa-accent);">پرداخت</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                @foreach ([
                    'وضعیت' => $labels['statuses'][$invoice->status] ?? $invoice->status,
                    'مبلغ' => $toman($invoice->amount).' تومان',
                    'پلن' => $labels['plans'][$invoice->subscription_type] ?? $invoice->subscription_type,
                    'روش پرداخت' => $labels['methods'][$invoice->payment_method] ?? $invoice->payment_method,
                    'کد پیگیری زرین‌پال (ref_id)' => $invoice->ref_id ?? '—',
                    'Authority زرین‌پال' => $invoice->authority ?? '—',
                    'تاریخ ایجاد فاکتور' => $date($invoice->created_at),
                    'تاریخ پرداخت' => $date($invoice->paid_at),
                    'شروع دوره‌ی اشتراک' => $date($invoice->period_start, 'Y/m/d'),
                    'پایان دوره‌ی اشتراک' => $date($invoice->period_end, 'Y/m/d'),
                    'ثبت‌کننده' => $invoice->createdBy ? $invoice->createdBy->name.' ('.$invoice->createdBy->phone.')' : '—',
                    'آخرین به‌روزرسانی' => $date($invoice->updated_at),
                ] as $label => $value)
                    <div>
                        <dt style="color: var(--sa-text-dim);">{{ $label }}</dt>
                        <dd class="font-medium mt-0.5" @if (in_array($label, ['کد پیگیری زرین‌پال (ref_id)', 'Authority زرین‌پال'])) dir="ltr" style="text-align:right;" @endif>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="sa-card p-5">
            <h3 class="font-bold mb-4" style="color: var(--sa-accent);">سالن</h3>
            @if ($invoice->salon)
                <div class="text-sm space-y-2">
                    <div class="font-bold">{{ $invoice->salon->name }}</div>
                    <div dir="ltr" style="color: var(--sa-text-dim); text-align:right;">{{ $invoice->salon->slug }}</div>
                    <div>اشتراک تا: {{ $date($invoice->salon->subscription_ends_at, 'Y/m/d') }}</div>
                    <div>تعداد فاکتورها: {{ to_persian_num((string) $salonTotals->cnt) }}</div>
                    <div>جمع پرداخت‌های این سالن: {{ $toman($salonTotals->paid_sum) }} تومان</div>
                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('superadmin.payments.index', ['salon_id' => $invoice->salon_id]) }}" style="color: var(--sa-accent);">همه‌ی پرداخت‌های این سالن</a>
                        <a href="{{ route('superadmin.salons.edit', $invoice->salon) }}" style="color: var(--sa-accent);">ویرایش سالن</a>
                    </div>
                </div>
            @else
                <p class="text-sm" style="color: var(--sa-text-dim);">سالن این فاکتور دیگر وجود ندارد.</p>
            @endif
        </div>
    </div>
@endsection
