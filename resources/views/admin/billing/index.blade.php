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

        {{-- پیامک‌های این ماه: سقف سالن جدا از کدهای تأیید (خرج پلتفرم) --}}
        <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                پیامک‌های این ماه
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
                <div>
                    <div style="color:var(--admin-text-dim);">مصرف از سقف ماهانه (قطعه)</div>
                    <div class="font-bold mt-1 persian-number">
                        {{ to_persian_num((string) $smsUsage['used']) }} از {{ to_persian_num((string) $smsUsage['quota']) }}
                    </div>
                </div>
                <div>
                    <div style="color:var(--admin-text-dim);">باقی‌مانده (قطعه)</div>
                    <div class="font-bold mt-1 persian-number" style="color:{{ $smsUsage['remaining'] > 0 ? 'var(--admin-text)' : '#b91c1c' }};">
                        {{ to_persian_num((string) $smsUsage['remaining']) }}
                    </div>
                </div>
                <div>
                    <div style="color:var(--admin-text-dim);">کدهای تأیید (ورود، دومرحله‌ای، تأیید تلفن)</div>
                    <div class="font-bold mt-1 persian-number">{{ to_persian_num((string) $smsUsage['otp']) }}</div>
                </div>
            </div>
            <div class="px-5 pb-4 text-xs leading-6" style="color:var(--admin-text-dim);">
                سقف بر حسب «قطعه» است، همان‌طور که اپراتور پیامک حساب می‌کند: هر پیامک فارسی تا ۷۰ نویسه یک قطعه است و پیامک
                بلندتر چند قطعه (هر ۶۷ نویسه یک قطعه).
                کدهای تأیید هزینه‌ای برای سالن ندارند، از سقف کم نمی‌شوند و با تمام شدن سقف هم قطع نمی‌شوند؛ فقط برای شفافیت نمایش داده می‌شوند.
                بقیه‌ی پیامک‌ها (تأیید و یادآوری نوبت، لغو، نظرسنجی، اطلاع‌رسانی به مدیر) از سقف ماهانه‌ی سالن کم می‌شوند.
            </div>
        </div>

        {{-- بسته‌ی پیامک اضافه (۲۰۲۶-۰۹-۳۰): اعتبار منقضی نمی‌شود؛ بعد از تمام شدن سهمیه‌ی ماه مصرف می‌شود --}}
        <div id="sms-packs" class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
            <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
                بسته‌ی پیامک اضافه
            </div>
            <div class="p-5 text-sm">
                <div class="mb-4 persian-number">
                    اعتبار خریده‌شده‌ی باقی‌مانده: <strong>{{ to_persian_num(number_format($smsUsage['credit'])) }}</strong> قطعه
                    <span style="color:var(--admin-text-dim);">(این ماه {{ to_persian_num(number_format($smsUsage['credit_used'])) }} قطعه از اعتبار مصرف شد)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($smsPacks as $pack)
                        <form method="POST" action="{{ route('admin.billing.sms-pack.purchase') }}" class="rounded-lg p-4 text-center" style="border:1px solid var(--admin-border);">
                            @csrf
                            <input type="hidden" name="parts" value="{{ $pack['parts'] }}">
                            <div class="font-bold persian-number">{{ to_persian_num(number_format($pack['parts'])) }} قطعه</div>
                            <div class="mt-1 persian-number">{{ to_persian_num(number_format($pack['price'])) }} تومان</div>
                            <button type="submit" class="mt-3 px-4 py-2 rounded-lg text-white text-sm" style="background:var(--admin-accent);">خرید</button>
                        </form>
                    @endforeach
                </div>
                <p class="text-xs leading-6 mt-4" style="color:var(--admin-text-dim);">
                    هر قطعه {{ to_persian_num(number_format($smsPartPrice)) }} تومان. اعتبار منقضی نمی‌شود و ماه بعد هم می‌ماند؛ فقط وقتی سهمیه‌ی ماهانه‌ی
                    اشتراک تمام شد مصرف می‌شود.
                </p>
                @if ($smsPurchases->isNotEmpty())
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-xs persian-number">
                            <thead><tr style="color:var(--admin-text-dim);"><th class="text-right p-2">تاریخ</th><th class="text-right p-2">قطعه</th><th class="text-right p-2">مبلغ</th><th class="text-right p-2">نوع</th><th class="text-right p-2">وضعیت</th></tr></thead>
                            <tbody>
                                @foreach ($smsPurchases as $purchase)
                                    <tr style="border-top:1px solid var(--admin-border);">
                                        <td class="p-2">{{ jalali_date($purchase->paid_at ?? $purchase->created_at, 'Y/m/d H:i') }}</td>
                                        <td class="p-2">{{ to_persian_num(number_format($purchase->parts)) }}</td>
                                        <td class="p-2">{{ $purchase->amount ? to_persian_num(number_format($purchase->amount)).' تومان' : '—' }}</td>
                                        <td class="p-2">{{ $purchase->source === 'grant' ? 'هدیه/جبران پشتیبانی' : 'خرید آنلاین' }}</td>
                                        <td class="p-2">{{ $purchase->status === 'paid' ? 'موفق' : 'ناموفق' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
                <p class="text-xs mt-3" style="color:var(--admin-text-dim);">
                    قیمت‌ها برای {{ to_persian_num((string) $specialists) }} متخصص (سقف متخصص سالن شما) است؛
                    {{ to_persian_num((string) $includedSpecialists) }} متخصص در قیمت پلن و هر متخصص بیشتر ماهانه {{ to_persian_num(number_format($extraSpecialistPrice)) }} تومان.
                    برای تغییر تعداد متخصص با پشتیبانی تماس بگیرید.
                </p>
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
