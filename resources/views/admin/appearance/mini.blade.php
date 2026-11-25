{{--
    طرح کوچک یک قالب (بسته‌ی «و»؛ بسته‌ی ۵الف: طرح ۴ قالب ترکیبی): $layout (mahru|signature|editorial|showcase|app)، $tokens (یک حالت:
    تیره یا روشن، کانال RGB). رنگ‌ها روی خود جعبه به متغیرهای --mm-* داده می‌شوند تا کنار هم با پالت‌های مختلف درست دیده شوند.
    سبک‌ها: admin/appearance/mini-styles.
--}}
@php
    $mmBtn = $layout === 'mahru' ? 'linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#A67F2E 100%)' : 'rgb('.$tokens['gold'].')';
    $mmAc2 = $tokens['accent2'] ?? $tokens['gold2'];
    $mmStyle = "--mm-bg:{$tokens['bg']};--mm-bg2:{$tokens['bg2']};--mm-bg3:{$tokens['bg3']};--mm-tx:{$tokens['tx']};--mm-mu:{$tokens['mu']};--mm-ac:{$tokens['gold']};--mm-ac2:{$mmAc2};--mm-btn:{$mmBtn};";
@endphp
<div class="mm mm--{{ $layout }}" style="{{ $mmStyle }}" aria-hidden="true">
    @switch($layout)
        @case('signature')
            <div class="mm-sg"><div><small>از سال …</small><span class="mm-serif">تیتر نسخ<br>دوخطی</span><span class="mm-dots"><i></i><i></i><i></i></span></div>
                <span class="mm-f"><i class="mm-bar" style="width:60%"></i><i class="mm-bar"></i><span class="mm-row"><i></i><i></i><i></i><i></i></span><span class="mm-row"><i></i><i></i><i></i></span><i class="mm-pill" style="width:100%"></i></span></div>
            @break
        @case('editorial')
            <div class="mm-ed"><small>سه‌شنبه ۳ آذر · امروز باز تا ۲۱</small><span class="mm-dbl"></span><span class="mm-serif">سالن</span><i class="mm-line"></i>
                <div class="mm-cols"><span class="mm-art" style="border-radius:2px"></span><span class="mm-dark"></span></div></div>
            @break
        @case('showcase')
            <div class="mm-sc"><span class="mm-art"><span class="mm-thumbs"><i></i><i></i><i></i><i></i></span></span>
                <div><b>سالن</b><span class="mm-slot">نزدیک‌ترین نوبت</span><i class="mm-bar"></i><i class="mm-bar" style="width:70%"></i><span class="mm-row"><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i></span></div></div>
            @break
        @case('app')
            <div class="mm-ap"><span class="mm-cardx"><span class="mm-ring"></span><b>سالن</b><i class="mm-bar" style="width:80%"></i><i class="mm-pill" style="width:90%"></i></span>
                <span class="mm-grid"><span class="mm-band"><i></i><i></i><i></i><i></i><i></i><i></i></span><span></span><span></span><span class="mm-a2"></span><span style="grid-column:span 2"></span><span class="mm-art" style="border-radius:7px"></span></span></div>
            @break
        @default
            <div class="mm-m"><i class="mm-bar" style="width:30%"></i><b style="color:rgb(var(--mm-ac2))">حسِ خاص بودن</b><i class="mm-pill"></i><span class="mm-row"><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i></span></div>
    @endswitch
</div>
