{{-- برگه‌ی A4 با ۸ کارت ویزیتی ۸۵×۵۵ میلی‌متر برای برش (بسته‌ی ۵). خط‌چین = خط برش. ورودی: $target، $logoPath، $brand --}}
<style>
    body { font-family: vazir; color: #1A1410; }
    table.sheet { border-collapse: collapse; margin: 0 auto; }
    td.card { width: 85mm; height: 55mm; border: 0.2mm dashed #999; padding: 3mm; vertical-align: middle; }
    td.qr { width: 38mm; text-align: center; vertical-align: middle; }
    td.text { vertical-align: middle; text-align: right; padding-right: 2mm; }
    .t { font-size: 11pt; font-weight: bold; }
    .s { font-size: 8.5pt; color: #4a3f36; margin-top: 1mm; }
    .scan { font-size: 8pt; margin-top: 2mm; }
    .code { font-size: 7.5pt; color: #555; margin-top: 1.5mm; }
</style>
<table class="sheet">
    @for ($row = 0; $row < 4; $row++)
        <tr>
            @for ($col = 0; $col < 2; $col++)
                <td class="card">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="text">
                                @if ($logoPath)
                                    <img src="{{ $logoPath }}" style="max-height: 9mm; max-width: 28mm;"><br>
                                @endif
                                <div class="t">{{ $target['title'] }}</div>
                                <div class="s">{{ $target['subtitle'] }}</div>
                                <div class="scan">اسکن کنید و نوبت بگیرید</div>
                                <div class="code">کد در اپ {{ $brand }}: <span dir="ltr">{{ $target['code'] }}</span></div>
                            </td>
                            <td class="qr"><barcode code="{{ $target['url'] }}" type="QR" size="1.3" error="M" disableborder="1" /></td>
                        </tr>
                    </table>
                </td>
            @endfor
        </tr>
    @endfor
</table>
