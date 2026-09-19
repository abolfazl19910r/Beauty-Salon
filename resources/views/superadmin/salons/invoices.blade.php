@extends('layouts.superadmin')

@section('title', 'تاریخچه‌ی فاکتورهای '.$salon->name)

@section('content')
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-bold text-lg">تاریخچه‌ی فاکتورهای «{{ $salon->name }}»</h2>
        <a href="{{ route('superadmin.salons.edit', $salon) }}" class="sa-btn">→ بازگشت به ویرایش سالن</a>
    </div>

    <div class="sa-card overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead>
                <tr style="color: var(--sa-text-dim); border-bottom: 1px solid var(--sa-border);">
                    <th class="py-3 px-4">تاریخ</th>
                    <th class="py-3 px-4">نوع اشتراک</th>
                    <th class="py-3 px-4">مبلغ</th>
                    <th class="py-3 px-4">روش پرداخت</th>
                    <th class="py-3 px-4">وضعیت</th>
                    <th class="py-3 px-4">کد پیگیری</th>
                    <th class="py-3 px-4">دوره</th>
                    <th class="py-3 px-4">ثبت‌شده توسط</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr style="border-bottom: 1px solid var(--sa-border);">
                        <td class="py-3 px-4">{{ jalali_date($invoice->created_at) }}</td>
                        <td class="py-3 px-4">{{ $invoice->subscription_type }}</td>
                        <td class="py-3 px-4">{{ number_format($invoice->amount) }} تومان</td>
                        <td class="py-3 px-4">{{ $invoice->payment_method === 'online' ? 'آنلاین (زرین‌پال)' : 'دستی' }}</td>
                        <td class="py-3 px-4">
                            @if ($invoice->status === 'paid')
                                <span style="color: var(--sa-success);">پرداخت‌شده</span>
                            @elseif ($invoice->status === 'pending')
                                <span style="color: var(--sa-text-dim);">در انتظار</span>
                            @else
                                <span style="color: var(--sa-danger);">ناموفق</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $invoice->ref_id ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @if ($invoice->period_start && $invoice->period_end)
                                {{ jalali_date($invoice->period_start) }} تا {{ jalali_date($invoice->period_end) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-4">{{ $invoice->createdBy?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 px-4 text-center" style="color: var(--sa-text-dim);">هنوز فاکتوری ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
