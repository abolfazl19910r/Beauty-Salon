{{-- سند PDF پذیرش قرارداد سالن (۲۰۲۶-۱۱-۲۲) — SalonContractController. mPDF: رنگ‌ها ثابت، بدون متغیر CSS --}}
@php
    $at = $acceptance->accepted_at ?? $acceptance->created_at;
    $sources = ['web' => 'وب', 'signup' => 'ثبت‌نام سالن', 'app' => 'اپ', 'app-implicit' => 'اپ (ضمنی، نسخه‌ی قدیمی)', 'staff' => 'ثبت حضوری'];
@endphp
<html dir="rtl"><head><meta charset="utf-8">
<style>
    body { font-family: vazir; font-size: 10.5pt; color: #222; line-height: 1.8; }
    h1 { font-size: 15pt; text-align: center; margin: 0 0 4pt; }
    .sub { text-align: center; color: #666; font-size: 9pt; margin-bottom: 10pt; }
    table.meta { width: 100%; border-collapse: collapse; margin: 6pt 0 10pt; }
    table.meta td { border: 1px solid #ccc; padding: 4pt 6pt; vertical-align: top; }
    table.meta td.k { width: 30%; background: #f4f1ea; font-weight: bold; }
    .ltr { direction: ltr; text-align: left; }
    .warn { border: 1px solid #c9a24b; background: #fbf6ea; padding: 6pt 8pt; margin: 8pt 0; font-size: 9.5pt; }
    h2 { font-size: 12pt; margin: 12pt 0 4pt; }
    h3 { font-size: 11pt; margin: 8pt 0 2pt; }
    .foot { margin-top: 14pt; font-size: 8.5pt; color: #666; border-top: 1px solid #ccc; padding-top: 4pt; }
</style></head><body>
    <h1>سند پذیرش {{ $acceptance->documentLabel() }}</h1>
    <div class="sub">{{ $platform['brand'] }} ({{ $platform['domain'] }})@if ($platform['legal_name']) — {{ $platform['legal_name'] }}@endif @if ($platform['national_id']) · شناسه‌ی ملی {{ to_persian_num((string) $platform['national_id']) }}@endif</div>

    <table class="meta">
        <tr><td class="k">سالن</td><td>{{ $salon->name }} <span class="ltr">({{ $salon->slug }})</span></td></tr>
        <tr><td class="k">پذیرنده</td><td>{{ $acceptance->user?->name ?: '—' }} @if ($acceptance->user?->phone)<span class="ltr">{{ \App\Support\Fa::digits($acceptance->user->phone) }}</span>@endif</td></tr>
        <tr><td class="k">سند و نسخه</td><td>{{ $acceptance->documentLabel() }} — نسخه‌ی {{ to_persian_num(str_replace('-', '/', (string) $acceptance->version)) }}</td></tr>
        <tr><td class="k">زمان پذیرش</td><td>{{ $at ? to_persian_num(fa_date($at->copy()->timezone(config('app.timezone')), true)) : '—' }} به وقت <span class="ltr">{{ config('app.timezone') }}</span><br><span class="ltr" style="font-size: 8.5pt;">UTC {{ $at?->copy()->utc()->format('Y-m-d H:i:s') }}</span></td></tr>
        <tr><td class="k">نشانی IP</td><td class="ltr">{{ $acceptance->ip_address ?: '—' }}</td></tr>
        <tr><td class="k">مرورگر / دستگاه</td><td class="ltr" style="font-size: 8.5pt;">{{ $acceptance->user_agent ?: '—' }}</td></tr>
        <tr><td class="k">منبع پذیرش</td><td>{{ $sources[$acceptance->source] ?? $acceptance->source }}@if ($acceptance->app_version) — اپ <span class="ltr">{{ $acceptance->app_version }}</span>@endif</td></tr>
        <tr><td class="k">شناسه‌ی سابقه</td><td class="ltr">legal_acceptances #{{ \App\Support\Fa::digits($acceptance->id) }}</td></tr>
    </table>

    @unless ($isCurrent)
        <div class="warn">متن نسخه‌ی پذیرفته‌شده ({{ to_persian_num(str_replace('-', '/', (string) $acceptance->version)) }}) در سامانه بایگانی نمی‌شود؛ متن زیر مال نسخه‌ی فعلی ({{ to_persian_num(str_replace('-', '/', $currentVersion)) }}) است.</div>
    @endunless
    @if ($draft)
        <div class="warn">این متن پیش‌نویس است و هنوز بازبینی حقوقی نشده است.</div>
    @endif

    @if ($acceptance->document === 'salon_terms')
        <h2>متن: قوانین و شرایط — بخش ب، سالن‌ها</h2>
        @include('legal.partials.salon-terms')
    @else
        <p>متن کامل این سند در <span class="ltr">{{ $platform['domain'] }}/{{ $acceptance->document === 'privacy' ? 'privacy' : 'terms' }}</span> است.</p>
    @endif

    <div class="foot">
        این سند از سابقه‌ی ثبت‌شده در سامانه‌ی {{ $platform['brand'] }} ساخته شد: {{ \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i') }}@if ($generatedBy) توسط {{ $generatedBy->name }}@endif.
    </div>
</body></html>
