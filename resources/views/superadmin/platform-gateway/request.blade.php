@extends('layouts.superadmin')

@section('title', 'درخواست درگاه پلتفرم — '.$req->salon?->name)

@php($pct = fn ($v) => to_persian_num(rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.')).'٪')

@section('content')
    <div class="sa-card sa-card-pad text-sm space-y-2 mb-6" style="color: var(--sa-text);" data-platform-request>
        <div class="sa-card-head">
            <div class="sa-card-title">{{ $req->salon?->name }} <span class="text-xs" dir="ltr" style="color: var(--sa-text-dim);">{{ $req->salon?->slug }}</span></div>
            <div class="sa-card-desc">وضعیت: <b>{{ $req->statusLabel() }}</b> · ارسال: {{ \Morilog\Jalali\Jalalian::fromCarbon($req->created_at)->format('Y/m/d H:i') }} توسط {{ $req->requester?->name }} <span dir="ltr">{{ $req->requester?->phone }}</span></div>
        </div>
        <div>روش واریز: <b>{{ \App\Support\PayoutDestination::label($req->payout_method) }}</b></div>
        <div>شماره‌ی کامل: <b dir="ltr" class="select-all" data-full-destination>{{ $req->destination() }}</b></div>
        <div>صاحب حساب: <b>{{ $req->account_holder }}</b>@if ($req->payout_bank) · بانک: {{ $req->payout_bank }}@endif</div>
        @if ($req->owner_note)<div>توضیح مالک: {{ $req->owner_note }}</div>@endif
        <div class="text-xs leading-6" style="color: var(--sa-text-dim);">پیش از تأیید، نام صاحب حساب را با استعلام شبا/کارت در بانک مقایسه کنید؛ و احراز سالن («احراز سالن‌ها») تأییدشده باشد.</div>
        @if ($req->status !== 'pending' && $req->review_note)<div>یادداشت بررسی: {{ $req->review_note }}</div>@endif
        @if ($req->fee_percent !== null)<div>کارمزد: {{ $pct($req->fee_percent) }}</div>@endif
    </div>

    @if ($req->status === 'pending')
        <div class="grid md:grid-cols-2 gap-6">
            <form method="POST" action="{{ route('superadmin.platform-gateway.requests.approve', $req) }}" class="sa-card sa-card-pad space-y-3">
                @csrf
                <div class="sa-card-title">تأیید</div>
                <label class="sa-label" for="fee_percent">کارمزد پلتفرم از هر پرداخت (٪، حداکثر {{ to_persian_num((string) $maxFee) }})</label>
                <input id="fee_percent" name="fee_percent" value="{{ old('fee_percent') }}" class="sa-input" dir="ltr" inputmode="decimal" placeholder="10">
                @error('fee_percent')<div class="text-xs" style="color: var(--sa-bad, #c0392b);">{{ $message }}</div>@enderror
                <div class="text-xs leading-6" style="color: var(--sa-text-dim);">این درصد از کل مبلغ هر پرداخت نزد پلتفرم می‌ماند (مثلاً برای مالیات و کارمزد درگاه) و بقیه سهم سالن است. تغییر بعدی فقط روی پرداخت‌های تازه اثر دارد.</div>
                <label class="sa-label" for="review_note">یادداشت (اختیاری)</label>
                <input id="review_note" name="review_note" value="{{ old('review_note') }}" class="sa-input">
                <button type="submit" class="sa-btn" data-approve>تأیید و روشن کردن درگاه پلتفرم</button>
            </form>
            <form method="POST" action="{{ route('superadmin.platform-gateway.requests.reject', $req) }}" class="sa-card sa-card-pad space-y-3">
                @csrf
                <div class="sa-card-title">رد</div>
                <label class="sa-label" for="reason">دلیل (برای مالک پیامک می‌شود)</label>
                <textarea id="reason" name="reason" rows="3" class="sa-input">{{ old('reason') }}</textarea>
                @error('reason')<div class="text-xs" style="color: var(--sa-bad, #c0392b);">{{ $message }}</div>@enderror
                <button type="submit" class="sa-btn sa-btn-ghost" data-reject>رد درخواست</button>
            </form>
        </div>
    @else
        <a href="{{ route('superadmin.platform-gateway.salon', $req->salon_id) }}" class="sa-btn">حساب و تسویه‌ی این سالن</a>
    @endif

    @if ($history->isNotEmpty())
        <div class="sa-card sa-card-pad mt-6 text-sm">
            <div class="sa-card-title mb-2">درخواست‌های قبلی همین سالن</div>
            @foreach ($history as $h)
                <div class="py-1">#{{ to_persian_num((string) $h->id) }} · {{ $h->statusLabel() }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($h->created_at)->format('Y/m/d') }} · <span dir="ltr">{{ $h->maskedDestination() }}</span>@if ($h->review_note) · {{ $h->review_note }}@endif</div>
            @endforeach
        </div>
    @endif
@endsection
