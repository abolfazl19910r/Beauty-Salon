@extends('layouts.admin')
@section('title', 'اشتراک و صورتحساب')

@section('content')
    <div class="fade-in max-w-4xl">
        <div class="flex justify-between items-center mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                اشتراک و صورتحساب
            </h1>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded-lg text-sm" style="background:var(--admin-accent-light); color:var(--admin-accent);">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg text-sm" style="background:#fee2e2; color:#b91c1c;">
                {{ $errors->first() }}
            </div>
        @endif

        @include('admin.partials.merchant-warning', ['salon' => $salon])
        @include('admin.partials.salon-public-link', ['salon' => $salon])

        {{-- وضعیت فعلی اشتراک --}}
        <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                وضعیت فعلی
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
                <div>
                    <div style="color:var(--admin-text-dim);">وضعیت</div>
                    <div class="font-bold mt-1">
                        @if ($salon->subscription_ends_at->isPast())
                            <span style="color:#b91c1c;">منقضی‌شده</span>
                        @elseif ($salon->isOnTrial())
                            <span style="color:#92400E;">دوره‌ی آزمایشی رایگان</span>
                        @else
                            <span style="color:var(--admin-accent);">فعال</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div style="color:var(--admin-text-dim);">اشتراک تا</div>
                    <div class="font-bold mt-1">{{ jalali_date($salon->subscription_ends_at) }}</div>
                </div>
                <div>
                    <div style="color:var(--admin-text-dim);">نوع فعلی</div>
                    <div class="font-bold mt-1">{{ ['1m' => 'یک ماهه', '3m' => 'سه ماهه', '6m' => 'شش ماهه', '12m' => 'دوازده ماهه'][$salon->subscription_type] ?? $salon->subscription_type }}</div>
                </div>
            </div>
        </div>

        {{-- خرید/تمدید آنلاین --}}
        <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                تمدید / خرید آنلاین اشتراک
            </div>
            <form method="POST" action="{{ route('admin.billing.purchase') }}" class="p-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    @foreach (['1m' => 'یک ماهه', '3m' => 'سه ماهه', '6m' => 'شش ماهه', '12m' => 'دوازده ماهه'] as $type => $label)
                        <label class="rounded-lg p-4 cursor-pointer text-center" style="border:1px solid var(--admin-border);">
                            <input type="radio" name="subscription_type" value="{{ $type }}" class="ml-1" {{ $salon->subscription_type === $type ? 'checked' : '' }}>
                            <div class="font-bold mt-2" style="color:var(--admin-text);">{{ $label }}</div>
                            <div class="text-sm mt-1 persian-number" style="color:var(--admin-text-dim);">{{ number_format($prices[$type]) }} تومان</div>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="mt-4 px-5 py-2.5 rounded-lg text-sm font-bold text-white" style="background:var(--admin-accent);">
                    پرداخت و تمدید
                </button>
            </form>
        </div>

        {{-- تاریخچه‌ی فاکتورها --}}
        <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                تاریخچه‌ی فاکتورها
            </div>
            <table class="w-full text-sm text-right">
                <thead>
                    <tr style="color:var(--admin-text-dim); border-bottom:1px solid var(--admin-border);">
                        <th class="py-3 px-4">تاریخ</th>
                        <th class="py-3 px-4">نوع</th>
                        <th class="py-3 px-4">مبلغ</th>
                        <th class="py-3 px-4">روش</th>
                        <th class="py-3 px-4">وضعیت</th>
                        <th class="py-3 px-4">کد پیگیری</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr style="border-bottom:1px solid var(--admin-border);">
                            <td class="py-3 px-4">{{ jalali_date($invoice->created_at) }}</td>
                            <td class="py-3 px-4">{{ $invoice->subscription_type }}</td>
                            <td class="py-3 px-4 persian-number">{{ number_format($invoice->amount) }} تومان</td>
                            <td class="py-3 px-4">{{ $invoice->payment_method === 'online' ? 'آنلاین' : 'دستی (سوپر ادمین)' }}</td>
                            <td class="py-3 px-4">
                                @if ($invoice->status === 'paid')
                                    <span style="color:var(--admin-accent);">پرداخت‌شده</span>
                                @elseif ($invoice->status === 'pending')
                                    <span style="color:#b45309;">در انتظار</span>
                                @else
                                    <span style="color:#b91c1c;">ناموفق</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">{{ $invoice->ref_id ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 px-4 text-center" style="color:var(--admin-text-dim);">هنوز فاکتوری ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $invoices->links() }}</div>
        </div>
    </div>
@endsection
