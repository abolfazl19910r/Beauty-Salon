{{-- نوار سهم در گزارش PDF (mPDF div تو در توی درصدی را نمی‌کشد، پس جدول دوخانه‌ای). $percent: ۰ تا ۱۰۰.
     جدول LTR با «باقی‌مانده» اول، تا بخش پر از سمت راست شروع شود (خوانش راست‌به‌چپ). --}}
@php $barPercent = (int) max(0, min(100, round($percent))); @endphp
<table class="bar" dir="ltr"><tr>
    @if ($barPercent < 100)<td class="rest">&nbsp;</td>@endif
    @if ($barPercent > 0)<td class="fill" style="width: {{ $barPercent }}%;">&nbsp;</td>@endif
</tr></table>
