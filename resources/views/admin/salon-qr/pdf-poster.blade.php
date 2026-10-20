{{-- پوستر A5 چاپی QR (بسته‌ی ۵) — mPDF: بدون متغیر CSS؛ تیره روی سفید. ورودی: $target، $logoPath، $brand --}}
<style>
    body { font-family: vazir; color: #1A1410; }
    .center { text-align: center; }
    .title { font-size: 22pt; font-weight: bold; margin-top: 4mm; }
    .subtitle { font-size: 13pt; color: #4a3f36; margin-top: 2mm; }
    .scan { font-size: 12pt; margin-top: 4mm; }
    .url { font-size: 9pt; color: #555; direction: ltr; margin-top: 3mm; }
    .code { font-size: 11pt; margin-top: 3mm; }
    .brand { font-size: 8pt; color: #888; margin-top: 6mm; }
</style>
<div class="center">
    @if ($logoPath)
        <img src="{{ $logoPath }}" style="max-height: 26mm; max-width: 60mm;">
    @endif
    <div class="title">{{ $target['title'] }}</div>
    <div class="subtitle">{{ $target['subtitle'] }}</div>
    <div style="margin-top: 6mm;">
        <barcode code="{{ $target['url'] }}" type="QR" size="2.4" error="M" disableborder="1" />
    </div>
    <div class="scan">با دوربین گوشی اسکن کنید و نوبت بگیرید</div>
    <div class="url">{{ $target['url'] }}</div>
    <div class="code">کد سالن در اپ {{ $brand }}: <b><span dir="ltr">{{ $target['code'] }}</span></b></div>
    <div class="brand">نوبت‌دهی آنلاین با {{ $brand }}</div>
</div>
