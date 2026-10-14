@extends('layouts.app')
@section('title', 'پرداخت دوره')

@section('content')
    @php extract(\App\Support\Courses\CourseUi::customer()) @endphp
    <div class="max-w-xl mx-auto fade-in">
        <div class="rounded-xl p-6" style="{{ $card }}">
            @include('courses._flash')
            <h1 class="text-xl font-bold" style="{{ $gold }}">پرداخت دوره‌ی «{{ $course->name }}»</h1>
            <p class="text-sm mt-2 persian-number" style="{{ $dim }}">
                قیمت کل {{ number_format($course->price_total) }} تومان@if ($course->discount_amount > 0) (با {{ number_format($course->discount_amount) }} تومان تخفیف)@endif؛
                پرداخت‌شده {{ number_format($course->amount_paid) }}؛ مانده {{ number_format($course->balanceDue()) }} تومان.
            </p>

            @if (! $onlinePayment)
                <div class="mt-4 p-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); color: #FCA5A5;">پرداخت آنلاین در این سالن فعال نیست؛ پرداخت را در سالن انجام دهید.</div>
            @endif

            <form method="POST" action="{{ route('my-courses.pay.store', $course) }}" class="mt-5 space-y-4" novalidate>
                @csrf
                <div>
                    <label class="block text-sm mb-1" style="color: var(--rasta-cream);" for="amount">مبلغ این پرداخت (تومان)</label>
                    <input id="amount" name="amount" inputmode="numeric" dir="ltr" class="w-full px-3 py-3 rounded-lg text-center persian-number"
                           value="{{ old('amount', (int) $course->balanceDue()) }}"
                           style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.3); color: var(--rasta-cream);">
                    <p class="text-xs mt-1 persian-number" style="{{ $dim }}">
                        @if ($minAmount < $course->balanceDue())
                            حداقل {{ number_format($minAmount) }} و حداکثر {{ number_format($course->balanceDue()) }} تومان. جلسه‌ها به اندازه‌ی مبلغ پرداخت‌شده رزرو می‌شوند (هر جلسه {{ number_format($course->per_session_value) }} تومان).
                        @else
                            پرداخت کل مبلغ.
                        @endif
                    </p>
                </div>

                @if ($walletBalance > 0)
                    <label class="flex items-center gap-2 text-sm persian-number" style="color: var(--rasta-cream);">
                        <input type="checkbox" name="use_wallet" value="1" @checked(old('use_wallet', true))>
                        استفاده از کیف پول (موجودی {{ number_format($walletBalance) }} تومان)
                    </label>
                @endif

                @if (count($gateways) > 1)
                    <div>
                        <div class="text-sm mb-2" style="color: var(--rasta-cream);">درگاه پرداخت</div>
                        @foreach ($gateways as $gateway)
                            <label class="flex items-center gap-2 text-sm mb-1 persian-number" style="color: var(--rasta-cream);">
                                <input type="radio" name="gateway_id" value="{{ $gateway['id'] }}" @checked($loop->first)>
                                {{ $gateway['name'] }}@if ($gateway['fee'] > 0) <span style="{{ $dim }}">(کارمزد {{ number_format($gateway['fee']) }} تومان)</span>@endif
                            </label>
                        @endforeach
                    </div>
                @endif

                <button type="submit" class="w-full py-3 rounded-lg font-bold" style="{{ $btn }}">پرداخت</button>
                <a href="{{ route('my-courses.show', $course) }}" class="block text-center text-sm" style="{{ $dim }}">بعداً</a>
            </form>
        </div>
    </div>
    <script>
        // ارقام فارسی و جداکننده پیش از ارسال به عدد خام
        document.querySelector('form[action$="/pay"]').addEventListener('submit', function () {
            const input = document.getElementById('amount');
            input.value = (input.value || '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9.]/g, '');
        });
    </script>
@endsection
