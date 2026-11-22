{{--
    درگاه پرداخت پلتفرم (۲۰۲۶-۱۱-۲۲) — AdminPlatformGatewayController. سالنی که مرچنت خودش را ندارد، با تأیید ابرمدیر پرداخت مشتری‌ها
    را با درگاه پلتفرم می‌گیرد؛ کارمزد پلتفرم از هر پرداخت کم و بقیه با «تسویه» به حساب سالن واریز می‌شود.
    $pg: enabled, latest (?PlatformGatewayRequest), active, summary (ریال), settlements, maxFee
--}}
@php
    $toman = fn (int $rial) => to_persian_num(number_format(intdiv($rial, 10))).' تومان';
    $pct = fn ($v) => to_persian_num(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.')).'٪';
    $latest = $pg['latest'];
    $canRequest = $pg['enabled'] && (! $latest || in_array($latest->status, ['rejected', 'revoked'], true));
@endphp
<div class="{{ $card }} space-y-4" style="{{ $cardStyle }}" id="platform-gateway" data-platform-gateway>
    <div>
        <h2 class="font-bold" style="color: var(--admin-text);">درگاه پرداخت {{ config('brand.name') }}</h2>
        <p class="text-sm mt-1 leading-7" style="color: var(--admin-text-dim);">
            اگر درگاه پرداخت (مرچنت) جدا ندارید، پرداخت مشتری‌های سالن را با درگاه {{ config('brand.name') }} بگیرید. از هر پرداخت، کارمزدی که
            {{ config('brand.name') }} هنگام تأیید تعیین می‌کند کم می‌شود و بقیه با تسویه به حساب سالن واریز می‌شود.
        </p>
    </div>

    @if ($latest)
        <div class="rounded-lg p-3 text-sm leading-7" style="background:var(--admin-accent-light); color:var(--admin-text);" data-platform-gateway-status="{{ $latest->status }}">
            وضعیت درخواست: <b>{{ $latest->statusLabel() }}</b>
            @if ($latest->status === 'approved')
                · کارمزد پلتفرم: <b>{{ $pct($latest->fee_percent) }}</b> از هر پرداخت
                @unless ($pg['enabled'])<br><span style="color:rgb(var(--m-bad));">درگاه پلتفرم فعلاً برای همه‌ی سالن‌ها خاموش است؛ پرداخت تازه‌ای با آن گرفته نمی‌شود.</span>@endunless
            @endif
            <br>واریز سهم سالن به: <span dir="ltr">{{ $latest->maskedDestination() }}</span> ({{ $latest->account_holder }})
            @if (in_array($latest->status, ['rejected', 'revoked'], true) && $latest->review_note)
                <br>دلیل: {{ $latest->review_note }}
            @endif
        </div>
    @endif

    @if ($pg['summary']['count'] > 0)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm" data-platform-gateway-summary>
            <div><div style="color:var(--admin-text-dim);">کل پرداخت‌ها</div><b>{{ $toman($pg['summary']['gross']) }}</b></div>
            <div><div style="color:var(--admin-text-dim);">کارمزد پلتفرم</div><b>{{ $toman($pg['summary']['platform']) }}</b></div>
            <div><div style="color:var(--admin-text-dim);">سهم سالن</div><b>{{ $toman($pg['summary']['salon']) }}</b></div>
            <div><div style="color:var(--admin-text-dim);">واریزشده / مانده</div><b>{{ $toman($pg['summary']['settled']) }}</b> / {{ $toman($pg['summary']['outstanding'] + $pg['summary']['pending']) }}</div>
        </div>
    @endif

    @if ($pg['settlements']->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead><tr style="color:var(--admin-text-dim);"><th class="py-2">تسویه</th><th class="py-2">مبلغ</th><th class="py-2">وضعیت</th><th class="py-2">پیگیری بانک</th></tr></thead>
                <tbody>
                @foreach ($pg['settlements'] as $s)
                    <tr style="border-top:1px solid var(--admin-border);">
                        <td class="py-2">#{{ to_persian_num((string) $s->id) }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($s->created_at)->format('Y/m/d') }}</td>
                        <td class="py-2">{{ $toman($s->salon_share_rial) }}</td>
                        <td class="py-2">{{ $s->statusLabel() }}@if ($s->paid_at) ({{ \Morilog\Jalali\Jalalian::fromCarbon($s->paid_at)->format('Y/m/d') }})@endif</td>
                        <td class="py-2" dir="ltr">{{ $s->bank_reference ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($canRequest)
        <form method="POST" action="{{ route('admin.payment-gateways.platform.store') }}" class="space-y-3" data-platform-gateway-form>
            @csrf
            <div class="grid md:grid-cols-2 gap-3">
                <label class="block text-sm" style="color:var(--admin-text);">روش واریز سهم سالن
                    <select name="payout_method" class="{{ $input }} mt-1" style="{{ $inputStyle }}">
                        @foreach (\App\Support\PayoutDestination::METHODS as $key => $label)
                            <option value="{{ $key }}" @selected(old('payout_method', 'iban') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm" style="color:var(--admin-text);">شماره‌ی شبا / کارت / حساب
                    <input name="destination" value="{{ old('destination') }}" dir="ltr" inputmode="numeric" autocomplete="off" class="{{ $input }} mt-1" style="{{ $inputStyle }}" placeholder="IR…">
                </label>
                <label class="block text-sm" style="color:var(--admin-text);">نام صاحب حساب
                    <input name="account_holder" value="{{ old('account_holder') }}" class="{{ $input }} mt-1" style="{{ $inputStyle }}">
                </label>
                <label class="block text-sm" style="color:var(--admin-text);">نام بانک (برای شماره‌ی حساب الزامی)
                    <input name="payout_bank" value="{{ old('payout_bank') }}" class="{{ $input }} mt-1" style="{{ $inputStyle }}">
                </label>
            </div>
            <label class="block text-sm" style="color:var(--admin-text);">توضیح برای {{ config('brand.name') }} (اختیاری)
                <textarea name="owner_note" rows="2" class="{{ $input }} mt-1" style="{{ $inputStyle }}">{{ old('owner_note') }}</textarea>
            </label>
            <label class="flex items-start gap-2 text-sm leading-7" style="color:var(--admin-text);">
                <input type="checkbox" name="accept_fee" value="1" class="mt-2" @checked(old('accept_fee'))>
                <span>می‌پذیرم که کارمزد {{ config('brand.name') }} (درصدی که هنگام تأیید اعلام می‌شود، حداکثر {{ $pct($pg['maxFee']) }}) از هر پرداخت کم شود
                و بقیه با تسویه به همین حساب واریز شود. نام صاحب حساب باید با نام ثبت‌شده در بانک یکی باشد.</span>
            </label>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold" style="background:var(--admin-accent); color:#fff;">ارسال درخواست</button>
        </form>
    @endif
</div>
